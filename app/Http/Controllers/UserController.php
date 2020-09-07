<?php

namespace App\Http\Controllers;

use App\Post;
use App\User;
use App\UserRelation;
use DateTime;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Image;

class UserController extends Controller
{
    private $loggedUser;

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->loggedUser = auth()->user();
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
                $path = storage_path('avatar');

                $img = Image::make($image->path())
                    ->fit(200, 200)
                    ->save($path . '/' . $filename);

                $user = User::Find($this->loggedUser['id']);
                $user->avatar = $filename;
                $user->save();

                $response['url'] = $filename;
            } else {
                $response['error'] = "Arquivo não suportado!";
            }
            return $response;
        } else {
            $response['error'] = "Arquivo não enviado!";
        }

        return $response;
    }

    public function updateCover(Request $request)
    {
        $response = ['error' => ''];
        $allowedTypes = ['image/jpg', 'image/jpeg', 'image/png'];

        $image = $request->file('cover');
        if ($image) {
            if (in_array($image->getClientMimeType(), $allowedTypes)) {

                $filename = md5(time(), rand(0, 9999)) . ".jpg";
                $path = url('storage/cover/');

                $img = Image::make($image->path())
                    ->fit(850, 310)
                    ->save($path . $filename);

                $user = User::Find($this->loggedUser['id']);
                $user->cover = $filename;
                $user->save();

                $response['url'] = $filename;
            } else {
                $response['error'] = "Arquivo não suportado!";
            }
            return $response;
        } else {
            $response['error'] = "Arquivo não enviado!";
        }

        return $response;
    }

    public function read($id = false)
    {
        $response = ['error' => ''];

        $id = (filter_var($id, FILTER_VALIDATE_INT)) ? $id : $this->loggedUser['id'];
        $user = User::find($id);

        if (!$user) {
            $response['error'] = "Usuário inexistente!";
            return $response;
        }

        $user['me'] = ($id == $this->loggedUser['id']) ? true : false;
        $user['avatar'] = url("storage/avatar/{$user['avatar']}");
        $user['cover'] = url("storage/cover/{$user['cover']}");

        $dateFrom = new \DateTime($user['birthdate']);
        $dateTo = new \DateTime('today');
        $user['age'] = $dateFrom->diff($dateTo)->y;

        $user['followers'] = UserRelation::where("user_to", $user['id'])->count();
        $user['following'] = UserRelation::where("user_from", $user['id'])->count();

        $user['photoCount'] = Post::where("id_user", $user['id'])
            ->where("type", "photo")
            ->count();

        $hasRelation = UserRelation::where("user_from", $this->loggedUser['id'])
            ->where('user_to', $user['id'])
            ->count();
        $user['isFollowing'] = ($hasRelation > 0) ? true : false;

        $response['data'] = $user;
        return $response;
    }

    public function follow($id)
    {
        $response = ['error' => ''];

        $id_user = $this->loggedUser['id'];
        if ($id == $id_user) {
            $response['error'] = "Não é possivel seguir você mesmo!";
            return response()->json($response);
        }

        $user = User::find($id);
        if (!$user) {
            $response['error'] = "Usuário inexistente!";
            return response()->json($response);
        }

        $relation = UserRelation::where('user_from', $id_user)
            ->where('user_to', $id)
            ->first();
            
            if($relation){
                $relation->delete();
            } else{
                $newRelation = new UserRelation();
                $newRelation->user_from = $id_user;
                $newRelation->user_to = $id;
                $newRelation->save();
            }
        return response()->json($response);
    }
    
    public function followers($id){
        $response = ['error' => ''];

        $user = User::find($id);
        if(!$user){
            $response['error'] = "Usuário inexistente!";
            return response()->json($response);
        }

        $followers = UserRelation::where("user_to", $id)->get();
        $following = UserRelation::where("user_from", $id)->get();
        $response['followers'] = [];
        $response['following'] = [];

        foreach($followers as $follower){
            $user = User::find($follower->user_from);
            $response['followers'][] = [
                "id" => $user->id,
                "name" => $user->name,
                "avatar" => storage_path("avatar/{$user->avatar}")
            ];
        }
        foreach($following as $f){
            $user = User::find($f->user_to);
            $response['following'][] = [
                "id" => $user->id,
                "name" => $user->name,
                "avatar" => storage_path("avatar/{$user->avatar}")
            ];
        }

        return response()->json($response);
    }
}
