<?php

namespace App\Traits;

trait ApiResponseHandler
{
        /**
     * Standardized response handler
     * 
     * @param array $options Response options
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleResponse($options = [])
    {
        $defaults = [
            'type' => 'success',       // Response type (success, error, not_found, etc.)
            'data' => "",              // Response data
            'message' => '',           // Response message
            'status' => null,          // Status code for the response body
            'httpStatus' => null,      // HTTP status code (can differ from status)
            'useEncryption' => false,  // Whether to encrypt the response
            'shouldLog' => false,      // Whether to log this response
            'logLevel' => 'info',      // Log level (info, error, warning, debug, etc.)
            'logContext' => [],        // Additional log context
            'exception' => null        // Optional exception for error responses
        ];
        
        $options = array_merge($defaults, $options);
        
        // Set appropriate status codes and messages based on response type
        switch ($options['type']) {
            case 'success':
                $options['status'] = $options['status'] ?? 200;
                $options['message'] = $options['message'] ?: 'Success';
                $options['httpStatus'] = $options['httpStatus'] ?? $options['status'];
                $options['logLevel'] = $options['logLevel'] ?? 'info';
                break;
                
            case 'error':
                $options['status'] = $options['status'] ?? 500;
                $options['message'] = $options['message'] ?: 'Server Error';
                $options['httpStatus'] = $options['httpStatus'] ?? $options['status'];
                $options['logLevel'] = $options['logLevel'] ?? 'error';
                
                // If exception is provided and message is empty, use exception message
                if ($options['exception'] && empty($options['data'])) {
                    $options['data'] = $options['message'] . ': ' . $options['exception']->getMessage();
                }
                break;
                
            case 'not_found':
                $options['status'] = $options['status'] ?? 404;
                $options['message'] = $options['message'] ?: 'Record not found';
                $options['httpStatus'] = $options['httpStatus'] ?? $options['status'];
                $options['logLevel'] = $options['logLevel'] ?? 'warning';
                break;
                
            case 'validation_error':
                $options['status'] = $options['status'] ?? 422;
                $options['message'] = $options['message'] ?: 'Validation failed';
                $options['httpStatus'] = $options['httpStatus'] ?? $options['status'];
                $options['logLevel'] = $options['logLevel'] ?? 'warning';
                break;
                
            case 'unauthorized':
                $options['status'] = $options['status'] ?? 403;
                $options['message'] = $options['message'] ?: 'You do not have permission to access this resource';
                $options['httpStatus'] = $options['httpStatus'] ?? $options['status'];
                $options['logLevel'] = $options['logLevel'] ?? 'warning';
                break;
                
            // Adding the new response types
            case 'invalid_token':
                $options['status'] = $options['status'] ?? 405;
                $options['message'] = $options['message'] ?: 'token_missing_or_invalid';
                $options['data'] = $options['data'] ?: 'Authentication token is missing or invalid';
                $options['httpStatus'] = $options['httpStatus'] ?? $options['status'];
                $options['logLevel'] = $options['logLevel'] ?? 'warning';
                break;
                
            case 'unauthenticated':
                $options['status'] = $options['status'] ?? 401;
                $options['message'] = $options['message'] ?: 'Authentication Required';
                $options['data'] = $options['data'] ?: 'User not authenticated';
                $options['httpStatus'] = $options['httpStatus'] ?? $options['status'];
                $options['logLevel'] = $options['logLevel'] ?? 'warning';
                break;
                
            case 'inactive_user':
                $options['status'] = $options['status'] ?? 402;
                $options['message'] = $options['message'] ?: 'Inactive Account';
                $options['data'] = $options['data'] ?: 'Your account is inactive. Please contact administrator';
                $options['httpStatus'] = $options['httpStatus'] ?? $options['status'];
                $options['logLevel'] = $options['logLevel'] ?? 'warning';
                break;
                
            case 'too_many_requests':
                $options['status'] = $options['status'] ?? 429;
                $options['message'] = $options['message'] ?: 'too_many_requests';
                $options['data'] = $options['data'] ?: 'Too many requests. Please try again later';
                $options['httpStatus'] = $options['httpStatus'] ?? $options['status'];
                $options['logLevel'] = $options['logLevel'] ?? 'warning';
                break;
        }
        
        // Handle logging if enabled
        if ($options['shouldLog']) {
            $logLevel = $options['logLevel'];
            $logMessage = "{$options['type']}: {$options['message']} (Status: {$options['status']})";
            $logContext = $options['logContext'];
            
            if ($options['exception']) {
                $logContext['exception'] = $options['exception']->getMessage();
                $logContext['trace'] = $options['exception']->getTraceAsString();
            }
            
            \Illuminate\Support\Facades\Log::$logLevel($logMessage, $logContext);
        }
        
        // Prepare response data
        $responseData = [
            'status' => $options['status'],
            'msg' => $options['message'],
            'data' => $options['data']
        ];
        
        // Return encrypted or regular response
        // if ($options['useEncryption']) {
        //     return response()->json([
        //         'status' => $options['status'],
        //         'msg' => $options['message'],
        //         'encrypted_data' => $this->encryptionService->encrypt(json_encode($responseData)),
        //     ], $options['httpStatus'] ?? $options['status']);
        // }
        
        return response()->json($responseData, $options['httpStatus'] ?? $options['status']);
    }
}