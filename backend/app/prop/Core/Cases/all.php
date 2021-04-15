<?php

namespace Prop\Core\Cases;

use App\Models\Association;
use App\Models\User;
use Prop\Core\Errors\ModelExists;
use Prop\Core\Errors\ModelNotFound;
use Prop\Core\Errors\Unauthorized;
use Prop\Services\Mailman\Mailman;
use Prop\Services\Persistence\Database;
use Prop\Services\Persistence\Persistence;

// TODO: Use eloquent instead of repo.
function createAdmin($values, Mailman $mailman): User {
    $user = User::where('email', $values->email)->first();
    if ($user) {
        throw new ModelExists();
    }

    $user = new User();
    $user->first_name = $values->first_name;
    $user->last_name = $values->last_name;
    $user->email = $values->email;
    $user->password = $values->password;
    $user->is_backoffice = false;
    $user->is_admin = true;
    $user->save();

    // Send email
    $mailman->send(
        $user->email,
        'emails.welcome_admin',
        'Welcome',
        ['user' => $user]
    );

    return $user;
}

function login($email, $password): User {
    // TODO Password encryption at creation
    $user = User
        ::where('email', $email)
        ->where('password', $password)
        ->first();

    if (!$user) {
        throw new ModelNotFound();
    }

    do {
        $token = uniqid();
    } while (User::where('remember_token', $token)->first());

    $user->remember_token = $token;
    $user->save();

    return $user;
}

function forgotPassword($email, Mailman $mailman) {
    $user = User::where('email', $email)->first();

    if (!$user) {
        return;
    }

    $token = (new Database())->create_password_reset_token($email);
    $link = "foo/$token";

    $mailman->send(
        $user->email,
        'emails.reset_password',
        'Welcome',
        ['user' => $user, 'link' => $link]
    );
}

function resetPassword($password, $token) {
    $token = (new Database())->find_password_reset_token($token);

    if (!$token) {
        throw new ModelNotFound();
    }

    $email = $token->email;

    $user = User::where('email', $email)->first();
    $user->password = $password;
    $user->save();

    (new Database())->delete_password_reset_token($token->token);
}

function create_association($token, $values): Association {
    $user = User::where('remember_token', $token)->first();

    if (!$token || !$user) {
        throw new Unauthorized();
    }

    if (!$user->is_admin) {
        throw new Unauthorized();
    }

    var_dump($user->managedAssociations);
    if ($user->managedAssociations->isNotEmpty()) {
        throw new ModelExists();
    }

    $association = new Association();
    $association->name = $values->name;
    $association->address = $values->address;
    $association->save();

    $association->admins()->attach($user);
    $association->save();

    return $association;
}
