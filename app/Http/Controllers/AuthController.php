<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Traits\storeImagesTrait;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidTypeException;
use Illuminate\Support\Facades\Cache;

class AuthController extends Controller
{
    use storeImagesTrait;

    public function register(Request $request)
    {
        // Validate incoming request data
        $data = $request->validate([
            'name' => 'required | string | min:3 | max:20',
            'phone_number' => 'required | numeric | digits:10 | unique:App\Models\User, phone_number | confirmed',
            'phone_number_confirmation' => 'required',
            'image' => 'nullable  | image | max:5120',
            'location' => 'nullable | string | min:3 | max:100',
        ]);

        // Store image in 'Public' folder
        if(isset($data['image']))
            $data['image'] = $this->storeImage($data['image'],'images/profiles');

        // Create a new user
        $user = User::create([
            'name' => $data['name'],
            'phone_number' => $data['phone_number'],
            'image' => $data['image']?? null,
            'location' => $data['location']?? null,
            'role' => 'user',
        ]);
        
        // Set expiration time for tokens
        $expForAccessToken = $this->setExpirationTime('access');
        $expForRefreshToken = $this->setExpirationTime('refresh');

        // Generate refresh and access tokens for the user
        $refreshToken = JWTAuth::customClaims(['type' => 'refresh', 'exp' => $expForRefreshToken])->fromUser($user);
        $accessToken = JWTAuth::customClaims(['exp' => $expForAccessToken])->fromUser($user);

        // Return response with refresh and access tokens and user details
        return response()->json([
            'message' => 'User registered successfully',
            'accessToken' => $this->respondWithToken($accessToken, $expForAccessToken),
            'refreshToken' =>$this->respondWithToken($refreshToken, $expForRefreshToken),
            'user' => $user,
        ], 201);
    }



    public function login(Request $request)
    {

        // Check of number if it exists
        $user = User::where('phone_number', $request->phone_number)->first();
        if (!$user)
            return response()->json(['error' => 'User not found'], 404);

        // Set expiration time for tokens
        $expForAccessToken = $this->setExpirationTime('access');
        $expForRefreshToken = $this->setExpirationTime('refresh');

        // Generate refresh and access tokens for the user
        $refreshToken = JWTAuth::customClaims(['type' => 'refresh', 'exp' => $expForRefreshToken])->fromUser($user);
        $accessToken = JWTAuth::customClaims(['exp' => $expForAccessToken])->fromUser($user);

        // Return response with refresh and access tokens and user details
        return response()->json([
            'message' => 'User login successfully',
            'access_token' => $this->respondWithToken($accessToken, $expForAccessToken),
            'refresh_token' => $this->respondWithToken($refreshToken, $expForRefreshToken),
            'user' => $user
        ],200);
    }

    
    
    public function me()
    {
        return response()->json(auth()->user());
    }

    
    
    public function logout(Request $request)
    {
        auth()->guard('api')->logout();

        try {

            // Get tokens
            $refreshToken = $request->get('refresh_token');
            $accessToken = str_replace('Bearer ', '', $request->header('Authorization'));

            // Blacklist the access token (invalidate it)
            JWTAuth::setToken($accessToken)->invalidate($accessToken);

            // Get the payload and extract the jti claim
            $payload = JWTAuth::setToken($refreshToken)->getPayload($refreshToken);  
            $jti = $payload->get('jti');

            // Blacklist the refresh token (invalidate it)
            Cache::forever("blacklisted_refresh_token_{$jti}", true);            

            return response()->json(['message' => 'Successfully logged out'], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to logout'], 500);
        }
    }

    
    
    public function refresh()
    {       
            // Generate a new access token
            $newAccessToken = JWTAuth::claims(['type' => 'access'])->fromUser(auth()->user());

            // Set expiration time for token
            $exp = $this->setExpirationTime('access');

            return response()->json([
                'new_access_token' => $this->respondWithToken($newAccessToken, $exp)
            ]);
    }


    
    public function to_admin(User $user)        // Promote user to admin
    {
        // Prevent changing the role of the owner
        if($user->id == 1)
            return response()->json('Owner can not be changed', 403);

        if($user->role == 'admin')
            return response()->json('This is an admin not a user');
    
        $user->update([
            'role' => 'admin'
        ]);

        return response()->json('The user become an admin');
    }



    public function to_user(User $user)        // demote admin to user
    {
        // Prevent changing the role of the owner
        if($user->id == 1)
            return response()->json('Owner can not be changed', 403);

        if($user->role == 'user')
            return response()->json('This is a user not an admin');
    
        $user->update([
            'role' => 'user'
        ]);

        return response()->json('The admin become a user');
    }


    
    protected function respondWithToken($token, $exp)       //format the returned token
    {
        return [
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $exp
        ];
    }



    protected function setExpirationTime(String $type = null) : Carbon      // Set unified expiration for tokens
    {
        $expForRefreshToken = Carbon::now()->addMinutes(20160);
        $expForAccessToken = Carbon::now()->addMinutes(60);

        if($type === 'refresh')
            return $expForRefreshToken;
        else if($type === 'access')
            return $expForAccessToken;
        else
            throw new InvalidTypeException('Invalid token type');
    }
}
