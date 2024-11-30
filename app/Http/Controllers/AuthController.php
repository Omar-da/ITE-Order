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

        
        if(isset($data['image']))
            $data['image'] = $this->storeProfiles($data['image'],'images/profiles');

        // Create a new user
        $user = User::create([
            'name' => $data['name'],
            'phone_number' => $data['phone_number'],
            'image' => $data['image']?? null,
            'location' => $data['location']?? null,
        ]);
        
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


    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function login()
    {

        $user = User::where('phone_number', request()->phone_number)->first();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

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

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth()->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->guard('api')->logout();

        try {
            $refreshToken = request()->get('refresh_token');
            $accessToken = str_replace('Bearer ', '', request()->header('Authorization'));

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

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh(Request $request)
    {       
            // Generate a new access token
            $newAccessToken = JWTAuth::claims(['type' => 'access'])->fromUser(auth()->user());
            $exp = $this->setExpirationTime('access');
            return response()->json([
                'new_access_token' => $this->respondWithToken($newAccessToken, $exp)
            ]);
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token, $exp)
    {
        return [
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $exp
        ];
    }



    protected function setExpirationTime(String $type = null) : Carbon
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
