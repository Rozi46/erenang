<?php

namespace App\Exports;


require '../vendor/autoload.php';

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ApiController;
use Artisan;
use Cookie;
use JWTAuth;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class Atlet implements FromView
{
    public $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function view(): View
    {
    	if(!session()->has('key_token_renang') && !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{
            $request = $this->request;
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'masterdata';
            $request['url_active'] = 'listatlet';
            $viewpath = 'admin.AdminOne.masterdata.exportdata.dataatlet';

            $get_user[] = app('App\Http\Controllers\ApiController')->getadmin($request);  
            $get_user = collect($get_user)->toJson();
            $get_user = json_decode($get_user,true);
            $get_user = $get_user[0]['original'];
            $res_user = $get_user['results'][0]['detailadmin'][0];

            $request['vd'] = '999999999999999';
            $request['type'] = 'export';
            
            $results[] = app('App\Http\Controllers\ApiControllerMasterData')->listatlet($request);  
            $results = collect($results)->toJson();
            $results = json_decode($results,true);
            $results = $results[0]['original'];

             return view($viewpath,['url_api' => $url_api,'app' => $request['app'],'url_active' => $request['url_active'],'request' => $request,'res_user' => $res_user,'results' => $results['results']['listdata'],'listdata' => $results['results']]);
        }
    }
}