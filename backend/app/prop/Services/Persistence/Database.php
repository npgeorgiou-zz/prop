<?php

namespace Prop\Services\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Database {
    function create_password_reset_token($email): string {
        $token = uniqid();

        DB::table('password_resets')->insert([
            'email' => $email,
            'token' => $token,
            'created_at' => new \DateTime()
        ]);

        return $token;
    }

    function find_password_reset_token($token) {
        return DB::table('password_resets')->where('token', $token)->first();
    }

    function delete_password_reset_token($token) {
        DB::table('password_resets')->where('token', $token)->delete();
    }

//    function createUser(User $user): User {
//        $orm_user = $this->domainToOrm($user);
//
//        DB::beginTransaction();
//
//        try {
//            $orm_user->save();
//
//            foreach ($user->roles as $role) {
//                $orm_user->roles()->attach(\App\Models\Role::where('name', Role::ROLE_ADMIN)->firstOrFail());
//            }
//
//            $orm_user->save();
//
//            DB::commit();
//        } catch (\Exception $e) {
//            DB::rollBack();
//
//            throw $e;
//        }
//
//        return $this->ormToDomain($orm_user);
//    }
//
//    function updateUser(User $user): User {
//        $orm_user = \App\Models\User::find($user->id);
//        $orm_user = $this->domainToOrm($user, $orm_user);
//        $orm_user->save();
//
//        return $this->ormToDomain($orm_user);
//    }
//
//    function create_association(Association $association): User {
//        $orm_association = $this->domainToOrm($association);
//
//        $orm_association->save();
//
//        return $this->ormToDomain($orm_association);
//    }
//
//    function findUser(string|array $idOrValues): ?User {
//        $query = \App\Models\User::query();
//
//        if (is_string($idOrValues)) {
//            $query->find($idOrValues);
//        } else {
//            foreach ($idOrValues as $name => $value) {
//                $query->where($name, $value);
//            }
//        }
//
//        return $this->ormToDomain($query->first());
//    }
//
//    function domainToOrm($domainModel, $orm_model = null): Model {
//        if ($domainModel instanceof Role) {
//            $orm_role = $orm_model ?? new \App\Models\Role();
//            $orm_role->name = $domainModel->name;
//            return $orm_role;
//        }
//
//        if ($domainModel instanceof User) {
//            $orm_user = $orm_model ?? new \App\Models\User();
//            $orm_user->first_name = $domainModel->first_name;
//            $orm_user->last_name = $domainModel->last_name;
//            $orm_user->email = $domainModel->email;
//            $orm_user->password = $domainModel->password;
//            $orm_user->remember_token = $domainModel->remember_token;
//
//            return $orm_user;
//        }
//
//        if ($domainModel instanceof Association) {
//            $orm_association = $orm_model ?? new \App\Models\Association();
//            $orm_association->name = $domainModel->name;
//            $orm_association->address = $domainModel->address;
//
//            return $orm_association;
//        }
//
//        if ($domainModel instanceof Unit) {
//            $orm_unit = $orm_model ?? new \App\Models\Unit();
//            $orm_unit->address = $domainModel->address;
//
//            return $orm_unit;
//        }
//    }
//
//    function ormToDomain(?Model $orm_model) {
//        if (!$orm_model) {
//            return null;
//        }
//
//        if ($orm_model instanceof \App\Models\Role) {
//            $role = new Role($orm_model->name);
//            return $role;
//        }
//
//        if ($orm_model instanceof \App\Models\User) {
//            $user = new User(
//                $orm_model->first_name,
//                $orm_model->last_name,
//                $orm_model->email,
//                $orm_model->password,
//                $orm_model->remember_token,
//
//                $orm_model->roles->map(fn($it) => $this->ormToDomain($it))->toArray()
//            );
//
//            $user->id = $orm_model->id;
//
//            return $user;
//        }
//
//        if ($orm_model instanceof \App\Models\Association) {
//            $association = new Association($orm_model->name, $orm_model->address);
//            return $association;
//        }
//
//        if ($orm_model instanceof \App\Models\Unit) {
//            $association = new Unit($orm_model->address);
//            return $association;
//        }
//    }
}
