<?php
namespace Prop\Services\Persistence;

use Prop\Core\Models\User;

interface Persistence {
    function create_password_reset_token($email): string;
    function find_password_reset_token($token);
    function delete_password_reset_token($token);

    function createUser(User $user): User;
    function updateUser(User $user): User;
    function findUser(string|array $idOrValues): ?User;
}
