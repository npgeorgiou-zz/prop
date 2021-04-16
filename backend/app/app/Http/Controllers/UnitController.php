<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Prop\Core\Errors\Conflict;
use Prop\Core\Errors\ModelExists;
use Prop\Core\Errors\ModelNotFound;
use Prop\Core\Errors\Unauthorized;
use Prop\Services\Mailman\Mailman;
use function Prop\Core\Cases\assign_owner;
use function Prop\Core\Cases\create_unit;
class UnitController extends Controller {

    function create(Request $request) {
        $token = $request->header('token');

        try {
            $unit = create_unit($token, (object)[
                'address' => $request->input('address'),
            ]);

            return response()->json($unit);
        } catch (Unauthorized) {
            return response('Cant do that', Response::HTTP_UNAUTHORIZED);
        } catch (ModelExists) {
            return response('Unit already exists', Response::HTTP_CONFLICT);
        }
    }

    function assign_owner(Request $request, Mailman $mailman) {
        $token = $request->header('token');

        try {
            $unit = assign_owner(
                $token,
                $request->input('unit_id'),
                $request->input('email'),
                $mailman
            );

            return response()->json($unit);
        } catch (Unauthorized) {
            return response('Cant do that', Response::HTTP_UNAUTHORIZED);
        } catch (ModelNotFound) {
            return response('Unit doesnt exist', Response::HTTP_CONFLICT);
        } catch (Conflict) {
            return response('User already owns this unit', Response::HTTP_CONFLICT);
        }
    }
}
