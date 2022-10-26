<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::group([
	'prefix'        => '/',
	'namespace'     => 'App\Http\Controllers\Api',
	// 'middleware'    => ['language'],
	'as'            => 'api.'
], function () {
	Route::group([
		'prefix'        => 'tiktok/',
		'namespace'     => 'Tiktok',
		'as'			=> 'tiktok.'
	], function () {
		Route::get('callback', 'CallbackController@index')->name('callback');
		Route::get('login', 'CallbackController@userLogin')->name('login');
		Route::post('proinfo', 'CallbackController@proinfo')->name('proinfo');
		Route::post('orderinfo', 'CallbackController@orderinfo')->name('orderinfo');
		Route::get('aggregate', 'CallbackController@aggregate')->name('aggregate');
        Route::post('upload', 'IndexController@upload');
		Route::group([
			'prefix'        => 'index/',
			'as'			=> 'index.'
		], function () {
			Route::get('index', 'IndexController@index')->name('index');
			Route::get('ranking', 'IndexController@ranking')->name('ranking');
		});
		Route::group([
			'prefix'        => 'product/',
			'as'			=> 'product.'
		], function () {
			Route::get('index', 'ProductController@index')->name('index');
			Route::get('options', 'ProductController@options')->name('options');
			Route::get('detail', 'ProductController@detail')->name('detail')->middleware(['getlogin']); //商品详情
			Route::get('collection', 'ProductController@collects')->name('collects')->middleware(['auths']); //收藏列表
			Route::post('collection', 'ProductController@collection')->name('collection')->middleware(['auths']); //收藏/取消收藏同一个接口
			Route::post('collect', 'ProductController@collect')->name('collect')->middleware(['auths']); //收藏
			Route::post('uncollect', 'ProductController@uncollect')->name('uncollect')->middleware(['auths']); //取消收藏
			Route::post('apply', 'ProductController@apply')->name('apply')->middleware(['auths']); //申领样品

            route::group([
                'prefix' => 'video',
                'as' => 'product.',
                'middleware' => ['auths']
            ], function () {
                route::get('check', 'ProductVideoController@check')->name('check');
                route::get('original', 'ProductVideoController@original')->name('original');
                route::get('receivedList', 'ProductVideoController@receivedList')->name('receivedList');
                route::post('receiveOriginal', 'ProductVideoController@receiveOriginal')->name('receiveOriginal');
                route::post('receiveClip', 'ProductVideoController@receiveClip')->name('receiveClip');
            });
		});

		Route::group([
			'middleware'	=> ['auths'],
			'prefix'        => 'user/',
			'as'			=> 'user.'
		], function () {
			Route::get('address', 'AddressController@index')->name('address');
			Route::post('address', 'AddressController@add')->name('address.add'); //添加地址
			Route::post('address/editer', 'AddressController@editer')->name('address.editer');
			Route::post('address/default', 'AddressController@default')->name('address.default');
			Route::post('address/remove', 'AddressController@remove')->name('address.remove');

			Route::group([
				'prefix'		=> 'darren/',
				'as'			=> 'darren.'
			], function () {
				Route::get('', 'DarrenController@index')->name('index');
				Route::post('add', 'DarrenController@add')->name('add');
				Route::post('remove', 'DarrenController@remove')->name('remove');
			});
		});

		//样品
		Route::group([
			'middleware'	=> ['auths'],
			'prefix'        => 'sample/',
			'as'			=> 'sample.'
		], function () {
			Route::post('cancel', 'SampleController@unapply')->name('cancel');
			Route::get('lists', 'SampleController@index')->name('address');
		});
	});

    Route::group([
        'namespace'     => 'Pay',
        'prefix'        => 'pay/',
        'as'			=> 'pay.'
    ], function () {
        Route::post('pay/course', 'PayController@payCourse')->middleware(['auths']);
        Route::get('callback/course', 'PayController@callbackCourse')->name('courseCallback')->middleware(['auths']);
        Route::post('notify', 'PayController@notify')->name('notify');
    });

    Route::group([
        'namespace'     => 'Course',
        'prefix'        => 'course/',
        'as'			=> 'course.',
        'middleware' => ['auths']
    ], function () {
        Route::get('', 'CourseController@courseList')->name('courseList');
        Route::get('category', 'CourseController@category')->name('category');
        Route::get('video', 'CourseController@videoList')->name('videoList');
        Route::get('video/{id}/info', 'CourseController@videoInfo')->name('videoInfo');
    });

    Route::group([
        'namespace'     => 'HT',
        'prefix'        => 'ht/',
        'as'			=> 'ht.',
        'middleware' => ['auths']
    ], function () {
        Route::post('article', 'ArticleController@add')->name('add');
        Route::get('article', 'ArticleController@search')->name('search');
    });

    Route::group([
        'namespace' => 'General',
        'prefix' => 'general/',
        'as' => 'general.'
    ], function () {
        Route::get('dict/info', 'DictController@info')->name('dictInfo');
    });

	// 用户
	Route::group([
		// 'middleware'	=> ['auths'],
		'namespace'     => 'User',
		'as'			=> 'user.'
	], function () {
		Route::post('emailcode', 'UserOpenController@sendCode')->name('emailcode');
		Route::post('sign', 'UserOpenController@sign')->name('sign');
		Route::post('login', 'UserOpenController@login')->name('login');
		Route::post('forgot', 'UserOpenController@forgot')->name('forgot');
		Route::get('country', 'UserOpenController@getCountries')->name('country');
		Route::get('country/{cn}', 'UserOpenController@getCities')->name('city');
		Route::get('langs', 'UserOpenController@getLanguages')->name('langs');
	});
	Route::group([
		'middleware'	=> ['auths'],
		'namespace'     => 'User',
		'as'			=> 'user.'
	], function () {
		Route::get('user', 'UserAuthController@GetUserInfo');
		Route::post('user/editerbatch', 'UserAuthController@updateUser');
	});

});
