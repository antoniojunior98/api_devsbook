<?php

namespace App\Http\Controllers;

use App\Post;
use App\PostComment;
use App\PostLike;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;

class PostController extends Controller
{
    private $loggedUser;

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->loggedUser = auth()->user();
    }

    public function like($id)
    {
        $response = ['error' => ''];

        $postExists = Post::find($id);

        if ($postExists) {
            $id_user = $this->loggedUser['id'];
            $isLiked = PostLike::where("id_post", $id)
                ->where("id_user", $id_user)
                ->count();
            if ($isLiked > 0) {
                $like = PostLike::where("id_post", $id)
                    ->where("id_user", $id_user)
                    ->first();
                $like->delete();

                $isLiked = false;
            } else {
                $like = new PostLike();
                $like->id_post = $id;
                $like->id_user = $id_user;
                $like->save();

                $isLiked = true;
            }
            $likeCount = PostLike::where("id_post", $id)
                ->count();
            $response['likeCount'] = $likeCount;
        } else {
            $response['error'] = "Post inexistente!";

        }
        return $response;
    }

    public function comment(Request $request, $id)
    {
        $response = ['error' => ''];

        $post = Post::find($id);

        if(!$post){
            $response['error'] = "Post inexistente!";
            response()->json($response);
        }
        $data = $request->only($request, ['text']);

        $validate = Validator::make($data,[
            'text'=>['required','string']
        ]);

        if($validate->fails()){
            $errors = $validate->errors();
            $response['error'] = $errors->all();
        } else{
            $comment = new PostComment();
            $comment->id_post = $id;
            $comment->id_user = $this->loggedUser['id'];
            $comment->body = $data['text'];
            $comment->save();
        }
        response()->json($response);
    }
}
