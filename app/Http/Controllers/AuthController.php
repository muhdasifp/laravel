<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\RefreshToken; // Added RefreshToken model
use App\Traits\ApiResponseHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str; // Added Str facade
use RuntimeException;

class AuthController extends Controller
{
    use ApiResponseHandler;
    /**
     * Login user and create token.
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
            
            // Optional: Rehash password to Bcrypt for future logins
            // $user->password = Hash::make($request->password);
            // $user->save();
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
        
        // Store access token in user record (keeping original functionality)
        $user->token = $accessToken;
        $user->save();
        
        return $this->handleResponse([
            'type' => 'success',
            'data' => [
                // 'user' => $user,
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_type' => 'Bearer'
            ],
            'message' => 'Login successful'
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
        
        // Delete used refresh token
        $refreshToken->delete();
        
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
        // Delete current access token
        $request->user()->currentAccessToken()->delete();
        
        // Delete refresh token if provided
        if ($request->has('refresh_token')) {
            RefreshToken::where('token', hash('sha256', $request->refresh_token))->delete();
        }
        
        // Clear token in user record (keeping original functionality)
        $user = $request->user();
        $user->token = null;
        $user->save();
        
        return $this->handleResponse([
            'type' => 'success',
            'data' => [],
            'message' => 'Logged out successfully'
        ]);
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