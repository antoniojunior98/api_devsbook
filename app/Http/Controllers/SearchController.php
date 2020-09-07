<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SearchController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->loggedUser = auth()->user();
    }

    public function search(Request $request)
    {
        $response = ['error' => '', 'users' => []];

        $search = filter_var($request['search'], FILTER_SANITIZE_STRING);
        
        if (empty($search)) {
            $response['error'] = "Digite alguma coisa para pesquisar.";
            return response()->json($response);
        }

        $users = User::where("name", "like", "%{$search}%")->get();
        foreach($users as $user){
            $response['users'][] = [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => storage_path("avatar{$user->avatar}")
            ];
        }

        return response()->json($response);
    }

}
