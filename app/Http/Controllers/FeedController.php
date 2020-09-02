<?php

namespace App\Http\Controllers;

use App\Post;
use App\PostLike;
use App\User;
use App\UserReletion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class FeedController extends Controller
{
    private $loggedUser;

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->loggedUser = auth()->user();
    }

    public function create(Request $request)
    {
        $response = ['error' => ''];

        $data = $request->only([
            'type',
            'body',
            'photo',
        ]);

        $validate = Validator::make($data, [
            'type' => 'required|string|max:10',
            'body' => 'string',
            'photo' => 'file',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors();
            $response['error'] = $errors->all();
            return $response;
        }

        $type = $data['type'];

        switch ($type) {
            case 'body':
                if (!$data['body']) {
                    $response['error'] = "Digite alguma coisa que você está pensando!";
                    return $response;
                }
                $body = $data['body'];
                break;
            case 'photo':

                break;
            default:
                $response['error'] = "Tipo de postagem inexistente!";
                return $response;
                break;
        }

        $post = new Post();
        $post->type = $type;
        $post->id_user = $this->loggedUser['id'];
        $post->body = $body;
        if ($post->save()) {
            $response['success'] = "Postagem realizada com sucesso!";
        } else {
            $response['error'] = "Não foi possivel realizar a postagem. Por favor tente novamente mais tarde!";
        }
        return $response;
    }

    public function read(Request $request)
    {
        $response = ['error' => ''];

        $page = intval($request->input('page'));
        $perPage = 2;

        $users = [];
        $id_user = $this->loggedUser['id'];

        $userList = UserReletion::where("user_from", $id_user);
        foreach ($userList as $item) {
            $users[] = $item['user_to'];
        }
        $users[] = $id_user;

        $postList = Post::whereIn("id_user", $users)->orderBy("created_at", "desc")
            ->offset($page * $perPage)
            ->limit($perPage)
            ->get();

        $postCount = POST::whereIn("id_user", $users)->count();
        $pageCount = ceil($postCount / $perPage);

        $posts = $this->_postListToObject($postList, $id_user);

        $response['posts'] = $posts;
        $response['pageCount'] = $pageCount;
        $response['currentPage'] = $page;

        return $response;
    }

    public function feedUser(Request $request, $id = false){
        $response = ['error' => ''];

        if($id == false){
            $id = $this->loggedUser['id'];
        }

        $page = intval($request->input('page'));
        $perPage = 2;

        $posts = Post::where("id_user", $id)->orderBy("created_at", "desc")
            ->offset($page * $perPage)
            ->limit($perPage)
            ->get();
        $postCount = Post::where("id_user", $id)->count();
        $pageCount = ceil($postCount / $perPage);

        $posts = $this->_postListToObject($posts, $id);

        $response['posts'] = $posts;
        $response['pageCount'] = $pageCount;
        $response['currentPage'] = $page;

        return $response;
    }

    private function _postListToObject($postList, $id_user)
    {
        foreach ($postList as $postKey => $post) {

            if ($post['id_user'] == $id_user) {
                $posts[$postKey]['mine'] = true;
            } else {
                $posts[$postKey]['mine'] = false;
            }

            $userInfo = User::find($post['id_user']);
            $userInfo['avatar'] = url('storage/avatar/' . $userInfo['avatar']);
            $userInfo['cover'] = url('storage/cover/' . $userInfo['cover']);
            $posts[$postKey]['users'] = $userInfo;

            $likes = PostLike::where("id_post", $post['id'])->count();
            $posts[$postKey]['likes'] = $likes;

            $isLiked = PostLike::where("id_post", $post['id'])
                ->where("id_user", $id_user)
                ->count();

            $posts[$postKey]['liked'] = ($isLiked) ? true : false;

            $comments = $post['comments'];

            foreach ($comments as $commentKey => $coment) {
                $user = User::find($post['id_user']);
                $user['avatar'] = url('storage/avatar/' . $user['avatar']);
                $user['cover'] = url('storage/cover/' . $user['cover']);
                $comments[$commentKey]['users'] = $user;
            }
            $posts[$postKey]['comments'] = $comments;
        }
        return $posts;
    }
}
