<?php

namespace Prop\Core\Cases;

use App\Models\Association;
use App\Models\Invitation;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Prop\Core\Errors\Conflict;
use Prop\Core\Errors\ModelExists;
use Prop\Core\Errors\ModelNotFound;
use Prop\Core\Errors\Unauthorized;
use Prop\Services\Mailman\Mailman;
use Prop\Services\Persistence\Database;
use Prop\Services\Persistence\Persistence;
use function PHPUnit\Framework\assertEquals;

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

function create_unit($token, $values): Unit {
    $user = User::where('remember_token', $token)->first();

    if (!$token || !$user) {
        throw new Unauthorized();
    }

    if (!$user->is_admin) {
        throw new Unauthorized();
    }

    $unit = Unit::where('address', $values->address)->first();
    if ($unit) {
        throw new ModelExists();
    }

    $association = $user->managedAssociations->first();

    $unit = new Unit();
    $unit->address = $values->address;
    $unit->address = $values->address;
    $unit->association()->associate($association);
    $unit->save();

    return $unit;
}

function assign_owner($token, $unit_id, $email, Mailman $mailman): Unit {
    $user = User::where('remember_token', $token)->first();

    if (!$token || !$user) {
        throw new Unauthorized();
    }

    if (!$user->is_admin) {
        throw new Unauthorized();
    }

    $unit = Unit::find($unit_id);
    if (!$unit) {
        throw new ModelNotFound();
    }

    $user = User::where('email', $email)->first();

    if (!$user) {
        // TODO: multiple owners
        $user = new User();
        $user->first_name = 'autocreated';
        $user->last_name = 'user';
        $user->email = $email;
        $user->password = '';
        $user->is_backoffice = false;
        $user->is_admin = false;
        $user->save();
    }

    if ($user->units->contains($unit)) {
        throw new Conflict();
    }

    if ($user->first_name === 'autocreated') {
        $invitation = new Invitation();
        $invitation->token = (new Database)->unique_value_for(Invitation::class, 'token');
        $invitation->user()->associate($user);
        $invitation->save();

        $mailman->send(
            $email,
            'emails.invite_owner',
            'Come join',
            ['unit' => $unit, 'link' => "foo/$token"]
        );
    } else {
        $mailman->send(
            $email,
            'emails.unit_assigned',
            $unit->address . ' was assigned to you',
            ['unit' => $unit, 'link' => "foo/$token"]
        );
    }

    $unit->owners()->attach($user);
    $unit->save();

    return $unit;
}

function accept_invitation($token, $values, Mailman $mailman): User {
    $invitation = Invitation::where('token', $token)->first();

    if (!$invitation) {
        throw new ModelNotFound();
    }

    $user = $invitation->user;
    $user->first_name = $values->first_name;
    $user->last_name = $values->last_name;
    $user->password = $values->password;
    $user->is_backoffice = false;
    $user->is_admin = false;
    $user->save();

    $mailman->send(
        $user->email,
        'emails.welcome_owner',
        'Welcome',
        []
    );

    $invitations = Invitation::where('user_id', $user->id);
    $invitations->delete();

    return $user;
}
