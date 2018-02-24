<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Http\Services\{UserService, LogService};
use Illuminate\Http\{Request, JsonResponse, Response};
use Illuminate\Support\Facades\{DB, Validator};
use App\Http\Resources\User as UserResource;

class UserController extends Controller
{
    /** @var UserService $userService */
    private $userService;
    /** @var LogService $logService */
    private $logService;

    /**
     * UserController constructor.
     * @param UserService $userService
     * @param LogService $logService
     */
    public function __construct(UserService $userService, LogService $logService)
    {
        $this->userService = $userService;
        $this->logService = $logService;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        /* Validation */
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['data' => $validator->messages()], Response::HTTP_CONFLICT);
        }

        /* Create User and Log Action */
        $user = DB::transaction(function () use ($request) {
            $user = $this->userService->create($request->name, $request->password);

            $this->logService->log($user->id, Log::REGISTER, $request);

            return $user;
        });

        return UserResource::make($user)->response();
    }
}