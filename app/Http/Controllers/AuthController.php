<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\RefreshToken;
use App\Models\OtpVerification;
use App\Notifications\LoginOtpNotification;
use App\Traits\ApiResponseHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;

class AuthController extends Controller
{
    use ApiResponseHandler;
    
    /**
     * Login user and send OTP to email.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->handleResponse([
                'type' => 'validation_error',
                'data' => $validator->errors(),
                'message' => 'Validation failed'
            ]);
        }

        // Find user by email and active status
        $user = User::where('email', $request->email)
                    ->where('status', User::STATUS_ACTIVE)
                    ->first();

        // If user not found or inactive
        if (!$user) {
            return $this->handleResponse([
                'type' => 'unauthenticated',
                'status' => 401,
                'data' => ['email' => ['The provided credentials are incorrect.']],
                'message' => 'The provided credentials are incorrect.'
            ]);
        }

        // Check password - use direct comparison instead of Hash::check
        $passwordCorrect = false;
        
        // First try: Use legacy password verification (md5, sha1 or plain text)
        if ($this->verifyLegacyPassword($request->password, $user->password)) {
            $passwordCorrect = true;
        } else {
            // Second try: Use Bcrypt check in case some passwords are already migrated
            try {
                $passwordCorrect = Hash::check($request->password, $user->password);
            } catch (RuntimeException $e) {
                // Do nothing here - already tried legacy verification
            }
        }

        // If password is incorrect
        if (!$passwordCorrect) {
            return $this->handleResponse([
                'type' => 'unauthenticated',
                'status' => 401,
                'data' => ['email' => ['The provided credentials are incorrect.']],
                'message' => 'The provided credentials are incorrect.'
            ]);
        }

        // Generate and send OTP
        $otp = $this->generateOtp();
        
        // Delete any existing OTPs for this user
        OtpVerification::where('user_id', $user->id)->delete();
        
        // Create new OTP record
        OtpVerification::create([
            'user_id' => $user->id,
            'otp' => $otp,
            'verified' => false,
            'expires_at' => now()->addMinutes(10), // OTP valid for 10 minutes
        ]);
        
        // Send OTP email
        $user->notify(new LoginOtpNotification($otp));
        
        return $this->handleResponse([
            'type' => 'success',
            'data' => [
                'user_id' => $user->id,
                'message' => 'OTP has been sent to your email'
            ],
            'message' => 'Please verify your email with the OTP sent'
        ]);
    }
    
    /**
     * Verify OTP and issue tokens.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function verifyOtp(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'otp' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return $this->handleResponse([
                'type' => 'validation_error',
                'data' => $validator->errors(),
                'message' => 'Validation failed'
            ]);
        }
        
        // Find OTP record
        $otpVerification = OtpVerification::where('user_id', $request->user_id)
            ->where('otp', $request->otp)
            ->where('verified', false)
            ->where('expires_at', '>', now())
            ->first();
            
        if (!$otpVerification) {
            return $this->handleResponse([
                'type' => 'validation_error',
                'status' => 400,
                'data' => ['otp' => ['Invalid or expired OTP.']],
                'message' => 'Invalid or expired OTP'
            ]);
        }
        
        // Mark OTP as verified
        $otpVerification->verified = true;
        $otpVerification->save();
        
        // Get user
        $user = User::find($request->user_id);
        
        // Revoke all existing tokens for this user (single session enforcement)
        $user->tokens()->delete();
        
        // Delete all existing refresh tokens for this user
        RefreshToken::where('user_id', $user->id)->delete();
        
        // Generate access token
        $accessToken = $user->createToken('mobile-app')->plainTextToken;
        
        // Generate refresh token
        $refreshToken = Str::random(60);
        
        // Store refresh token in database
        RefreshToken::create([
            'user_id' => $user->id,
            'token' => hash('sha256', $refreshToken),
            'expires_at' => now()->addDays(30),
        ]);
        
        // Store access token in user record
        $user->token = $accessToken;
        $user->save();
        
        return $this->handleResponse([
            'type' => 'success',
            'data' => [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_type' => 'Bearer'
            ],
            'message' => 'Login successful'
        ]);
    }
    
    /**
     * Resend OTP if needed.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function resendOtp(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->handleResponse([
                'type' => 'validation_error',
                'data' => $validator->errors(),
                'message' => 'Validation failed'
            ]);
        }
        
        // Get user
        $user = User::find($request->user_id);
        
        // Check for rate limiting (optional - prevent abuse)
        $lastOtp = OtpVerification::where('user_id', $user->id)
            ->latest()
            ->first();
            
        if ($lastOtp && $lastOtp->created_at->diffInMinutes(now()) < 2) {
            return $this->handleResponse([
                'type' => 'error',
                'status' => 429,
                'data' => [],
                'message' => 'Please wait before requesting another OTP'
            ]);
        }
        
        // Generate and send new OTP
        $otp = $this->generateOtp();
        
        // Delete any existing OTPs for this user
        OtpVerification::where('user_id', $user->id)->delete();
        
        // Create new OTP record
        OtpVerification::create([
            'user_id' => $user->id,
            'otp' => $otp,
            'verified' => false,
            'expires_at' => now()->addMinutes(10),
        ]);
        
        // Send OTP email
        $user->notify(new LoginOtpNotification($otp));
        
        return $this->handleResponse([
            'type' => 'success',
            'data' => [
                'message' => 'OTP has been resent to your email'
            ],
            'message' => 'OTP resent successfully'
        ]);
    }

    /**
     * Refresh access token using refresh token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function refresh(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'refresh_token' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->handleResponse([
                'type' => 'validation_error',
                'data' => $validator->errors(),
                'message' => 'Validation failed'
            ]);
        }
        
        // Find and validate refresh token
        $refreshToken = RefreshToken::where('token', hash('sha256', $request->refresh_token))
            ->where('expires_at', '>', now())
            ->first();
            
        if (!$refreshToken) {
            return $this->handleResponse([
                'type' => 'unauthenticated',
                'status' => 401,
                'data' => ['refresh_token' => ['Invalid or expired refresh token.']],
                'message' => 'Invalid or expired refresh token'
            ]);
        }
        
        // Get user
        $user = User::find($refreshToken->user_id);
        
        // Revoke all tokens
        $user->tokens()->delete();
        
        // Delete ALL refresh tokens for this user (not just the used one)
        RefreshToken::where('user_id', $user->id)->delete();
        
        // Create new access token
        $accessToken = $user->createToken('mobile-app')->plainTextToken;
        
        // Create new refresh token
        $newRefreshToken = Str::random(60);
        
        // Store new refresh token
        RefreshToken::create([
            'user_id' => $user->id,
            'token' => hash('sha256', $newRefreshToken),
            'expires_at' => now()->addDays(30),
        ]);
        
        // Store token in user record (keeping original functionality)
        $user->token = $accessToken;
        $user->save();
        
        return $this->handleResponse([
            'type' => 'success',
            'data' => [
                'access_token' => $accessToken,
                'refresh_token' => $newRefreshToken,
                'token_type' => 'Bearer'
            ],
            'message' => 'Token refreshed successfully'
        ]);
    }

    /**
     * Logout user (revoke token).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        // Get user before deleting tokens
        $user = $request->user();
        
        // Delete all access tokens for the user
        $user->tokens()->delete();
        
        // Delete all refresh tokens for the user
        RefreshToken::where('user_id', $user->id)->delete();
        
        // Clear token in user record (keeping original functionality)
        $user->token = null;
        $user->save();
        
        return $this->handleResponse([
            'type' => 'success',
            'data' => [],
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Generate a 6-digit OTP.
     *
     * @return string
     */
    private function generateOtp()
    {
        return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Legacy password verification method
     * 
     * @param string $plainPassword The input password from the request
     * @param string $storedPassword The password stored in the database
     * @return bool Whether the password is correct
     */
    private function verifyLegacyPassword($plainPassword, $storedPassword)
    {
        // Option 1: Plain text comparison (not recommended but possible)
        if ($plainPassword === $storedPassword) {
            return true;
        }
        
        // Option 2: MD5 (common in legacy systems)
        if (md5($plainPassword) === $storedPassword) {
            return true;
        }
        
        // Option 3: SHA1 (also common)
        if (sha1($plainPassword) === $storedPassword) {
            return true;
        }
        
        // Add other algorithms as needed
        // Example: SHA256
        if (hash('sha256', $plainPassword) === $storedPassword) {
            return true;
        }
        
        // No match with any of the legacy formats
        return false;
    }
}