<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Prop\Core\Errors\ModelExists;
use Prop\Core\Errors\ModelNotFound;
use Prop\Core\Errors\Unauthorized;
use Prop\Services\Mailman\Mailman;
use Prop\Services\Persistence\Database;
use function Prop\Core\Cases\createAdmin;
use function Prop\Core\Cases\create_association;
use function Prop\Core\Cases\forgotPassword;
use function Prop\Core\Cases\login;
use function Prop\Core\Cases\resetPassword;

class AssociationController extends Controller {

    public function create(Request $request) {
        $token = $request->header('token');

        try {
            $association = create_association($token, (object)[
                'name' => $request->input('name'),
                'address' => $request->input('address'),
            ]);

            return response()->json($association);
        } catch (Unauthorized) {
            return response('Cant do that', Response::HTTP_UNAUTHORIZED);
        } catch (ModelExists) {
            return response('You already manage an association', Response::HTTP_CONFLICT);
        }
    }
}
