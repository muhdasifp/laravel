<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseHandler;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use RuntimeException;


class UserController extends Controller
{
    use ApiResponseHandler;

    public function getProfile(Request $request)
    {
        // Get authenticated user
        $user = $request->user();
        
        // Return user profile information
        return $this->handleResponse([
            'type' => 'success',
            'data' => $user,
            'message' => 'Profile retrieved successfully'
        ]);
    }
    
    public function updateProfile(Request $request)
    {
        // Get authenticated user
        $user = $request->user();
        
        // Validate request
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'mobile_no' => 'sometimes|string|max:20',
            'address' => 'sometimes|string',
            'dob' => 'sometimes|date',
            'gender' => 'sometimes|string',
            'school_name' => 'sometimes|string',
            'roll_no' => 'sometimes|string',
            'image_url' => 'sometimes|string',
        ]);
        
        if ($validator->fails()) {
            return $this->handleResponse([
                'type' => 'validation_error',
                'data' => $validator->errors(),
                'message' => 'Validation failed'
            ]);
        }
        
        // Update user profile
        $user->fill($request->only([
            'name', 'email', 'mobile_no', 'address', 'dob', 
            'gender', 'school_name', 'roll_no', 'image_url'
        ]));
        
        $user->save();
        
        return $this->handleResponse([
            'type' => 'success',
            'data' => $user,
            'message' => 'Profile updated successfully'
        ]);
    }
    
    public function changePassword(Request $request)
    {
        // Get authenticated user
        $user = $request->user();
        
        // Validate request
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|min:8|different:current_password',
            'confirm_password' => 'required|same:new_password',
        ]);
        
        if ($validator->fails()) {
            return $this->handleResponse([
                'type' => 'validation_error',
                'data' => $validator->errors(),
                'message' => 'Validation failed'
            ]);
        }
        
        // Verify current password
        $passwordCorrect = false;
        
        // First try: Use legacy password verification
        if ($this->verifyLegacyPassword($request->current_password, $user->password)) {
            $passwordCorrect = true;
        } else {
            // Second try: Use Bcrypt check
            try {
                $passwordCorrect = Hash::check($request->current_password, $user->password);
            } catch (RuntimeException $e) {
                // Do nothing here - already tried legacy verification
            }
        }
        
        if (!$passwordCorrect) {
            return $this->handleResponse([
                'type' => 'validation_error',
                'data' => ['current_password' => ['Current password is incorrect']],
                'message' => 'Current password is incorrect'
            ]);
        }
        
        // Update password - store as bcrypt hash
        $user->password = Hash::make($request->new_password);
        $user->save();
        
        return $this->handleResponse([
            'type' => 'success',
            'data' => [],
            'message' => 'Password changed successfully'
        ]);
    }
  
    public function getUsers(Request $request)
    {
        // No need to check for admin since AdminMiddleware is already doing that
        
        // Get users with pagination
        // $users = User::paginate(10);
        $users = User::all();
        
        return $this->handleResponse([
            'type' => 'success',
            'data' => $users,
            'message' => 'Users retrieved successfully'
        ]);
    }
    
    public function addUser(Request $request)
    {
        // No need to check for admin since AdminMiddleware is already doing that
        
        // Validate request
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'role' => 'required|string|in:' . User::ROLE_ADMIN . ',' . User::ROLE_USER . ',' . User::ROLE_MANAGER,
            'status' => 'sometimes|integer|in:' . User::STATUS_INACTIVE . ',' . User::STATUS_ACTIVE,
            'mobile_no' => 'sometimes|string|max:20',
            'fcm_id' => 'sometimes|string',
            'image_url' => 'sometimes|string',
            'address' => 'sometimes|string',
            'dob' => 'sometimes|date',
            'gender' => 'sometimes|string',
            'school_name' => 'sometimes|string',
            'roll_no' => 'sometimes|string',
        ]);
        
        if ($validator->fails()) {
            return $this->handleResponse([
                'type' => 'validation_error',
                'data' => $validator->errors(),
                'message' => 'Validation failed'
            ]);
        }
        
        // Create new user
        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password); // Always use bcrypt for new users
        $user->role = $request->role;
        $user->status = $request->status ?? User::STATUS_ACTIVE;
        
        // Optional fields
        if ($request->has('mobile_no')) $user->mobile_no = $request->mobile_no;
        if ($request->has('fcm_id')) $user->fcm_id = $request->fcm_id;
        if ($request->has('image_url')) $user->image_url = $request->image_url;
        if ($request->has('address')) $user->address = $request->address;
        if ($request->has('dob')) $user->dob = $request->dob;
        if ($request->has('gender')) $user->gender = $request->gender;
        if ($request->has('school_name')) $user->school_name = $request->school_name;
        if ($request->has('roll_no')) $user->roll_no = $request->roll_no;
        
        $user->save();
        
        return $this->handleResponse([
            'type' => 'success',
            'data' => $user,
            'message' => 'User created successfully'
        ]);
    }
    
    public function editUser(Request $request, $id)
    {
        // No need to check for admin since AdminMiddleware is already doing that
        
        // Find user
        $user = User::find($id);
        
        if (!$user) {
            return $this->handleResponse([
                'type' => 'not_found',
                'message' => 'User not found'
            ]);
        }
        
        // Validate request
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'role' => 'sometimes|string|in:' . User::ROLE_ADMIN . ',' . User::ROLE_USER . ',' . User::ROLE_MANAGER,
            'status' => 'sometimes|integer|in:' . User::STATUS_INACTIVE . ',' . User::STATUS_ACTIVE,
            'mobile_no' => 'sometimes|string|max:20',
            'fcm_id' => 'sometimes|string',
            'image_url' => 'sometimes|string',
            'address' => 'sometimes|string',
            'dob' => 'sometimes|date',
            'gender' => 'sometimes|string',
            'school_name' => 'sometimes|string',
            'roll_no' => 'sometimes|string',
        ]);
        
        if ($validator->fails()) {
            return $this->handleResponse([
                'type' => 'validation_error',
                'data' => $validator->errors(),
                'message' => 'Validation failed'
            ]);
        }
        
        // Update user
        $user->fill($request->only([
            'name', 'email', 'role', 'status', 'mobile_no', 'fcm_id', 
            'image_url', 'address', 'dob', 'gender', 'school_name', 'roll_no'
        ]));
        
        // If password is provided, update it
        if ($request->has('password') && !empty($request->password)) {
            $user->password = Hash::make($request->password);
        }
        
        $user->save();
        
        return $this->handleResponse([
            'type' => 'success',
            'data' => $user,
            'message' => 'User updated successfully'
        ]);
    }
    
    public function removeUser(Request $request, $id)
    {
        // No need to check for admin since AdminMiddleware is already doing that
        
        // Find user
        $user = User::find($id);
        
        if (!$user) {
            return $this->handleResponse([
                'type' => 'not_found',
                'message' => 'User not found'
            ]);
        }
        
        // Prevent removing yourself
        if ($user->id === $request->user()->id) {
            return $this->handleResponse([
                'type' => 'error',
                'message' => 'You cannot remove your own account'
            ]);
        }
        
        // Option 1: Soft delete by changing status to inactive
        $user->status = User::STATUS_INACTIVE;
        $user->save();
        
        // Option 2: Hard delete (uncomment if preferred)
        // $user->delete();
        
        return $this->handleResponse([
            'type' => 'success',
            'data' => [],
            'message' => 'User removed successfully'
        ]);
    }
   
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