<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Services\UtilityService;
use App\User;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;


class UserController extends Controller
{
    protected $user;
    protected $utilityService;

    public function __construct(
        User $userModel,
        UtilityService $utilityService
    )
    {
        $this->middleware(
            'auth:user',
            [
                'except' =>
                    [
                        'register',
                        'login',
                    ]
            ]
        );
        $this->user = $userModel;
        $this->utilityService = $utilityService;
    }


    public function register(RegisterRequest $request)
    {
        $passwordHash = $this->utilityService->hashPassword($request->password);
        $this->user->createUser($request, $passwordHash);
        $success_message = "registration completed successfully";
        return $this->utilityService->is200Response($success_message);
    }


    /**
     * Get a JWT via given credentials.
     *
     * @param Request $request
     * @return Response
     */
    public function login(LoginUserRequest $request)
    {
        $credentials = $request->only(['email', 'password']);

        if (!$token = Auth::guard('user')->attempt($credentials)) {
            $responseMessage = "invalid username or password";
            return $this->utilityService->is422Response($responseMessage);
        }


        return $this->respondWithToken($token);
    }


    public function viewProfile()
    {
        return response()->json([
                'success' => true,
                'user' => Auth::guard('user')->user()
            ]
            , 200);
    }


    /**
     * Log the application out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        try {

            $this->logUserOut();

        } catch (TokenExpiredException $e) {

            $responseMessage = "token has already been invalidated";
            $this->tokenExpirationException($responseMessage);
        }
    }


    public function logUserOut()
    {
        Auth::guard('user')->logout();
        $responseMessage = "successfully logged out";
        return $this->utilityService->is200Response($responseMessage);
    }


    public function tokenExpirationException($responseMessage)
    {
        return $this->utilityService->is422Response($responseMessage);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refreshToken()
    {
        try {
            return $this->respondWithToken(Auth::guard('user')->refresh());
        } catch (TokenExpiredException $e) {
            $responseMessage = "Token has expired and can no longer be refreshed";
            return $this->tokenExpirationException($responseMessage);
        }
    }

}
