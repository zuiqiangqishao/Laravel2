<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\TopicRequest;
use Dingo\Api\Http\Request;
use App\Models\Topic;
use App\Transformers\TopicTransformer;
use Dingo\Api\Auth\Auth;
use App\Models\User;
class TopicsController extends Controller
{
    public $pageSize = 20;
    public function __construct()
    {
        $this->pageSize = config('myconfig.page.api.topic');
    }

    //获取话题列表
    public function index(Request $request)
    {
        $order = $request->order;
        $request = $request->only(['user_id', 'category_id','created_at', 'updated_at','excerpt','order']);
        $where = [];
        foreach ($request as $key=>$item) {
            if(empty($item))continue;
            switch ($key) {
                case 'created_at':
                    $where[] = [$key, '>=', $item];
                case 'updated_at':
                    $where[] = [$key, '<=', $item];
                default:
                    $where[] = [$key, '=', $item];
            }
        }
        $topics = Topic::where($where)->withOrder($order)->paginate($this->pageSize);
        return $this->response->paginator($topics, new TopicTransformer());

    }
    //获取用户发表的所有话题
    public function userIndex(User $user)
    {
        $topics = $user->topics()->recent()->paginate($this->pageSize);
        return $this->response->paginator($topics, new TopicTransformer());
    }

    //话题详情接口
    public function show(Topic $topic)
    {
        return $this->response->item($topic, new TopicTransformer());
    }

    //发表话题接口
    public function store(TopicRequest $request, Topic $topic)
    {
        $topic->fill($request->all());
        $topic->user_id = $this->user()->id;
        $topic->save();
        return $this->response->item($topic, new TopicTransformer())->setStatusCode(201);
    }

    //修改话题
    public function update(TopicRequest $request, Topic $topic)
    {
        $this->authorize('update', $topic);
        $topic->update($request->all());
        return $this->response->item($topic, new TopicTransformer());
    }

    //删除话题
    public function destroy(Topic $topic, Request $request)
    {
        $this->authorize('destroy', $topic);
        $topic->delete();
        return $this->response->noContent();
    }



}
