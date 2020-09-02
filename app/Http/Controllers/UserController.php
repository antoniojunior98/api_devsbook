<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Image;

class UserController extends Controller
{
    private $loggedUser;

    public function __construct()
    {
        // $this->middleware('auth:api');
        // $this->loggedUser = auth()->user();
    }

    public function read()
    {
        $response = ['error' => ''];

        $users = User::all();
        if ($users) {
            $response['users'] = $users;
        }

        return $response;
    }

    public function update(Request $request)
    {
        $response = ['error' => ''];

        $data = $request->only($request, [
            'name',
            'email',
            'password',
            'password_confirm',
            'birthdate',
            'city',
            'work',
        ]);

        $validate = Validator::make($data, [
            "name" => ['string'],
            "email" => ['email'],
            "password" => ['string', 'max:20', 'confirmed'],
            "birthdate" => ['date'],
            'city' => ['string', 'max:100'],
            'work' => ['string', 'max:100'],
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors();
            $response['error'] = $errors->all();
            return $response;
        }

        $user = User::find($this->loggedUser['id']);

        if ($data['email']) {
            $user->email = $data['email'];
            array_diff($data, $data["email"]);
        }

        if ($data['password']) {
            $user->password = password_hash($data['password'], PASSWORD_DEFAULT);
            array_diff($data, $data["password"]);
        }

        foreach ($data as $fields => $values) {
            if ($fields) {
                $user->$fields = $values;
            }
        }

        if ($user->save()) {
            $response['success'] = "Usuário atualizado com sucesso!";
        } else {
            $response['error'] = "Não foi possivel realizar a atualização. Por favor tente novamente mais tarde!";
        }
        return $response;
    }

    public function updateAvatar(Request $request)
    {
        $response = ['error' => ''];
        $allowedTypes = ['image/jpg', 'image/jpeg', 'image/png'];

        $image = $request->file('avatar');
        if ($image) {
            if (in_array($image->getClientMimeType(), $allowedTypes)) {

                $filename = md5(time(), rand(0, 9999)) . ".jpg";
                $path = url('storage/avatar/');

                $img = Image::make($image->path())
                    ->fit(200, 200)
                    ->save($path.$filename);

                $user = User::Find($this->loggedUser['id']);
                $user->avatar = $filename;
                $user->save();

                $response['url'] = $filename;
            } else{
                $response['error'] = "Arquivo não suportado!";
            }
            return $response;
        } else {
            $response['error'] = "Arquivo não enviado!";
        }

        return $response;
    }

    public function updateCover(Request $request){
        $response = ['error' => ''];
        $allowedTypes = ['image/jpg', 'image/jpeg', 'image/png'];

        $image = $request->file('cover');
        if ($image) {
            if (in_array($image->getClientMimeType(), $allowedTypes)) {

                $filename = md5(time(), rand(0, 9999)) . ".jpg";
                $path = url('storage/cover/');

                $img = Image::make($image->path())
                    ->fit(850, 310)
                    ->save($path.$filename);

                $user = User::Find($this->loggedUser['id']);
                $user->cover = $filename;
                $user->save();

                $response['url'] = $filename;
            } else{
                $response['error'] = "Arquivo não suportado!";
            }
            return $response;
        } else {
            $response['error'] = "Arquivo não enviado!";
        }

        return $response;
    }

    public function read($id = false){
        $response = ['error'=>''];

        
    }   
}
