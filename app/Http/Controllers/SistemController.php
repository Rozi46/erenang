<?php

namespace App\Http\Controllers;

require '../vendor/autoload.php';

use Illuminate\Http\{Request, Response, UploadedFile};
use Illuminate\Support\Facades\{Http, Route, Session, Hash, Artisan, Cookie};
use Illuminate\Support\Carbon;
use App\Http\Controllers\{Controller, ApiController};
use Tymon\JWTAuth\Facades\JWTAuth;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\{DataPengguna, AktivitasPengguna, Atlet, Club, Kategori, KelompokUmur, Championship, Event, Register};

class SistemController extends Controller
{
    public function formlogin(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $url_api =  env('APP_API');

        if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){            
            return view('admin.AdminOne.login',['url' => '/admin/login']);
        }else{
            return redirect('/admin/dash');
        }
    }

    public function login(Request $request)
    {   
        date_default_timezone_set('Asia/Jakarta');
        $url_api =  env('APP_API');
        $url_app =  env('ART_APP');
        $email = $request->email;
        $password = $request->password;

        $this->validate($request, [
            'email' => 'required|min:1|max:200',
            'password' => 'required|min:1|max:200',
        ]);

        $response[] = app('App\Http\Controllers\ApiController')->login($request);  
        $response = collect($response)->toJson();
        $response = json_decode($response,true);
        $response = $response[0]['original'];
		
		$status = $response['status_message'];
		$note = $response['note'];
        $results = $response['results'];

        if($status == 'success'){
        	$detailadmin = $results[0]['detailadmin'][0];
            
            if($detailadmin['level']=='LV7622003'){
                Session::put('key_token_renang_cash',$response['key_token']);
                Session::put('admin_login_renang_cash',$detailadmin['id']);    
                $this->backup_database();    
                return redirect('/cash/dash');
            }else{
                Session::put('key_token_renang',$response['key_token']);
                Session::put('admin_login_renang',$detailadmin['id']);
                $this->backup_database();
                return redirect('/admin/dash');
            }
        }else{
        	return redirect('/admin/administration')->with('error',$note);
        }
    }

    public function logout(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $url_api =  env('APP_API');
    	$admin_login = session('admin_login_renang');
    	$key_token = session('key_token_renang');
        $request['u'] = $admin_login;
        $request['token'] = $key_token;

        $response[] = app('App\Http\Controllers\ApiController')->logout($request);  
        $response = collect($response)->toJson();
        $response = json_decode($response,true);
        $response = $response[0]['original'];

        Session::forget('key_token_renang');
        Session::forget('admin_login_renang');

        Cookie::queue(Cookie::forget('key_token_renang'));

        $this->backup_database();

        return redirect('/admin/login');
    }

    public function dash(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'dash';
            $request['url_active'] = 'dash';
            $viewpath = 'admin.AdminOne.home';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company']; 
            
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            $vd = intval($request->vd ?? 20);
            $vd = max(1, min($vd, 100));
            $request['vd'] = $vd;
            
            $results[] = app('App\Http\Controllers\ApiController')->getdash($request);  
            $results = collect($results)->toJson();
            $results = json_decode($results,true);
            $results = $results[0]['original'];  
            
            return view($viewpath,['url_api' => $url_api,'app' => $request['app'],'url_active' => $request['url_active'],'request' => $request,'res_user' => $res_user,'level_user' => $level_user[0],'list_akses' => $list_akses['results'],'count_vd' => $vd,'listdata' => $results['results'] ]);
        }
    }

    // Kejuaraan
    public function listchampionship(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{ 
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'kejuaraan';
            $request['url_active'] = 'listchampionship';
            $menu='kejuaraan';
            $action='listchampionship';
            $viewpath = 'admin.AdminOne.championship.listdata.datachampionship';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company']; 

            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            $vd = intval($request->vd ?? 20);
            $vd = max(1, min($vd, 100));
            $request['vd'] = $vd;
            
            $results[] = app('App\Http\Controllers\ApiControllerChampionship')->listchampionship($request);  
            $results = collect($results)->toJson();
            $results = json_decode($results,true);
            $results = $results[0]['original'];        

            if($results['note'] == 'Tidak ada akses'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            return view($viewpath,['url_api' => $url_api,'app' => $request['app'],'url_active' => $request['url_active'],'request' => $request,'res_user' => $res_user,'level_user' => $level_user[0],'list_akses' => $list_akses['results'],'count_vd' => $vd,'keysearch' => $request->keysearch,'results' => $results['results']['listdata'],'listdata' => $results['results']]);
        }
    }

    public function newchampionship(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{ 
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'kejuaraan';
            $request['url_active'] = 'listchampionship';
            $menu='listchampionship';
            $action='newchampionship';
            $viewpath = 'admin.AdminOne.championship.newdata.datachampionship';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company'];

            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            return view($viewpath,['url_api' => $url_api,'app' => $request['app'],'url_active' => $request['url_active'],'request' => $request,'res_user' => $res_user,'level_user' => $level_user[0],'list_akses' => $list_akses['results']]);
        }
    }

    public function editchampionship(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{  
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'kejuaraan';
            $request['url_active'] = 'listchampionship';
            $menu='listchampionship';
            $action='editchampionship';
            $viewpath = 'admin.AdminOne.championship.editdata.datachampionship';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company']; 
            
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            $request['code_data'] = $request['d'];
            
            $get_data[] = app('App\Http\Controllers\ApiControllerChampionship')->viewchampionship($request);  
            $get_data = collect($get_data)->toJson();
            $get_data = json_decode($get_data,true);
            $get_data = $get_data[0]['original'];

            if($get_data['note'] == 'Data tidak ditemukan'){return redirect('/admin/dash')->with('error','Data tidak ditemukan');}

           return view($viewpath,['url_api' => $url_api,'app' => $request['app'],'url_active' => $request['url_active'],'request' => $request,'res_user' => $res_user,'level_user' => $level_user[0],'list_akses' => $list_akses['results'],'results' => $get_data]);
        }
    }

    public function exportchampionship(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{ 
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'kejuaraan';
            $request['url_active'] = 'listchampionship';
            $menu='listchampionship';
            $action='exportchampionship';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company']; 

            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}
            
            $datetime_now = date('Y-m-d-His');
            $nama_file = "Data-Kejuaraan-".$datetime_now.".xls" ;
            return Excel::download(new Championship($request), $nama_file);
        }
    }

    // Hasil Pertandingan
    public function historyresult(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{ 
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'menuhasilpertandingan';
            $request['url_active'] = 'menudatahasilpertandingan';
            $menu='menudatahasilpertandingan';
            $action='historyresult';
            $viewpath = 'admin.AdminOne.result.listdata.dataresult';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company']; 

            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            $vd = intval($request->vd ?? 20);
            $vd = max(1, min($vd, 100));
            $request['vd'] = $vd;
            
            $results[] = app('App\Http\Controllers\ApiControllerResult')->historyresult($request);  
            $results = collect($results)->toJson();
            $results = json_decode($results,true);
            $results = $results[0]['original'];   

            if($results['note'] == 'Tidak ada akses'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            return view($viewpath,['url_api' => $url_api,'app' => $request['app'],'url_active' => $request['url_active'],'request' => $request,'res_user' => $res_user,'level_user' => $level_user[0],'list_akses' => $list_akses['results'],'count_vd' => $vd,'keysearch' => $request->keysearch,'results' => $results['results']['listdata'],'listdata' => $results['results']]);
        }
    }

    public function getopeventresult(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{ 
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'menuhasilpertandingan';
            $request['url_active'] = 'menudatahasilpertandingan';
            $menu='menudatahasilpertandingan';
            $action='inputresult';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company'];

            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');} 

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}   

            $results[] = app('App\Http\Controllers\ApiControllerResult')->listopeventresult($request);  
            $results = collect($results)->toJson();
            $results = json_decode($results,true);
            $results = $results[0]['original'];
        
            return $results;
        }
    }

    public function inputresult(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{ 
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'menuhasilpertandingan';
            $request['url_active'] = 'menudatahasilpertandingan';
            $menu='menudatahasilpertandingan';
            $action='inputresult';
            $viewpath = 'admin.AdminOne.result.newdata.dataresult';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company'];

            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');} 

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}
                       
            $list_championship = $this->get_op_championshipResult($request);

            return view($viewpath,['url_api' => $url_api,'app' => $request['app'],'url_active' => $request['url_active'],'request' => $request,'res_user' => $res_user,'level_user' => $level_user[0],'list_akses' => $list_akses['results'],'list_championship' => $list_championship['results']]);
        }
    }

    public function listdataevent(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{ 
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'menuhasilpertandingan';
            $request['url_active'] = 'menudatahasilpertandingan';
            $menu='menudatahasilpertandingan';
            $action='inputresult';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company']; 

            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');} 

            $vd = intval($request->vd ?? 20);
            $vd = max(1, min($vd, 100));
            $request['vd'] = $vd;

            if($request->status_data == 'Yes'){      
                $viewpath = 'admin.AdminOne.result.inputdata.listdataresult';      
                $results[] = app('App\Http\Controllers\ApiControllerResult')->viewresult($request);  
                $results = collect($results)->toJson();
                $results = json_decode($results,true);
                $results = $results[0]['original'];        

                if($results['note'] == 'Tidak ada akses'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

                return view($viewpath,['url_api' => $url_api,'app' => $request['app'],'url_active' => $request['url_active'],'request' => $request,'res_user' => $res_user,'level_user' => $level_user[0],'list_akses' => $list_akses['results'],'count_vd' => $vd,'keysearch' => $request->keysearch,'results' => $results['results']['listdata'],'listdata' => $results['results']]);
            }else{    
                $viewpath = 'admin.AdminOne.result.inputdata.listdataevent';        
                $results[] = app('App\Http\Controllers\ApiControllerResult')->listdataevent($request);  
                $results = collect($results)->toJson();
                $results = json_decode($results,true);
                $results = $results[0]['original'];        

                if($results['note'] == 'Tidak ada akses'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

                return view($viewpath,['url_api' => $url_api,'app' => $request['app'],'url_active' => $request['url_active'],'request' => $request,'res_user' => $res_user,'level_user' => $level_user[0],'list_akses' => $list_akses['results'],'count_vd' => $vd,'keysearch' => $request->keysearch,'results' => $results['results']['listdata'],'listdata' => $results['results']]);
            }
        }
    }

    public function viewresult(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{  
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'menuhasilpertandingan';
            $request['url_active'] = 'menudatahasilpertandingan';
            $menu='menudatahasilpertandingan';
            $action='inputresult';
            $viewpath = 'admin.AdminOne.result.editdata.dataresult';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company']; 
            
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');} 

            $request['code_data'] = $request['d'];
            $request['code_championship'] = $request['code_championship'];
            $request['code_event'] = $request['code_event'];
            
            $get_data[] = app('App\Http\Controllers\ApiControllerResult')->viewresult($request);  
            $get_data = collect($get_data)->toJson();
            $get_data = json_decode($get_data,true);
            $get_data = $get_data[0]['original'];

            if($get_data['note'] == 'Data tidak ditemukan'){return redirect('/admin/dash')->with('error','Data tidak ditemukan');}            
            
            $code_data = $request->get('code_data');
            $code_championship = $request['code_championship'];
            $code_event = $request['code_event'];

           return view($viewpath,['url_api' => $url_api,'app' => $request['app'],'url_active' => $request['url_active'],'request' => $request,'res_user' => $res_user,'level_user' => $level_user[0],'list_akses' => $list_akses['results'],'results' => $get_data,'code_data' => $code_data,'code_championship' => $code_championship,'code_event' => $code_event]);
        }
    }
    
    public function listdataresult(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{ 
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'menuhasilpertandingan';
            $request['url_active'] = 'menudatahasilpertandingan';
            $menu='menudatahasilpertandingan';
            $action='historyresult';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company']; 

            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');} 

            if($request->status_data == 'Yes'){      
                $viewpath = 'admin.AdminOne.result.inputdata.listinputdataresult';      
                $results[] = app('App\Http\Controllers\ApiControllerResult')->viewresult($request);  
                $results = collect($results)->toJson();
                $results = json_decode($results,true);
                $results = $results[0]['original'];   

                if($results['note'] == 'Tidak ada akses'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

                return view($viewpath,['url_api' => $url_api,'app' => $request['app'],'url_active' => $request['url_active'],'request' => $request,'res_user' => $res_user,'level_user' => $level_user[0],'list_akses' => $list_akses['results'],'results' => $results['results']['result'],'listdata' => $results['results']]);
            }else{    
                $viewpath = 'admin.AdminOne.result.inputdata.listdataevent';        
                $results[] = app('App\Http\Controllers\ApiControllerResult')->listdataevent($request);  
                $results = collect($results)->toJson();
                $results = json_decode($results,true);
                $results = $results[0]['original'];        

                if($results['note'] == 'Tidak ada akses'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

                return view($viewpath,['url_api' => $url_api,'app' => $request['app'],'url_active' => $request['url_active'],'request' => $request,'res_user' => $res_user,'level_user' => $level_user[0],'list_akses' => $list_akses['results'],'results' => $results['results']['listdata'],'listdata' => $results['results']]);
            }
        }
    }

    public function printresult(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{ 
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'kejuaraan';
            $request['url_active'] = 'listchampionship';
            $menu='menudatahasilpertandingan';
            $action='historyresult';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company']; 

            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');} 

            $request['code_data'] = $request['d'];
            $request['tipe_page'] = 'full';
            $request['file_print'] = 'result';
            $request['title_print'] = 'Result';
            
            return view('admin/AdminOne/print/tempprint',['url_api' => $url_api,'app' => 'tempprint','url_active' => 'tempprint','request' => $request,'res_user' => $res_user,'level_user' => $level_user[0],'list_akses' => $list_akses['results']]);
        }
    }

    // Pendafataram
    public function inputregister(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{ 
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'menupendaftaran';
            $request['url_active'] = 'menuregister';
            $menu='menuregister';
            $action='inputregister';
            $viewpath = 'admin.AdminOne.menuregister.newdata.register';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company'];

            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');} 
            
            $list_club = $this->get_op_club($request);            
            $list_championship = $this->get_op_championshipRegister($request);

            return view($viewpath,['url_api' => $url_api,'app' => $request['app'],'url_active' => $request['url_active'],'request' => $request,'res_user' => $res_user,'level_user' => $level_user[0],'list_akses' => $list_akses['results'],'list_club' => $list_club['results'],'list_championship' => $list_championship['results']]);
        }
    }

    public function getopatlete(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{ 
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'menupendaftaran';
            $request['url_active'] = 'menuregister';
            $menu='menuregister';
            $action='inputregister';
            $viewpath = 'admin.AdminOne.menuregister.newdata.register';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company'];

            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');} 

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}  

            $results[] = app('App\Http\Controllers\ApiControllerRegister')->listopatlet($request);  
            $results = collect($results)->toJson();
            $results = json_decode($results,true);
            $results = $results[0]['original'];
        
            return $results;
        }
    }

    public function getopevent(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{ 
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'menupendaftaran';
            $request['url_active'] = 'menuregister';
            $menu='menuregister';
            $action='inputregister';
            $viewpath = 'admin.AdminOne.menuregister.newdata.register';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company'];

            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');} 

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}     

            $results[] = app('App\Http\Controllers\ApiControllerRegister')->listopevent($request);  
            $results = collect($results)->toJson();
            $results = json_decode($results,true);
            $results = $results[0]['original'];
        
            return $results;
        }
    }

    public function histroryregister(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{ 
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'menupendaftaran';
            $request['url_active'] = 'menuregister';
            $menu='menuregister';
            $action='histroryregister';  
            $viewpath = 'admin.AdminOne.menuregister.listdata.historyregister';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company'];

            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');} 

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}  

            Carbon::setLocale('en');

            $datefilterstart = Carbon::now()->modify("-1 month")->format('Y-m-d') . ' 00:00:00';
            $datefilterend = Carbon::now()->modify("0 days")->format('Y-m-d') . ' 23:59:59';

            if($request->searchdate != ''){
                $getsearchdate = explode ("sd",$request->searchdate);
                $datefilterstart = Carbon::parse($getsearchdate[0])->format('Y-m-d') . ' 00:00:00';
                $datefilterend = Carbon::parse($getsearchdate[1])->format('Y-m-d') . ' 23:59:59';
            }

            $vd = intval($request->vd ?? 20);
            $vd = max(1, min($vd, 100));
            $request['vd'] = $vd;

            $results[] = app('App\Http\Controllers\ApiControllerRegister')->historyregister($request);  
            $results = collect($results)->toJson();
            $results = json_decode($results,true);
            $results = $results[0]['original'];

            if($results['note'] == 'Tidak ada akses'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            // return $results;

            return view($viewpath,['url_api' => $url_api,'app' => $request['app'],'url_active' => $request['url_active'],'request' => $request,'res_user' => $res_user,'level_user' => $level_user[0],'list_akses' => $list_akses['results'],'count_vd' => $vd,'keysearch' => $request->keysearch,'results' => $results['results']['listdata'],'listdata' => $results['results'],'searchdate' => 'searchdate='.$request->searchdate,'datefilterstart' => $datefilterstart,'datefilterend' => $datefilterend]);
        }
    }

    public function viewregister(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{ 
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'menupendaftaran';
            $request['url_active'] = 'menuregister';
            $menu='menuregister';
            $action='editregister';  
            $viewpath = 'admin.AdminOne.menuregister.editdata.register';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company'];

            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}  

            $request['code_data'] = $request['d'];
            $results[] = app('App\Http\Controllers\ApiControllerRegister')->viewregister($request);  
            $results = collect($results)->toJson();
            $results = json_decode($results,true);
            $results = $results[0]['original'];

            if($results['note'] == 'Data tidak ditemukan'){return redirect('/admin/dash')->with('error','Data tidak ditemukan');}

            // return $results;
            $list_club = $this->get_op_club($request);         
            $list_championship = $this->get_op_championshipRegister($request);

            return view($viewpath,['url_api' => $url_api,'app' => $request['app'],'url_active' => $request['url_active'],'request' => $request,'res_user' => $res_user,'level_user' => $level_user[0],'list_akses' => $list_akses['results'],'results' => $results,'list_club' => $list_club['results'],'list_championship' => $list_championship['results']]);
        }
    }

    public function printbook(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{ 
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'kejuaraan';
            $request['url_active'] = 'listchampionship';
            $menu='menudatahasilpertandingan';
            $action='historyresult';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company']; 

            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            $request['code_data'] = $request['d'];
            $request['tipe_page'] = 'full';
            $request['file_print'] = 'book';
            $request['title_print'] = 'Book';
            
            return view('admin/AdminOne/print/tempprint',['url_api' => $url_api,'app' => 'tempprint','url_active' => 'tempprint','request' => $request,'res_user' => $res_user,'level_user' => $level_user[0],'list_akses' => $list_akses['results']]);
        }
    }

    public function exportregister(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{ 
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'menupendaftaran';
            $request['url_active'] = 'menuregister';
            $menu='menuregister';
            $action='exportregister'; 

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company']; 

            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);            

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}
            
            $datetime_now = date('Y-m-d-His');
            $nama_file = "Data-Pendaftaran-".$datetime_now.".xls" ;
            return Excel::download(new Register($request), $nama_file);
        }
    }

    // Event
    public function listevent(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{ 
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'kejuaraan';
            $request['url_active'] = 'listevent';
            $menu='kejuaraan';
            $action='listevent'; 
            $viewpath = 'admin.AdminOne.championship.listdata.event';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company']; 

            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            $vd = intval($request->vd ?? 20);
            $vd = max(1, min($vd, 100));
            $request['vd'] = $vd;
            
            $results[] = app('App\Http\Controllers\ApiControllerChampionship')->listevent($request);  
            $results = collect($results)->toJson();
            $results = json_decode($results,true);
            $results = $results[0]['original'];        

            if($results['note'] == 'Tidak ada akses'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            return view($viewpath,['url_api' => $url_api,'app' => $request['app'],'url_active' => $request['url_active'],'request' => $request,'res_user' => $res_user,'level_user' => $level_user[0],'list_akses' => $list_akses['results'],'count_vd' => $vd,'keysearch' => $request->keysearch,'results' => $results['results']['listdata'],'listdata' => $results['results']]);
        }
    }

    public function newevent(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{ 
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'kejuaraan';
            $request['url_active'] = 'listevent';
            $menu='listevent';
            $action='newevent'; 
            $viewpath = 'admin.AdminOne.championship.newdata.event';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company'];

            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}
            
            $list_gaya = $this->get_op_gaya($request);
            $list_ku = $this->get_op_ku($request);            
            $list_championship = $this->get_op_championship($request);

            return view($viewpath,['url_api' => $url_api,'app' => $request['app'],'url_active' => $request['url_active'],'request' => $request,'res_user' => $res_user,'level_user' => $level_user[0],'list_akses' => $list_akses['results'],'list_gaya' => $list_gaya['results'],'list_ku' => $list_ku['results'],'list_championship' => $list_championship['results']]);
        }
    }

    public function editevent(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{  
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'kejuaraan';
            $request['url_active'] = 'listevent';
            $menu='listevent';
            $action='editevent'; 
            $viewpath = 'admin.AdminOne.championship.editdata.event';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company']; 
            
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            $request['code_data'] = $request['d'];
            
            $get_data[] = app('App\Http\Controllers\ApiControllerChampionship')->viewevent($request);  
            $get_data = collect($get_data)->toJson();
            $get_data = json_decode($get_data,true);
            $get_data = $get_data[0]['original'];

            if($get_data['note'] == 'Data tidak ditemukan'){return redirect('/admin/dash')->with('error','Data tidak ditemukan');}
            
            $list_gaya = $this->get_op_gaya($request);
            $list_ku = $this->get_op_ku($request);            
            $list_championship = $this->get_op_championship($request);

           return view($viewpath,['url_api' => $url_api,'app' => $request['app'],'url_active' => $request['url_active'],'request' => $request,'res_user' => $res_user,'level_user' => $level_user[0],'list_akses' => $list_akses['results'],'results' => $get_data,'list_gaya' => $list_gaya['results'],'list_ku' => $list_ku['results'],'list_championship' => $list_championship['results']]);
        }
    }

    public function exportevent(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{ 
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;            
            $request['app'] = 'kejuaraan';
            $request['url_active'] = 'listevent';
            $menu='listevent';
            $action='exportevent'; 

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company']; 

            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}
            
            $datetime_now = date('Y-m-d-His');
            $nama_file = "Data-Nomor Lomba-".$datetime_now.".xls" ;
            return Excel::download(new Event($request), $nama_file);
        }
    }

    // Heat
    public function listheat(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{ 
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'kejuaraan';
            $request['url_active'] = 'listheat';
            $menu='kejuaraan';
            $action='listheat'; 
            $viewpath = 'admin.AdminOne.championship.listdata.heat';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company']; 

            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            $vd = intval($request->vd ?? 20);
            $vd = max(1, min($vd, 100));
            $request['vd'] = $vd;
            
            $results[] = app('App\Http\Controllers\ApiControllerChampionship')->listheat($request);  
            $results = collect($results)->toJson();
            $results = json_decode($results,true);
            $results = $results[0]['original'];        

            if($results['note'] == 'Tidak ada akses'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            return view($viewpath,['url_api' => $url_api,'app' => $request['app'],'url_active' => $request['url_active'],'request' => $request,'res_user' => $res_user,'level_user' => $level_user[0],'list_akses' => $list_akses['results'],'count_vd' => $vd,'keysearch' => $request->keysearch,'results' => $results['results']['listdata'],'listdata' => $results['results']]);
        }
    }

    // HeatLine
    public function listheatline(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{ 
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'kejuaraan';
            $request['url_active'] = 'listheatline';
            $menu='kejuaraan';
            $action='listheatline';
            $viewpath = 'admin.AdminOne.championship.listdata.heatline';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company']; 

            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            $vd = intval($request->vd ?? 20);
            $vd = max(1, min($vd, 100));
            $request['vd'] = $vd;
            
            $results[] = app('App\Http\Controllers\ApiControllerChampionship')->listheatline($request);  
            $results = collect($results)->toJson();
            $results = json_decode($results,true);
            $results = $results[0]['original'];        

            if($results['note'] == 'Tidak ada akses'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            return view($viewpath,['url_api' => $url_api,'app' => $request['app'],'url_active' => $request['url_active'],'request' => $request,'res_user' => $res_user,'level_user' => $level_user[0],'list_akses' => $list_akses['results'],'count_vd' => $vd,'keysearch' => $request->keysearch,'results' => $results['results']['listdata'],'listdata' => $results['results']]);
        }
    }

    // Atlet
    public function listatlet(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{ 
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'masterdata';
            $request['url_active'] = 'listatlet';
            $menu='masterdata';
            $action='listatlet';
            $viewpath = 'admin.AdminOne.masterdata.listdata.dataatlet';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company']; 

            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            $vd = intval($request->vd ?? 20);
            $vd = max(1, min($vd, 100));
            $request['vd'] = $vd;
            
            $results[] = app('App\Http\Controllers\ApiControllerMasterData')->listatlet($request);  
            $results = collect($results)->toJson();
            $results = json_decode($results,true);
            $results = $results[0]['original'];        

            if($results['note'] == 'Tidak ada akses'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            return view($viewpath,['url_api' => $url_api,'app' => $request['app'],'url_active' => $request['url_active'],'request' => $request,'res_user' => $res_user,'level_user' => $level_user[0],'list_akses' => $list_akses['results'],'count_vd' => $vd,'keysearch' => $request->keysearch,'results' => $results['results']['listdata'],'listdata' => $results['results']]);
        }
    }

    public function newatlet(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{ 
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'masterdata';
            $request['url_active'] = 'listatlet';
            $menu='listatlet';
            $action='newatlet';
            $viewpath = 'admin.AdminOne.masterdata.newdata.dataatlet';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company'];

            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}
            
            $list_club = $this->get_op_club($request);

            return view($viewpath,['url_api' => $url_api,'app' => $request['app'],'url_active' => $request['url_active'],'request' => $request,'res_user' => $res_user,'level_user' => $level_user[0],'list_akses' => $list_akses['results'],'list_club' => $list_club['results']]);
        }
    }

    public function editatlet(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{  
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'masterdata';
            $request['url_active'] = 'listatlet';
            $menu='listatlet';
            $action='listatlet';
            $viewpath = 'admin.AdminOne.masterdata.editdata.dataatlet';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company']; 
            
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            $request['code_data'] = $request['d'];
            
            $get_data[] = app('App\Http\Controllers\ApiControllerMasterData')->viewatlet($request);  
            $get_data = collect($get_data)->toJson();
            $get_data = json_decode($get_data,true);
            $get_data = $get_data[0]['original'];

            if($get_data['note'] == 'Data tidak ditemukan'){return redirect('/admin/dash')->with('error','Data tidak ditemukan');}
            
            $list_club = $this->get_op_club($request);

           return view($viewpath,['url_api' => $url_api,'app' => $request['app'],'url_active' => $request['url_active'],'request' => $request,'res_user' => $res_user,'level_user' => $level_user[0],'list_akses' => $list_akses['results'],'results' => $get_data,'list_club' => $list_club['results']]);
        }
    }

    public function exportlistatlet(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{ 
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'masterdata';
            $request['url_active'] = 'listatlet';
            $menu='listatlet';
            $action='exportlistatlet';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company']; 

            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}
            
            $datetime_now = date('Y-m-d-His');
            $nama_file = "Data-Atlet-".$datetime_now.".xls" ;
            return Excel::download(new Atlet($request), $nama_file);
        }
    }

    // Data Club
    public function listclub(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{ 
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'masterdata';
            $request['url_active'] = 'listclub';
            $menu='masterdata';
            $action='listclub';
            $viewpath = 'admin.AdminOne.masterdata.listdata.dataclub';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company']; 

            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            $vd = intval($request->vd ?? 20);
            $vd = max(1, min($vd, 100));
            $request['vd'] = $vd;
            
            $results[] = app('App\Http\Controllers\ApiControllerMasterData')->listclub($request);  
            $results = collect($results)->toJson();
            $results = json_decode($results,true);
            $results = $results[0]['original'];

            if($results['note'] == 'Tidak ada akses'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            return view($viewpath,['url_api' => $url_api,'app' => $request['app'],'url_active' => $request['url_active'],'request' => $request,'res_user' => $res_user,'level_user' => $level_user[0],'list_akses' => $list_akses['results'],'count_vd' => $vd,'keysearch' => $request->keysearch,'results' => $results['results']['listdata'],'listdata' => $results['results']]);
        }
    }

    public function newclub(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{ 
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'masterdata';
            $request['url_active'] = 'listclub';
            $menu='listclub';
            $action='newclub';
            $viewpath = 'admin.AdminOne.masterdata.newdata.dataclub';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company'];

            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');} 

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');} 

           return view($viewpath,['url_api' => $url_api,'app' => $request['app'],'url_active' => $request['url_active'],'request' => $request,'res_user' => $res_user,'level_user' => $level_user[0],'list_akses' => $list_akses['results']]);
        }
    }

    public function editclub(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{  
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'masterdata';
            $request['url_active'] = 'listclub';
            $menu='listclub';
            $action='editclub';
            $viewpath = 'admin.AdminOne.masterdata.editdata.dataclub';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company']; 
            
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');} 

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');} 

            $request['code_data'] = $request['d'];
            
            $get_data[] = app('App\Http\Controllers\ApiControllerMasterData')->viewclub($request);  
            $get_data = collect($get_data)->toJson();
            $get_data = json_decode($get_data,true);
            $get_data = $get_data[0]['original'];

            if($get_data['note'] == 'Data tidak ditemukan'){return redirect('/admin/dash')->with('error','Data tidak ditemukan');}

           return view($viewpath,['url_api' => $url_api,'app' => $request['app'],'url_active' => $request['url_active'],'request' => $request,'res_user' => $res_user,'level_user' => $level_user[0],'list_akses' => $list_akses['results'],'results' => $get_data]);
        }
    }

    public function exportlistclub(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{ 
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;            
            $request['app'] = 'masterdata';
            $request['url_active'] = 'listclub';
            $menu='listclub';
            $action='exportclub';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company']; 

            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');} 
            
            $datetime_now = date('Y-m-d-His');
            $nama_file = "Data-Club-".$datetime_now.".xls" ;
            return Excel::download(new Club($request), $nama_file);
        }
    }

    // Kategori - Gaya Renang
    public function listkategori(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{ 
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'masterdata';
            $request['url_active'] = 'listkategori'; 
            $menu='masterdata';
            $action='listkategori';
            $viewpath = 'admin.AdminOne.masterdata.listdata.datakategori';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company']; 

            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');} 

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');} 

            $vd = intval($request->vd ?? 20);
            $vd = max(1, min($vd, 100)); 
            $request['vd'] = $vd;
            
            $results[] = app('App\Http\Controllers\ApiControllerMasterData')->listkategori($request);  
            $results = collect($results)->toJson();
            $results = json_decode($results,true);
            $results = $results[0]['original'];

            if($results['note'] == 'Tidak ada akses'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            return view($viewpath,['url_api' => $url_api,'app' => $request['app'],'url_active' => $request['url_active'],'request' => $request,'res_user' => $res_user,'level_user' => $level_user[0],'list_akses' => $list_akses['results'],'count_vd' => $vd,'keysearch' => $request->keysearch,'results' => $results['results']['listdata'],'listdata' => $results['results']]);
        }
    }

    public function newkategori(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{ 
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'masterdata';
            $request['url_active'] = 'listkategori';
            $menu='listkategori';
            $action='newkategori';
            $viewpath = 'admin.AdminOne.masterdata.newdata.datakategori';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company'];

            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');} 

           return view($viewpath,['url_api' => $url_api,'app' => $request['app'],'url_active' => $request['url_active'],'request' => $request,'res_user' => $res_user,'level_user' => $level_user[0],'list_akses' => $list_akses['results']]);
        }
    }

    public function editkategori(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{  
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'masterdata';
            $request['url_active'] = 'listkategori';
            $menu='listkategori';
            $action='editkategori';
            $viewpath = 'admin.AdminOne.masterdata.editdata.datakategori';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company']; 
            
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');} 

            $request['code_data'] = $request['d'];
            
            $get_data[] = app('App\Http\Controllers\ApiControllerMasterData')->viewkategori($request);  
            $get_data = collect($get_data)->toJson();
            $get_data = json_decode($get_data,true);
            $get_data = $get_data[0]['original'];

            if($get_data['note'] == 'Data tidak ditemukan'){return redirect('/admin/dash')->with('error','Data tidak ditemukan');}

           return view($viewpath,['url_api' => $url_api,'app' => $request['app'],'url_active' => $request['url_active'],'request' => $request,'res_user' => $res_user,'level_user' => $level_user[0],'list_akses' => $list_akses['results'],'results' => $get_data]);
        }
    }

    public function exportlistkategori(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{ 
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token; 
            $request['app'] = 'masterdata';
            $request['url_active'] = 'listkategori';
            $menu='listkategori';
            $action='exportkategori';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company']; 

            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');} 
            
            $datetime_now = date('Y-m-d-His');
            $nama_file = "Data-Kategori-".$datetime_now.".xls" ;
            return Excel::download(new Kategori($request), $nama_file);
        }
    }

    // Kelopmok Umur
    public function listku(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{ 
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'masterdata';
            $request['url_active'] = 'listku';
            $menu='masterdata';
            $action='listku';
            $viewpath = 'admin.AdminOne.masterdata.listdata.dataku';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company']; 

            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');} 

            $vd = intval($request->vd ?? 20);
            $vd = max(1, min($vd, 100)); 
            $request['vd'] = $vd;
            
            $results[] = app('App\Http\Controllers\ApiControllerMasterData')->listku($request);  
            $results = collect($results)->toJson();
            $results = json_decode($results,true);
            $results = $results[0]['original'];

            if($results['note'] == 'Tidak ada akses'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            return view($viewpath,['url_api' => $url_api,'app' => $request['app'],'url_active' => $request['url_active'],'request' => $request,'res_user' => $res_user,'level_user' => $level_user[0],'list_akses' => $list_akses['results'],'count_vd' => $vd,'keysearch' => $request->keysearch,'results' => $results['results']['listdata'],'listdata' => $results['results']]);
        }
    }

    public function newku(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{ 
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'masterdata';
            $request['url_active'] = 'listku';
            $menu='listku';
            $action='newku';
            $viewpath = 'admin.AdminOne.masterdata.newdata.dataku';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company'];

            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');} 

           return view($viewpath,['url_api' => $url_api,'app' => $request['app'],'url_active' => $request['url_active'],'request' => $request,'res_user' => $res_user,'level_user' => $level_user[0],'list_akses' => $list_akses['results']]);
        }
    }

    public function editku(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{  
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'masterdata';
            $request['url_active'] = 'listku';
            $menu='listku';
            $action='editku';
            $viewpath = 'admin.AdminOne.masterdata.editdata.dataku';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company']; 
            
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');} 

            $request['code_data'] = $request['d'];
            
            $get_data[] = app('App\Http\Controllers\ApiControllerMasterData')->viewku($request);  
            $get_data = collect($get_data)->toJson();
            $get_data = json_decode($get_data,true);
            $get_data = $get_data[0]['original'];

            if($get_data['note'] == 'Data tidak ditemukan'){return redirect('/admin/dash')->with('error','Data tidak ditemukan');}

           return view($viewpath,['url_api' => $url_api,'app' => $request['app'],'url_active' => $request['url_active'],'request' => $request,'res_user' => $res_user,'level_user' => $level_user[0],'list_akses' => $list_akses['results'],'results' => $get_data]);
        }
    }

    public function exportlistku(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{ 
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;    
            $request['app'] = 'masterdata';
            $request['url_active'] = 'listku';
            $menu='listku';
            $action='exportku';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company']; 

            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');} 
            
            $datetime_now = date('Y-m-d-His');
            $nama_file = "Data-Kelompok-Umur-".$datetime_now.".xls" ;
            return Excel::download(new KelompokUmur($request), $nama_file);
        }
    }

    //Admin
    public function editaccount(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{  
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $viewpath = 'admin.AdminOne.masterpengguna.editdata.account';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company']; 

            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            return view($viewpath,['url_api' => $url_api,'app' => $request['app'],'url_active' => $request['url_active'],'request' => $request,'res_user' => $res_user,'level_user' => $level_user[0],'list_akses' => $list_akses['results']]);
        }
    }

    // Pengguna
    public function listusers(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{ 
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'users';
            $request['url_active'] = 'listusers';
            $menu='users';
            $action='listusers';
            $viewpath = 'admin.AdminOne.masterpengguna.listdata.datapengguna';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company']; 
            
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');} 

            $vd = intval($request->vd ?? 20);
            $vd = max(1, min($vd, 100));
            $request['vd'] = $vd;
            
            $results[] = app('App\Http\Controllers\ApiControllerMasterPengguna')->listusers($request);  
            $results = collect($results)->toJson();
            $results = json_decode($results,true);
            $results = $results[0]['original'];

            if($results['note'] == 'Tidak ada akses'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            return view($viewpath,['url_api' => $url_api,'app' => $request['app'],'url_active' => $request['url_active'],'request' => $request,'res_user' => $res_user,'level_user' => $level_user[0],'list_akses' => $list_akses['results'],'count_vd' => $vd,'keysearch' => $request->keysearch,'results' => $results['results']['list'],'listdata' => $results['results']]);
        }
    }

    public function exportlistusers(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{ 
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'users';
            $request['url_active'] = 'listusers';
            $menu='listusers';
            $action='exportusers';
            $viewpath = 'admin.AdminOne.masterpengguna.listdata.datapengguna';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company']; 
            
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');} 
            
            $datetime_now = date('Y-m-d-His');
            $nama_file = "Data-Pengguna-".$datetime_now.".xls" ;
            return Excel::download(new DataPengguna($request), $nama_file);
        }
    }

    public function newusers(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{  
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'users';
            $request['url_active'] = 'listusers';
            $menu='listusers';
            $action='newusers';
            $viewpath = 'admin.AdminOne.masterpengguna.newdata.datapengguna';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company']; 
            
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}
            $menu='listusers';
            $action='exportusers';

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');} 

            $list_level = $this->get_op_level($request);

            return view($viewpath,['url_api' => $url_api,'app' => $request['app'],'url_active' => $request['url_active'],'request' => $request,'res_user' => $res_user,'level_user' => $level_user[0],'list_akses' => $list_akses['results'],'list_level' => $list_level['results']]);
        }
    }

    public function editusers(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{  
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'users';
            $request['url_active'] = 'listusers';
            $menu='listusers';
            $action='editusers';
            $viewpath = 'admin.AdminOne.masterpengguna.editdata.datapengguna';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company']; 
            
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');} 

            $list_level = $this->get_op_level($request);

            $request['id'] = $request['d'];
            
            $get_data[] = app('App\Http\Controllers\ApiControllerMasterPengguna')->viewusers($request);  
            $get_data = collect($get_data)->toJson();
            $get_data = json_decode($get_data,true);
            $get_data = $get_data[0]['original'];

            if($get_data['note'] == 'Data tidak ditemukan'){return redirect('/admin/dash')->with('error','Data tidak ditemukan');}

            return view($viewpath,['url_api' => $url_api,'app' => $request['app'],'url_active' => $request['url_active'],'request' => $request,'res_user' => $res_user,'level_user' => $level_user[0],'list_akses' => $list_akses['results'],'list_level' => $list_level['results'],'get_data' => $get_data['results'],'results' => $get_data['results'][0],'detailadmin' => $get_data['results'][0]['detailadmin'][0]]);
        }
    }

    // Level Pengguna
    public function levelusers(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{  
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'users';
            $request['url_active'] = 'levelusers';
            $menu='users';
            $action='levelusers';
            $viewpath = 'admin.AdminOne.masterpengguna.listdata.levelpengguna';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company']; 
            
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            $access_rights = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');} 

            $vd = intval($request->vd ?? 20);
            $vd = max(1, min($vd, 100));
            $request['vd'] = $vd;
            
            $results[] = app('App\Http\Controllers\ApiControllerMasterPengguna')->listlevelusers($request);  
            $results = collect($results)->toJson();
            $results = json_decode($results,true);
            $results = $results[0]['original'];

            if($results['note'] == 'Tidak ada akses'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            return view($viewpath,['url_api' => $url_api,'app' => $request['app'],'url_active' => $request['url_active'],'request' => $request,'res_user' => $res_user,'level_user' => $level_user[0],'list_akses' => $list_akses['results'],'count_vd' => $vd,'keysearch' => $request->keysearch,'results' => $results['results']]);
        }
    }

    public function newlevelusers(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{  
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'users';
            $request['url_active'] = 'levelusers';
            $menu='levelusers';
            $action='newlevelusers';
            $viewpath = 'admin.AdminOne.masterpengguna.newdata.levelpengguna';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company']; 
            
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');} 

            return view($viewpath,['url_api' => $url_api,'app' => $request['app'],'url_active' => $request['url_active'],'request' => $request,'res_user' => $res_user,'level_user' => $level_user[0],'list_akses' => $list_akses['results']]);
        }
    }

    public function editlevel(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{  
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'users';
            $request['url_active'] = 'levelusers';
            $menu='levelusers';
            $action='editlevelusers';
            $viewpath = 'admin.AdminOne.masterpengguna.editdata.levelpengguna';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company']; 
            
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');} 

            $list_level = $this->get_op_level($request);

            $request['code_data'] = $request['d'];
            
            $get_data[] = app('App\Http\Controllers\ApiControllerMasterPengguna')->viewlevel($request);  
            $get_data = collect($get_data)->toJson();
            $get_data = json_decode($get_data,true);
            $get_data = $get_data[0]['original'];

            if($get_data['note'] == 'Data tidak ditemukan'){return redirect('/admin/dash')->with('error','Data tidak ditemukan');}

            return view($viewpath,['url_api' => $url_api,'app' => $request['app'],'url_active' => $request['url_active'],'request' => $request,'res_user' => $res_user,'level_user' => $level_user[0],'list_akses' => $list_akses['results'],'results' => $get_data]);
        }
    }

    // Aktifitas Pengguna
    public function activityusers(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{  
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'users';
            $request['url_active'] = 'activityusers';
            $menu='users';
            $action='activityusers';
            $viewpath = 'admin.AdminOne.masterpengguna.listdata.aktivitaspengguna';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company']; 
            
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');} 

            Carbon::setLocale('en');

            $datefilterstart = Carbon::now()->modify("-30 days")->format('Y-m-d') . ' 00:00:00';
            $datefilterend = Carbon::now()->modify("0 days")->format('Y-m-d') . ' 23:59:59';

            if($request->searchdate != ''){
                $getsearchdate = explode ("sd",$request->searchdate);
                $datefilterstart = Carbon::parse($getsearchdate[0])->format('Y-m-d') . ' 00:00:00';
                $datefilterend = Carbon::parse($getsearchdate[1])->format('Y-m-d') . ' 23:59:59';
            }

            $vd = intval($request->vd ?? 20);
            $vd = max(1, min($vd, 100));
            $request['vd'] = $vd;

            $request['type'] = 'list';
            $request['searchdate'] = $datefilterstart.'sd'.$datefilterend;   
            
            $results[] = app('App\Http\Controllers\ApiControllerMasterPengguna')->activityusers($request);  
            $results = collect($results)->toJson();
            $results = json_decode($results,true);
            $results = $results[0]['original'];

            if($results['note'] == 'Tidak ada akses'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

             return view($viewpath,['url_api' => $url_api,'app' => $request['app'],'url_active' => $request['url_active'],'request' => $request,'res_user' => $res_user,'level_user' => $level_user[0],'list_akses' => $list_akses['results'],'count_vd' => $vd,'keysearch' => $request->keysearch,'results' => $results['results'],'searchdate' => '&searchdate='.$request->searchdate,'datefilterstart' => $datefilterstart,'datefilterend' => $datefilterend]);
        }
    }

    public function exportactivityusers(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{ 
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'users';
            $request['url_active'] = 'activityusers';
            $menu='activityusers';
            $action='exportactivityusers';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company']; 
            
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0][$request['app']] == 'No' OR $level_user[0][$request['url_active']] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            if($level_user[0][$menu] == 'No' OR $level_user[0][$action] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');} 
            
            $datetime_now = date('Y-m-d-His');
            $nama_file = "Aktifitas-Pengguna-".$datetime_now.".xls" ;
            return Excel::download(new AktivitasPengguna($request), $nama_file);
        }
    }

    // Pengaturan
    public function settingmenu(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{  
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'setting';
            $request['url_active'] = 'settingmenu';
            $viewpath = 'admin.AdminOne.pengaturan.settingmenu';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company']; 
            
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($res_user['level'] == 'LV5677001'){
                 return view($viewpath,['url_api' => $url_api,'app' => $request['app'],'url_active' => $request['url_active'],'request' => $request,'res_user' => $res_user,'level_user' => $level_user[0],'list_akses' => $list_akses['results']]);
            }else{
                return redirect('/admin/dash')->with('error','Tidak ada akses');
            }
        }
    }

    public function listcompany(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{  
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'setting';
            $request['url_active'] = 'listcompany';
            $viewpath = 'admin.AdminOne.pengaturan.listcompany';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company']; 
            
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            $vd = intval($request->vd ?? 20);
            $vd = max(1, min($vd, 100));
            $request['vd'] = $vd;
            
            $results[] = app('App\Http\Controllers\ApiControllerPengaturan')->listcompany($request);  
            $results = collect($results)->toJson();
            $results = json_decode($results,true);
            $results = $results[0]['original'];

            if($results['note'] == 'Tidak ada akses'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            return view($viewpath,['url_api' => $url_api,'app' => $request['app'],'url_active' => $request['url_active'],'request' => $request,'res_user' => $res_user,'level_user' => $level_user[0],'list_akses' => $list_akses['results'],'count_vd' => $vd,'keysearch' => $request->keysearch,'results' => $results['results']['listdata'],'listdata' => $results['results']]);
        }
    }

    public function newcompany(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{ 
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'setting';
            $request['url_active'] = 'listcompany';
            $viewpath = 'admin.AdminOne.pengaturan.newcompany';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company'];

            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);     

            return view($viewpath,['url_api' => $url_api,'app' => $request['app'],'url_active' => $request['url_active'],'request' => $request,'res_user' => $res_user,'level_user' => $level_user[0],'list_akses' => $list_akses['results']]);
        }
    }

    public function editcompany(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{  
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'setting';
            $request['url_active'] = 'listcompany';
            $viewpath = 'admin.AdminOne.pengaturan.editcompany';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company']; 
            
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            $request['id'] = $request['d'];
            
            $get_data[] = app('App\Http\Controllers\ApiControllerPengaturan')->viewcompany($request);  
            $get_data = collect($get_data)->toJson();
            $get_data = json_decode($get_data,true);
            $get_data = $get_data[0]['original'];

            if($get_data['note'] == 'Data tidak ditemukan'){return redirect('/admin/dash')->with('error','Data tidak ditemukan');}

            return view($viewpath,['url_api' => $url_api,'app' => $request['app'],'url_active' => $request['url_active'],'request' => $request,'res_user' => $res_user,'level_user' => $level_user[0],'list_akses' => $list_akses['results'],'results' => $get_data]);
        }
    }

    public function manualbook(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{  
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'setting';
            $request['url_active'] = 'manualbook';
            $viewpath = 'admin.AdminOne.pengaturan.manualbook';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company']; 
            
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($res_user['level'] == 'LV5677001'){                
                $get_data[] = app('App\Http\Controllers\ApiControllerPengaturan')->viewManualBook($request);  
                $get_data = collect($get_data)->toJson();
                $get_data = json_decode($get_data,true);
                $get_data = $get_data[0]['original'];
    
                if($get_data['note'] == 'Data tidak ditemukan'){return redirect('/admin/dash')->with('error','Data tidak ditemukan');}

                return view($viewpath,['url_api' => $url_api,'app' => $request['app'],'url_active' => $request['url_active'],'request' => $request,'res_user' => $res_user,'level_user' => $level_user[0],'list_akses' => $list_akses['results'],'results' => $get_data]);
            }else{
                return redirect('/admin/dash')->with('error','Tidak ada akses');
            }
        }
    }

    public function viewmanualbook(Request $request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{  
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;
            $request['app'] = 'tempmanualbook';
            $request['url_active'] = 'tempmanualbook';
            $viewpath = 'admin.AdminOne.manualbook.tempmanualbook';

            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $request['data_company'] = $get_user['results']['data_company']; 
            
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $get_setting = $this->get_setting($request);
            $manual_book =  $get_setting['results']['data_setting']['manual_book'];
            $request['manual_book'] = $manual_book;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            $request['tipe_page'] = 'full';
            $request['file_manualbook'] = $request['d'];
            $request['title_manualbook'] = 'Manual Book';
            
            return view($viewpath,['url_api' => $url_api,'app' => $request['app'],'url_active' => $request['url_active'],'request' => $request,'res_user' => $res_user,'level_user' => $level_user[0],'list_akses' => $list_akses['results']]);
        }
    }

}
