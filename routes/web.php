<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', 'TopicsController@index')->name('root');
/**************************************************************/
//Auth::routes(); //等同于下面的这些路由，为了方便观看，替换为具体路由

// Authentication Routes...
Route::get('login', 'Auth\LoginController@showLoginForm')->name('login');
Route::post('login', 'Auth\LoginController@login');
Route::post('logout', 'Auth\LoginController@logout')->name('logout');

// Registration Routes...
Route::get('register', 'Auth\RegisterController@showRegistrationForm')->name('register');
Route::post('register', 'Auth\RegisterController@register');

// Password Reset Routes...
Route::get('password/reset', 'Auth\ForgotPasswordController@showLinkRequestForm')->name('password.request');
Route::post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail')->name('password.email');
Route::get('password/reset/{token}', 'Auth\ResetPasswordController@showResetForm')->name('password.reset');
Route::post('password/reset', 'Auth\ResetPasswordController@reset');

//给用户控制器注册一个资源路由：
Route::resource('users', 'UsersController', ['only' => ['show', 'update', 'edit']]);
/*上面的代码等同于*/
/*
Route::get('/users/{user}', 'UsersController@show')->name('users.show');
Route::get('/users/{user}/edit', 'UsersController@edit')->name('users.edit');
Route::patch('/users/{user}', 'UsersController@update')->name('users.update');
*/

//话题（帖子）这里没有show方法
Route::resource('topics', 'TopicsController', ['only' => ['index', 'create', 'store', 'update', 'edit', 'destroy']]);
Route::get('topics/{topic}/{slug?}', 'TopicsController@show')->name('topics.show'); //show方法加了seo优化后缀
//分类
Route::resource('categories', 'CategoriesController', ['only' => ['show']]);

//设置话题上传图片路由
Route::post('upload_image', 'TopicsController@uploadImage')->name('topics.upload_image');

//回复的创建和删除
Route::resource('replies', 'RepliesController', ['only' => ['store', 'destroy']]);

//回复通知
Route::resource('notifications', 'NotificationsController', ['only' => ['index']]);

//无权限访问时的跳转页
Route::get('permission-denied', 'PagesController@permissionDenied')->name('permission-denied');

