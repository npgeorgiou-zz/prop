<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Prop\Core\Errors\ModelExists;
use Prop\Core\Errors\ModelNotFound;
use Prop\Services\Mailman\Mailman;
use Prop\Services\Persistence\Database;
use function Prop\Core\Cases\accept_invitation;
use function Prop\Core\Cases\createAdmin;
use function Prop\Core\Cases\forgotPassword;
use function Prop\Core\Cases\login;
use function Prop\Core\Cases\resetPassword;

class UserController extends Controller {

    public function create(Request $request, Mailman $mailman) {
        try {
            $user = createAdmin((object)[
                'first_name' => $request->input('first_name'),
                'last_name' => $request->input('last_name'),
                'email' => $request->input('email'),
                'password' => $request->input('password'),
            ], $mailman);

            return response()->json($user);
        } catch (ModelExists) {
            return response('Email exists', Response::HTTP_CONFLICT);
        }
    }

    public function test(Request $request, Mailman $mailman) {
        try {
            $user = createAdmin((object)[
                'first_name' => 'nikos',
                'last_name' => 'pap',
                'email' => 'nikodfdfs@pap.com',
                'password' => 'password',
            ], $mailman);

            return response()->json($user);
        } catch (ModelExists) {
            return response('Email exists', Response::HTTP_CONFLICT);
        }

    }

    public function login(Request $request) {
        try {
            $user = login($request->email, $request->password);

            // Add token.
            $token = $user->remember_token;
            $user = json_decode($user->toJson());
            $user->token = $token;
            return response()->json($user);
        } catch (ModelNotFound) {
            return response('Wrong credentials', Response::HTTP_UNAUTHORIZED);
        }
    }

    public function forgot_password(Request $request, Mailman $mailman) {
        forgotPassword($request->email, $mailman);
        return response()->json();
    }

    public function reset_password(Request $request, Mailman $mailman) {
        try {
            resetPassword($request->password, $request->token);
            return response('OK', Response::HTTP_OK);
        } catch (ModelNotFound) {
            return response('token already used', Response::HTTP_CONFLICT);
        }
    }

    public function accept_invitation(Request $request, Mailman $mailman) {
        try {
            $user = accept_invitation($request->input('token'), (object)[
                'first_name' => $request->input('first_name'),
                'last_name' => $request->input('last_name'),
                'password' => $request->input('password'),
            ], $mailman);

            return response()->json($user);
        } catch (ModelNotFound) {
            return response('token already used', Response::HTTP_CONFLICT);
        }
    }
}
