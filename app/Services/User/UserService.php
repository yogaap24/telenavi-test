<?php

namespace App\Services\User;

use App\Models\Entity\User;
use App\Services\AppService;
use App\Services\AppServiceInterface;
use Illuminate\Support\Facades\Hash;

class UserService extends AppService implements AppServiceInterface
{

    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function dataTable($filter)
    {
        return User::datatable($filter)->paginate($filter->entries ?? 15);
    }

    public function getById($id)
    {
        return User::findOrFail($id);
    }

    public function create($data)
    {
        return User::create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => Hash::make($data->password),
        ]);
    }

    public function update($id, $data)
    {
        $user = User::findOrFail($id);
        $user->update([
            'name' => $data->name,
            'email' => $data->email,
        ]);
        return $user;
    }

    public function delete($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return $user;
    }
}