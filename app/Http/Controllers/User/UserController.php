<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\ApiController;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Services\User\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends ApiController
{
    protected UserService $service;

    /**
     * @param UserService $service
     * @param Request $request
     */
    public function __construct(UserService $service, Request $request)
    {
        $this->service = $service;
        parent::__construct($request);
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $users = $this->service->dataTable($request);
        return $this->sendSuccess($users, null, 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreUserRequest $request
     * @return JsonResponse
     */
    public function store(StoreUserRequest $request)
    {
        $user = $this->service->create($request);
        return $this->sendSuccess($user, null, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param String $id
     * @return JsonResponse
     */
    public function show(string $id)
    {
        $feature = $this->service->getById($id);
        return $this->sendSuccess($feature, null, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateUserRequest $request
     * @param String $id
     * @return JsonResponse
     */
    public function update(UpdateUserRequest $request, string $id)
    {
        $user = $this->service->update($id, $request);
        return $this->sendSuccess($user, null, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param String $id
     * @return JsonResponse
     */
    public function destroy(string $id)
    {
        $user = $this->service->delete($id);
        return $this->sendSuccess($user, null, 200);
    }
}