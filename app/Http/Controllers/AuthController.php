<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\User;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', [
            'except'=>[
                'login',
                'create',
                'unauthorized'
            ]
        ]);
    }
    
    public function unauthorized(){
        return response()->json(['error'=>'Não autorizado', 401]);
    }
    public function login(Request $request){
        $response = ['error'=>''];

        $data = $request->only([
            'email',
            'password',
        ]);

        $validate = Validator::make($data, [
            'email' => ['required', 'email'],
            'password' => ['required', 'string']
        ]);

        if($validate->fails()){
            $errors = $validate->errors();
            $response['error'] = $errors->all();
            return $response;
        }

        $token = Auth::attempt($data);

        if(!$token){
            $response['error'] = "E-mail e/ou senha errados!";
            return $response;
        }

        $response['token'] = $token;
        return $response;
    }

    public function logout(){
        Auth::logout();
        return ['error'=>''];
    }

    public function refresh(){
        $token = Auth::refresh();
        return [
            'error'=>'',
            'token'=>$token
        ];
    }

    public function create(Request $request){
        $response = ['error'=>''];
        $data = $request->only([
            'name',
            'email',
            'password',
            'birthdate',
            'city',
            'work',
        ]);

        $validate = Validator::make($data, [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users','max:200'],
            'password' => ['required', 'string', 'max:20'],
            'birthdate' => ['required', 'date'],
            'city' => ['string', 'max:100'],
            'work' => ['string', 'max:100'],
        ]);

        if($validate->fails()){
            $errors = $validate->errors();
            $response['error'] = $errors->all();
            return $response;
        }

        $user = new User();        
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->password = password_hash($data['password'], PASSWORD_DEFAULT);
        $user->birthdate = $data['birthdate'];
        $user->city = $data['city'];
        $user->work = $data['work'];
        if($user->save()){
            $response['success'] = "Cadastrado com sucesso!";
        } else{
            $response['error'] = "Não foi possivel realizar o cadastro. Por favor tente novamente mais tarde!";
        }
        
        return $response;
    }
}
