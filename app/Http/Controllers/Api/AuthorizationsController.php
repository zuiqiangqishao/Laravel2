<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\AuthorizationRequest;
use App\Http\Requests\Api\SocialAuthorizationRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
class AuthorizationsController extends Controller
{
    //这方法是用户通过第三方登录的时候获取用户信息并注册用户
    public function socialStore($type, SocialAuthorizationRequest $request)
    {
        if (!in_array($type, ['weixin'])) {
            return $this->response->errorBadRequest();
        }

        $drive = Socialite::driver($type);

        //客户端可以直接传code，让服务端请求accesstoken和openid再请求userinfo
        //客户端也可以自己拿code去请求，然后返回服务端accesstoken和openid
        try {
            if ($code = $request->code) {
                //如果传过来的是code
                $resopnse = $drive->getAccessTokenResponse($code);
                $token = array_get($resopnse, 'access_token');
            } else {
                //如果直接传的token
                $token = $request->access_token;

                if($type == 'weixin') {
                    $drive->setOpenId($request->openid);
                }
            }

            $oauth_user = $drive->userFromToken($token);

        } catch (\Exception $e) {
            return $this->response->errorUnauthorized('参数错误，未获取用户信息');
        }

        switch ($type) {
            case 'weixin':
                $unionid = $oauth_user->offsetExists('unionid') ? $oauth_user->offsetGet('unionid') : null;

                if ($unionid) {
                    $user = User::where('weixin_unionid', $unionid)->first();
                } else {
                    $user = User::where('weixin_openid', $oauth_user->getId())->first();
                }

                //如果没有用户，就创建一个
                if(!$user) {
                    $user = User::create([
                        'name'=>$oauth_user->getNickname(),
                        'avatar'=>$oauth_user->getAvatar(),
                        'weixin_openid'=>$oauth_user->getId(),
                        'weixin_unionid'=>$unionid,
                    ]);
                }

                break;

        }
        $apitoken = Auth::guard('api')->fromUser($user);
        return $this->respondWithToken($apitoken)->setStatusCode(201);
    }

    //直接通过邮箱或手机号+密码登录
    public function store(AuthorizationRequest $request)
    {
        $username = $request->username;

        filter_var($username, FILTER_VALIDATE_EMAIL) ?
            $credentials['email'] = $username
            :
            $credentials['phone'] = $username
        ;
        $credentials['password'] = $request->password;

        if (!$apitoken = Auth::guard('api')->attempt($credentials)) {
           return $this->response->errorUnauthorized('用户名或密码错误');
        }

        return $this->respondWithToken($apitoken)->setStatusCode(201);

    }

    //刷新token
    public function update()
    {
        $token = Auth::guard('api')->refresh();
        return $this->respondWithToken($token);
    }

    //删除token
    public function destroy()
    {
        Auth::guard('api')->logout();
        return $this->response->noContent();
    }

    protected function respondWithToken($apitoken)
    {
        return $this->response->array([
            'access_token' => $apitoken,
            'token_type' => 'Bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60
        ]);
    }
}