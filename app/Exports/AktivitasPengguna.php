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
// use Jenssegers\Date\Date;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ApiController;
use Artisan;
use Cookie;
use JWTAuth;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class AktivitasPengguna implements FromView
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
            $request['app'] = 'users';
            $request['url_active'] = 'activityusers';
            $viewpath = 'admin.AdminOne.masterpengguna.exportdata.aktivitaspengguna';

            $get_user[] = app('App\Http\Controllers\ApiController')->getadmin($request);  
            $get_user = collect($get_user)->toJson();
            $get_user = json_decode($get_user,true);
            $get_user = $get_user[0]['original'];
            $res_user = $get_user['results'][0]['detailadmin'][0];

           Carbon::setLocale('en');

            $datefilterstart = Carbon::now()->modify("-30 days")->format('Y-m-d') . ' 00:00:00';
            $datefilterend = Carbon::now()->modify("0 days")->format('Y-m-d') . ' 23:59:59';

            if($request->searchdate != ''){
                $getsearchdate = explode ("sd",$request->searchdate);
                $datefilterstart = Carbon::parse($getsearchdate[0])->format('Y-m-d') . ' 00:00:00';
                $datefilterend = Carbon::parse($getsearchdate[1])->format('Y-m-d') . ' 23:59:59';
            }

            $request['vd'] = '999999999999999';
            $request['type'] = 'export';
            $request['searchdate'] = $datefilterstart.'sd'.$datefilterend;
            
            $results[] = app('App\Http\Controllers\ApiControllerMasterPengguna')->activityusers($request);  
            $results = collect($results)->toJson();
            $results = json_decode($results,true);
            $results = $results[0]['original'];

             return view($viewpath,['url_api' => $url_api,'app' => $request['app'],'url_active' => $request['url_active'],'request' => $request,'res_user' => $res_user,'results' => $results['results']]);
        }
    }
}