<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\Auth\AuthService;
use Exception;
use Illuminate\Http\Request;

class AuthController extends ApiController
{
    protected AuthService $service;

    /**
     * @param AuthService $service
     * @param Request $request
     */
    public function __construct(AuthService $service, Request $request)
    {
        $this->service = $service;
        parent::__construct($request);
    }

    /**
     * Login user with username and password.
     *
     * @param LoginRequest $request
     * @return object
     */
    public function login(LoginRequest $request)
    {
        try {
            $user = $this->service->login($request);
            return $this->sendSuccess($user, null, 200);
        } catch (Exception $e) {
            return $this->sendError(null, $e->getMessage(), $e->getCode());
        }
    }

    /**
     * Register new user.
     *
     * @param RegisterRequest $request
     * @return object
     */
    public function register(RegisterRequest $request)
    {
        $user = $this->service->register($request);
        return $this->sendSuccess($user, null, 200);
    }

    /**
     * Get authenticated user.
     *
     * @return object
     */
    public function profile()
    {
        $user = $this->service->profile();
        return $this->sendSuccess($user, null, 200);
    }

    /**
     * Logout user.
     *
     * @return object
     */
    public function logout() {
        $user = $this->service->logout();
        return $this->sendSuccess($user, null, 200);
    }
}