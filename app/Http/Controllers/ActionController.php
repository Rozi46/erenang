<?php

namespace App\Http\Controllers;

require '../vendor/autoload.php';

use App\Http\Controllers\{Controller, ApiController};
use Illuminate\Http\{Request, Response, UploadedFile};
use Illuminate\Support\Facades\{Http, Route,Session, Hash, Artisan, Cookie};
use Illuminate\Support\Carbon;
use Tymon\JWTAuth\Facades\JWTAuth;

class ActionController extends Controller
{
    // Pendaftaran
    public function saveregister(Request $request)
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
 
            $get_user = $this->get_user($request);         
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0]['menuregister'] == 'No' OR $level_user[0]['inputregister'] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            $this->validate($request, [
                'code_club'         => 'required|string|max:200',
                'code_atlete'       => 'required|string|max:200',
                'code_championship' => 'required|string|max:200',
                'code_event'        => 'required|array|min:1',
                'code_event.*'      => 'string|max:200',
            ]);

            $response[] = app('App\Http\Controllers\ApiControllerRegister')->saveregister($request);  
            $response = collect($response)->toJson();
            $response = json_decode($response,true);
            $response = $response[0]['original'];
            $status = $response['status_message'];
            $note = $response['note'];

            // dd($status, $note, $response); 
            return redirect('/admin/menuregister')->with($status,$note);
        }
    }

    public function editregister(Request $request)
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
 
            $get_user = $this->get_user($request);         
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0]['menuregister'] == 'No' OR $level_user[0]['editregister'] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            $this->validate($request, [
                'nama_club'         => 'required|string|max:200',
                'nama_atlet'        => 'required|string|max:200',
                'nama_kejuaraan'    => 'required|string|max:200',
                'code_event'        => 'required|array|min:1',
                'code_event.*'      => 'string|max:200',
            ]);
            
            $request['code_data'] = $request['nomor_pendaftaran'];
            $response[] = app('App\Http\Controllers\ApiControllerRegister')->editregister($request);  
            $response = collect($response)->toJson();
            $response = json_decode($response,true);
            $response = $response[0]['original'];
            $status = $response['status_message'];
            $note = $response['note'];

            if($status == 'success'){
                return redirect('/admin/histroryregister')->with($status,$note);
            }else{
                return redirect('/admin/viewregister?d=' .$request->code_data)->with($status,$note);
            }
        }
    }

    public function verifiedregister(Request $request)
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
 
            $get_user = $this->get_user($request);         
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0]['menuregister'] == 'No' OR $level_user[0]['editregister'] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            // $this->validate($request, [
            //     'nama_club'         => 'required|string|max:200',
            //     'nama_atlet'        => 'required|string|max:200',
            //     'nama_kejuaraan'    => 'required|string|max:200',
            //     'code_event'        => 'required|array|min:1',
            //     'code_event.*'      => 'string|max:200',
            // ]);
            
            // $request['code_data'] = $request['nomor_pendaftaran'];
            $request['code_data'] = $request['d'];
            $response[] = app('App\Http\Controllers\ApiControllerRegister')->verifiedregister($request);  
            $response = collect($response)->toJson();
            $response = json_decode($response,true);
            $response = $response[0]['original'];
            $status = $response['status_message'];
            $note = $response['note'];

            return redirect('/admin/histroryregister')->with($status,$note);
        }
    }

    public function rejectedregister(Request $request)
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
 
            $get_user = $this->get_user($request);         
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0]['menuregister'] == 'No' OR $level_user[0]['editregister'] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            $request['code_data'] = $request['d'];
            $response[] = app('App\Http\Controllers\ApiControllerRegister')->rejectedregister($request);  
            $response = collect($response)->toJson();
            $response = json_decode($response,true);
            $response = $response[0]['original'];
            $status = $response['status_message'];
            $note = $response['note'];

            return redirect('/admin/histroryregister')->with($status,$note);
        }
    }

    // Kejuaraan
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
 
            $get_user = $this->get_user($request);         
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0]['listchampionship'] == 'No' OR $level_user[0]['newchampionship'] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            $this->validate($request, [
                'nama_kejuaraan'    => 'required|string|max:200',
                'lokasi'            => 'required|string|max:200',
                'jumlah_line'       => 'required|string|max:200',
                'tanggal_mulai'     => 'required|date',
                'tanggal_selesai'   => 'required|date|after_or_equal:tanggal_mulai',
            ]);

            $response[] = app('App\Http\Controllers\ApiControllerChampionship')->newchampionship($request);  
            $response = collect($response)->toJson();
            $response = json_decode($response,true);
            $response = $response[0]['original'];
            $status = $response['status_message'];
            $note = $response['note'];

            // dd($status, $note, $response); 

            if($status == 'success'){
                return redirect('/admin/listchampionship')->with($status,$note);
            }else{
                if(isset($response['note']['nama_kejuaraan'])){
                    return redirect('/admin/newchampionship')->with($status,$response['note']['nama_kejuaraan'][0]);
                }else{
                    return redirect('/admin/newchampionship')->with($status,$note);
                }
            }
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
 
            $get_user = $this->get_user($request);         
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0]['listchampionship'] == 'No' OR $level_user[0]['editchampionship'] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            $this->validate($request, [
                'nama_kejuaraan'    => 'required|string|max:200',
                'lokasi'            => 'required|string|max:200',
                'jumlah_line'       => 'required|string|max:200',
                'tanggal_mulai'     => 'required|date',
                'tanggal_selesai'   => 'required|date|after_or_equal:tanggal_mulai',
            ]);
            
            $request['code_data'] = $request['code_data'];
            $response[] = app('App\Http\Controllers\ApiControllerChampionship')->editchampionship($request);  
            $response = collect($response)->toJson();
            $response = json_decode($response,true);
            $response = $response[0]['original'];
            $status = $response['status_message'];
            $note = $response['note'];

            if($status == 'success'){
                return redirect('/admin/listchampionship')->with($status,$note);
            }else{
                if(isset($response['note']['nama_kejuaraan'])){                    
                    return redirect('/admin/editchampionship?d=' .$request->code_data)->with($status,$response['note']['nama_kejuaraan'][0]);
                }else{
                    return redirect('/admin/editchampionship?d=' .$request->code_data)->with($status,$note);
                }
            }
        }
    }

    public function deletechampionship(Request $request)
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
 
            $get_user = $this->get_user($request);         
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0]['listchampionship'] == 'No' OR $level_user[0]['deletechampionship'] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}
            
            $request['code_data'] = $request['d'];

            $response[] = app('App\Http\Controllers\ApiControllerChampionship')->deletechampionship($request);  
            $response = collect($response)->toJson();
            $response = json_decode($response,true);
            $response = $response[0]['original'];
            $status = $response['status_message'];
            $note = $response['note'];

            if($status == 'success'){
                return redirect('/admin/listchampionship')->with($status,$note);
            }else{
                return redirect('/admin/listchampionship')->with($status,$note);
            }
        }
    }

    // Event
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
 
            $get_user = $this->get_user($request);         
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0]['listevent'] == 'No' OR $level_user[0]['newevent'] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            $this->validate($request, [
                'code_event'        => 'required|string|max:200',
                'code_gaya'         => 'required|string|max:200',
                'jarak'             => 'required|string|max:200',
                'code_kategori'     => 'required|string|max:200',
                'gender'            => 'required|string|max:200',
                'tanggal'           => 'required|date',
                'code_kejuaraan'    => 'required|string|max:200',
            ]);

            $response[] = app('App\Http\Controllers\ApiControllerChampionship')->newevent($request);  
            $response = collect($response)->toJson();
            $response = json_decode($response,true);
            $response = $response[0]['original'];
            $status = $response['status_message'];
            $note = $response['note'];

            if($status == 'success'){
                return redirect('/admin/listevent')->with($status,$note);
            }else{
                return redirect('/admin/newevent')->with($status,$note);
            }
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
 
            $get_user = $this->get_user($request);         
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0]['listevent'] == 'No' OR $level_user[0]['editevent'] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            $this->validate($request, [
                'code_event'        => 'required|string|max:200',
                'code_gaya'         => 'required|string|max:200',
                'jarak'             => 'required|string|max:200',
                'code_kategori'     => 'required|string|max:200',
                'gender'            => 'required|string|max:200',
                'tanggal'           => 'required|date',
                'code_kejuaraan'    => 'required|string|max:200',
            ]);
            
            $request['code_data'] = $request['code_data'];
            $response[] = app('App\Http\Controllers\ApiControllerChampionship')->editevent($request);  
            $response = collect($response)->toJson();
            $response = json_decode($response,true);
            $response = $response[0]['original'];
            $status = $response['status_message'];
            $note = $response['note'];

            if($status == 'success'){
                return redirect('/admin/listevent')->with($status,$note);
            }else{
                return redirect('/admin/editevent?d=' .$request->code_data)->with($status,$note);
            }
        }
    }

    public function deleteevent(Request $request)
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
 
            $get_user = $this->get_user($request);         
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0]['listevent'] == 'No' OR $level_user[0]['deleteevent'] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}
            
            $request['code_data'] = $request['d'];

            $response[] = app('App\Http\Controllers\ApiControllerChampionship')->deleteevent($request);  
            $response = collect($response)->toJson();
            $response = json_decode($response,true);
            $response = $response[0]['original'];
            $status = $response['status_message'];
            $note = $response['note'];

            if($status == 'success'){
                return redirect('/admin/listevent')->with($status,$note);
            }else{
                return redirect('/admin/listevent')->with($status,$note);
            }
        }
    }

    public function generateheat(Request $request)
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
 
            $get_user = $this->get_user($request);         
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0]['listevent'] == 'No' OR $level_user[0]['editevent'] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}
            
            $request['code_data'] = $request['d'];

            $response[] = app('App\Http\Controllers\ApiControllerChampionship')->generateheat($request);  
            $response = collect($response)->toJson();
            $response = json_decode($response,true);
            $response = $response[0]['original'];
            $status = $response['status_message'];
            $note = $response['note'];

            return redirect('/admin/listevent')->with($status,$note);
        }
    }

    // Atlet
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
 
            $get_user = $this->get_user($request);         
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0]['listatlet'] == 'No' OR $level_user[0]['newatlet'] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            $this->validate($request, [
                'nis' => 'required|string|max:200',
                'nama' => 'required|string|max:200',
                'gender' => 'required|string|max:200',
                'tempat_lahir' => 'required|string|max:200',
                'code_club' => 'required|string|max:200',
            ]);

            $response[] = app('App\Http\Controllers\ApiControllerMasterData')->newatlet($request);  
            $response = collect($response)->toJson();
            $response = json_decode($response,true);
            $response = $response[0]['original'];
            $status = $response['status_message'];
            $note = $response['note'];

            // dd($status, $note, $response); 

            if($status == 'success'){
                return redirect('/admin/listatlet')->with($status,$note);
            }else{
                if(isset($response['note']['nis'])){
                    return redirect('/admin/newatlet')->with($status,$response['note']['nis'][0]);
                }else{
                    return redirect('/admin/newatlet')->with($status,$note);
                }
            }
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
 
            $get_user = $this->get_user($request);         
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0]['listatlet'] == 'No' OR $level_user[0]['editatlet'] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            $this->validate($request, [
                'nis'           => 'required|string|max:200',
                'nama'          => 'required|string|max:200',
                'gender'        => 'required|string|max:200',
                'tempat_lahir'  => 'required|string|max:200',
                'code_club'     => 'required|string|max:200',
            ]);
            
            $request['code_data'] = $request['code_data'];
            $response[] = app('App\Http\Controllers\ApiControllerMasterData')->editatlet($request);  
            $response = collect($response)->toJson();
            $response = json_decode($response,true);
            $response = $response[0]['original'];
            $status = $response['status_message'];
            $note = $response['note'];

            if($status == 'success'){
                return redirect('/admin/listatlet')->with($status,$note);
            }else{
                return redirect('/admin/editatlet?d=' .$request->code_data)->with($status,$note);
            }
        }
    }

    public function deleteatlet(Request $request)
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
 
            $get_user = $this->get_user($request);         
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0]['listatlet'] == 'No' OR $level_user[0]['deleteatlet'] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}
            
            $request['code_data'] = $request['d'];

            $response[] = app('App\Http\Controllers\ApiControllerMasterData')->deleteatlet($request);  
            $response = collect($response)->toJson();
            $response = json_decode($response,true);
            $response = $response[0]['original'];
            $status = $response['status_message'];
            $note = $response['note'];

            if($status == 'success'){
                return redirect('/admin/listatlet')->with($status,$note);
            }else{
                return redirect('/admin/listatlet')->with($status,$note);
            }
        }
    }

    // Club
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
 
            $get_user = $this->get_user($request);         
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0]['listclub'] == 'No' OR $level_user[0]['newclub'] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            $this->validate($request, [
                'nama_club' => 'required|string|max:200',
                'kota_asal' => 'required|string|max:200',
                'kontak' => 'required|string|max:200',
            ]);

            $response[] = app('App\Http\Controllers\ApiControllerMasterData')->newclub($request);  
            $response = collect($response)->toJson();
            $response = json_decode($response,true);
            $response = $response[0]['original'];
            $status = $response['status_message'];
            $note = $response['note'];

            // dd($status, $note, $response); 

            if($status == 'success'){
                return redirect('/admin/listclub')->with($status,$note);
            }else{
                if(isset($response['note']['nama_club'])){
                    return redirect('/admin/newclub')->with($status,$response['note']['nama_club'][0]);
                }else{
                    return redirect('/admin/newclub')->with($status,$note);
                }
            }
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
 
            $get_user = $this->get_user($request);         
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0]['listclub'] == 'No' OR $level_user[0]['editclub'] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            $this->validate($request, [
                'nama_club' => 'required|string|max:200',
                'kota_asal' => 'required|string|max:200',
                'kontak' => 'required|string|max:200',
            ]);
            
            $request['code_data'] = $request['code_data'];
            $response[] = app('App\Http\Controllers\ApiControllerMasterData')->editclub($request);  
            $response = collect($response)->toJson();
            $response = json_decode($response,true);
            $response = $response[0]['original'];
            $status = $response['status_message'];
            $note = $response['note'];

            if($status == 'success'){
                return redirect('/admin/listclub')->with($status,$note);
            }else{
                if(isset($response['note']['nama_club'])){                    
                    return redirect('/admin/editclub?d=' .$request->code_data)->with($status,$response['note']['nama_club'][0]);
                }else{
                    return redirect('/admin/editclub?d=' .$request->code_data)->with($status,$note);
                }
            }
        }
    }

    public function deleteclub(Request $request)
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
 
            $get_user = $this->get_user($request);         
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0]['listclub'] == 'No' OR $level_user[0]['deleteclub'] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}
            
            $request['code_data'] = $request['d'];

            $response[] = app('App\Http\Controllers\ApiControllerMasterData')->deleteclub($request);  
            $response = collect($response)->toJson();
            $response = json_decode($response,true);
            $response = $response[0]['original'];
            $status = $response['status_message'];
            $note = $response['note'];

            if($status == 'success'){
                return redirect('/admin/listclub')->with($status,$note);
            }else{
                return redirect('/admin/listclub')->with($status,$note);
            }
        }
    }

    // Kategori / Gaya Renang
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
 
            $get_user = $this->get_user($request);         
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0]['listkategori'] == 'No' OR $level_user[0]['newkategori'] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            $this->validate($request, [
                'nama_gaya' => 'required|string|max:200',
            ]);

            $response[] = app('App\Http\Controllers\ApiControllerMasterData')->newkategori($request);  
            $response = collect($response)->toJson();
            $response = json_decode($response,true);
            $response = $response[0]['original'];
            $status = $response['status_message'];
            $note = $response['note'];

            if($status == 'success'){
                return redirect('/admin/listkategori')->with($status,$note);
            }else{
                if(isset($response['note']['nama_gaya'])){
                    return redirect('/admin/newkategori')->with($status,$response['note']['nama_gaya'][0]);
                }else{
                    return redirect('/admin/newkategori')->with($status,$note);
                }
            }
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
 
            $get_user = $this->get_user($request);         
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0]['listkategori'] == 'No' OR $level_user[0]['editkategori'] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            $this->validate($request, [
                'nama_gaya' => 'required|string|max:200',
            ]);
            
            $request['code_data'] = $request['code_data'];
            $response[] = app('App\Http\Controllers\ApiControllerMasterData')->editkategori($request);  
            $response = collect($response)->toJson();
            $response = json_decode($response,true);
            $response = $response[0]['original'];
            $status = $response['status_message'];
            $note = $response['note'];

            if($status == 'success'){
                return redirect('/admin/listkategori')->with($status,$note);
            }else{
                if(isset($response['note']['nama'])){
                    return redirect('/admin/editkategori?d='.$request->code_data)->with($status,$response['note']['nama'][0]);
                }else{
                    return redirect('/admin/editkategori?d='.$request->code_data)->with($status,$note);
                }
            }
        }
    }

    public function deletekategori(Request $request)
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
 
            $get_user = $this->get_user($request);         
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0]['listkategori'] == 'No' OR $level_user[0]['deletekategori'] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}
            
            $request['code_data'] = $request['d'];

            $response[] = app('App\Http\Controllers\ApiControllerMasterData')->deletekategori($request);  
            $response = collect($response)->toJson();
            $response = json_decode($response,true);
            $response = $response[0]['original'];
            $status = $response['status_message'];
            $note = $response['note'];

            if($status == 'success'){
                return redirect('/admin/listkategori')->with($status,$note);
            }else{
                return redirect('/admin/listkategori')->with($status,$note);
            }
        }
    }   

    // Kelompok Umur
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
 
            $get_user = $this->get_user($request);         
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0]['listku'] == 'No' OR $level_user[0]['newku'] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            $this->validate($request, [
                'code_kelompok' => 'required|string|max:200',
                'nama_kelompok' => 'required|string|max:200',
                'min_usia' => 'required|string|max:200',
                'max_usia' => 'required|string|max:200',
            ]);

            $response[] = app('App\Http\Controllers\ApiControllerMasterData')->newku($request);  
            $response = collect($response)->toJson();
            $response = json_decode($response,true);
            $response = $response[0]['original'];
            $status = $response['status_message'];
            $note = $response['note'];

            if($status == 'success'){
                return redirect('/admin/listku')->with($status,$note);
            }else{
                return redirect('/admin/newku')->with($status,$note);
            }
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
 
            $get_user = $this->get_user($request);         
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0]['listku'] == 'No' OR $level_user[0]['editku'] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            $this->validate($request, [
                'code_kelompok' => 'required|string|max:200',
                'nama_kelompok' => 'required|string|max:200',
                'min_usia' => 'required|string|max:200',
                'max_usia' => 'required|string|max:200',
            ]);
            
            $request['code_data'] = $request['code_data'];
            $response[] = app('App\Http\Controllers\ApiControllerMasterData')->editku($request);  
            $response = collect($response)->toJson();
            $response = json_decode($response,true);
            $response = $response[0]['original'];
            $status = $response['status_message'];
            $note = $response['note'];

            if($status == 'success'){
                return redirect('/admin/listku')->with($status,$note);           
            }else{
                if(isset($response['note']['nama_kelompok'])){
                    return redirect('/admin/editku?d='.$request->code_data)->with($status,$response['note']['nama_kelompok'][0]);
                }else{
                    return redirect('/admin/editku?d='.$request->code_data)->with($status,$note);
                }
            }
        }
    }

    public function deleteku(Request $request)
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
 
            $get_user = $this->get_user($request);         
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0]['listku'] == 'No' OR $level_user[0]['deleteku'] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}
            
            $request['code_data'] = $request['d'];

            $response[] = app('App\Http\Controllers\ApiControllerMasterData')->deleteku($request);  
            $response = collect($response)->toJson();
            $response = json_decode($response,true);
            $response = $response[0]['original'];
            $status = $response['status_message'];
            $note = $response['note'];

            if($status == 'success'){
                return redirect('/admin/listku')->with($status,$note);
            }else{
                return redirect('/admin/listku')->with($status,$note);
            }
        }
    } 

    // Supplier
    public function newsupplier(Request $request)
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
 
            $get_user = $this->get_user($request);         
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0]['listsupplier'] == 'No' OR $level_user[0]['newsupplier'] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            $this->validate($request, [
                'nama' => 'required|string|max:200',
                'no_hp' => 'required|string|max:200',
                'alamat' => 'required|string',
            ]);

            $response[] = app('App\Http\Controllers\ApiControllerMasterData')->newsupplier($request);  
            $response = collect($response)->toJson();
            $response = json_decode($response,true);
            $response = $response[0]['original'];
            $status = $response['status_message'];
            $note = $response['note'];

            if($status == 'success'){
                return redirect('/admin/listsupplier')->with($status,$note);
            }else{
                if(isset($response['note']['nama'])){
                    return redirect('/admin/newsupplier')->with($status,$response['note']['nama'][0]);
                }else{
                    return redirect('/admin/newsupplier')->with($status,$note);
                }
            }
        }
    }

    public function editsupplier(Request $request)
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
 
            $get_user = $this->get_user($request);         
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0]['listsupplier'] == 'No' OR $level_user[0]['editsupplier'] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            $this->validate($request, [
                'nama' => 'required|string|max:200',
                'no_hp' => 'required|string|max:200',
                'alamat' => 'required|string',
            ]);
            
            $request['id'] = $request['id_data'];
            $response[] = app('App\Http\Controllers\ApiControllerMasterData')->editsupplier($request);  
            $response = collect($response)->toJson();
            $response = json_decode($response,true);
            $response = $response[0]['original'];
            $status = $response['status_message'];
            $note = $response['note'];

            if($status == 'success'){
                return redirect('/admin/listsupplier')->with($status,$note);
            }else{
                if(isset($response['note']['nama'])){
                    return redirect('/admin/editsupplier?d='.$request->id_data)->with($status,$response['note']['nama'][0]);
                }else{
                    return redirect('/admin/editsupplier?d='.$request->id_data)->with($status,$note);
                }
            }
        }
    }

    public function upstatussupplier(Request $request)
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
 
            $get_user = $this->get_user($request);         
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0]['listsupplier'] == 'No' OR $level_user[0]['editsupplier'] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}
            
            $request['id'] = $request['id'];

            $response[] = app('App\Http\Controllers\ApiControllerMasterData')->upstatussupplier($request);  
            $response = collect($response)->toJson();
            $response = json_decode($response,true);
            $response = $response[0]['original'];
            $status = $response['status_message'];
            $note = $response['note'];

            return response()->json(['status_message' => $status,'note' => $note]);
        }
    } 

    public function deletesupplier(Request $request)
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
 
            $get_user = $this->get_user($request);         
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0]['listsupplier'] == 'No' OR $level_user[0]['deletesupplier'] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}
            
            $request['id'] = $request['d'];

            $response[] = app('App\Http\Controllers\ApiControllerMasterData')->deletesupplier($request);  
            $response = collect($response)->toJson();
            $response = json_decode($response,true);
            $response = $response[0]['original'];
            $status = $response['status_message'];
            $note = $response['note'];

            if($status == 'success'){
                return redirect('/admin/listsupplier')->with($status,$note);
            }else{
                return redirect('/admin/listsupplier')->with($status,$note);
            }
        }
    }  

    // Pengguna    
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
 
            $get_user = $this->get_user($request);         
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0]['listusers'] == 'No' OR $level_user[0]['newusers'] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            $this->validate($request, [
                'full_name' => 'required|string|max:200',
                'phone_number' => 'required|string|max:200',
                'email' => 'required|string|email|max:200',
                'password' => 'required|min:1|max:200',
                'level' => 'required|string|max:30',
            ]);

            $response[] = app('App\Http\Controllers\ApiControllerMasterPengguna')->newusers($request);  
            $response = collect($response)->toJson();
            $response = json_decode($response,true);
            $response = $response[0]['original'];
            $status = $response['status_message'];
            $note = $response['note'];

            if($status == 'success'){
                return redirect('/admin/listusers')->with($status,$note);
            }else{
                if(isset($response['note']['email'])){
                    return redirect('/admin/newusers')->with($status,$response['note']['email'][0]);
                }else{
                    return redirect('/admin/newusers')->with($status,$note);
                }
            }
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
 
            $get_user = $this->get_user($request);         
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0]['listusers'] == 'No' OR $level_user[0]['editusers'] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            $this->validate($request, [
                'full_name' => 'required|string|max:200',
                'phone_number' => 'required|string|max:200',
                'email' => 'required|string|email|max:200',
                'level' => 'required|string|max:30',
                'status_data' => 'required|string|max:30',
            ]);
            
            $request['id'] = $request['id_data'];

            $response[] = app('App\Http\Controllers\ApiControllerMasterPengguna')->editusers($request);  
            $response = collect($response)->toJson();
            $response = json_decode($response,true);
            $response = $response[0]['original'];
            $status = $response['status_message'];
            $note = $response['note'];

            if($status == 'success'){
                return redirect('/admin/listusers')->with($status,$note);
            }else{
                if(isset($response['note']['email'])){
                    return redirect('/admin/editusers?d='.$request->id_data)->with($status,$response['note']['email'][0]);
                }else{
                    return redirect('/admin/editusers?d='.$request->id_data)->with($status,$note);
                }
            }
        }
    }

    public function deleteusers(Request $request)
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
 
            $get_user = $this->get_user($request);         
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0]['listusers'] == 'No' OR $level_user[0]['deleteusers'] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}
            
            $request['id'] = $request['d'];

            $response[] = app('App\Http\Controllers\ApiControllerMasterPengguna')->deleteusers($request);  
            $response = collect($response)->toJson();
            $response = json_decode($response,true);
            $response = $response[0]['original'];
            $status = $response['status_message'];
            $note = $response['note'];

            if($status == 'success'){
                return redirect('/admin/listusers')->with($status,$note);
            }else{
                return redirect('/admin/editusers?d='.$request->d)->with($status,$note);
            }
        }
    }

    // Level Pengguna 
    public function actionlevel(Request $request)
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
 
            $get_user = $this->get_user($request);         
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0]['levelusers'] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}

            $this->validate($request, [
                'level_name' => 'required|string',
            ]);

            $response[] = app('App\Http\Controllers\ApiControllerMasterPengguna')->actionlevel($request);  
            $response = collect($response)->toJson();
            $response = json_decode($response,true);
            $response = $response[0]['original'];
            $status = $response['status_message'];
            $note = $response['note'];

            if($status == 'success'){
                return redirect('/admin/levelusers')->with($status,$note);
            }else{
                if($request->get('code_data') != ''){
                    if(isset($response['note']['level_name'])){
                        return redirect('/admin/editlevel?d='.$request->code_data)->with($status,$response['note']['level_name'][0]);
                    }else{
                        return redirect('/admin/editlevel?d='.$request->code_data)->with($status,$note);
                    }
                }else{
                    if(isset($response['note']['level_name'])){
                        return redirect('/admin/newlevelusers')->with($status,$response['note']['level_name'][0]);
                    }else{
                        return redirect('/admin/newlevelusers')->with($status,$note);
                    }
                }
            }
        }
    }

    public function deletelevel(Request $request)
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
 
            $get_user = $this->get_user($request);         
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $res_user = $get_user['results'][0]['detailadmin'][0];
            $res_level_user = $get_user['results'][0]['leveladmin'][0];
            $nama_admin = substr($res_user['full_name'],0,15);
            if(strlen($nama_admin) > 15){$nama_admin = $nama_admin."...";}
            $request['nama_admin'] = $nama_admin;

            $list_akses = $this->get_akses($request);
            $level_user = array();
            for ($x = 0; $x <= count($res_level_user) - 1; $x++) {$access_rights[''.$res_level_user[$x]['data_menu'].''] = $res_level_user[$x]['access_rights'];}
            array_push($level_user, $access_rights);

            if($level_user[0]['levelusers'] == 'No' OR $level_user[0]['deletelevelusers'] == 'No'){return redirect('/admin/dash')->with('error','Tidak ada akses');}
            
            $request['code_data'] = $request['d'];

            $response[] = app('App\Http\Controllers\ApiControllerMasterPengguna')->deletelevel($request);  
            $response = collect($response)->toJson();
            $response = json_decode($response,true);
            $response = $response[0]['original'];
            $status = $response['status_message'];
            $note = $response['note'];

            if($status == 'success'){
                return redirect('/admin/levelusers')->with($status,$note);
            }else{
                return redirect('/admin/editlevel?d='.$request->d)->with($status,$note);
            }
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
 
            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $res_user = $get_user['results'][0]['detailadmin'][0];
    
            $this->validate($request, [
                'full_name' => 'required|string|max:200',
                'phone_number' => 'required|string|max:30',
                'email' => 'required|string|email|max:200',
            ]);
            
            $request['id'] = $res_user['id'];

            $response[] = app('App\Http\Controllers\ApiControllerMasterPengguna')->editadmin($request);  
            $response = collect($response)->toJson();
            $response = json_decode($response,true);
            $response = $response[0]['original'];
            $status = $response['status_message'];
            $note = $response['note'];

            if($status == 'success'){
                return redirect('/admin/editaccount')->with($status,$note);
            }else{
                if(isset($response['note']['email'])){
                    return redirect('/admin/editaccount')->with($status,$response['note']['email'][0]);
                }else{
                    return redirect('/admin/editaccount')->with($status,$note);
                }
            }
        }
    }

    public function editpassaccount(Request $request)
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
 
            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $res_user = $get_user['results'][0]['detailadmin'][0];
    
            $this->validate($request, [
                'old_password' => 'required|string|max:30',
                'new_password' => 'required|string|max:30',
            ]);

            if($request->old_password == $request->new_password){
                return redirect('/admin/editaccount')->with('error','Kata sandi baru harus berbeda dengan kata sandi lama.');
            }

            // if(!Hash::check($request->old_password,$res_user['password'])){
            //     return redirect('/admin/editaccount')->with('error','Kata sandi lama salah.');
            // }
            
            $request['id'] = $res_user['id'];

            $response[] = app('App\Http\Controllers\ApiControllerMasterPengguna')->editpassadmin($request);  
            $response = collect($response)->toJson();
            $response = json_decode($response,true);
            $response = $response[0]['original'];
            $status = $response['status_message'];
            $note = $response['note'];

            if($status == 'success'){
                return redirect('/admin/logout')->with($status,$note.', silahkan masuk kembali');
            }else{
                return redirect('/admin/editaccount')->with($status,$note);
            }
        }
    }

    // Pengaturan
    public function delmenu(Request $request)
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
 
            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $res_user = $get_user['results'][0]['detailadmin'][0];

            if($res_user['level'] == 'LV5677001'){
            
                $request['id'] = $request['d'];
    
                $response[] = app('App\Http\Controllers\ApiControllerPengaturan')->delmenu($request);  
                $response = collect($response)->toJson();
                $response = json_decode($response,true);
                $response = $response[0]['original'];
                $status = $response['status_message'];
                $note = $response['note'];
    
                if($status == 'success'){
                    return redirect('/admin/settingmenu')->with($status,$note);
                }else{
                    return redirect('/admin/settingmenu')->with($status,$note);
                }
            }else{
                return redirect('/admin/dash')->with('error','Tidak ada akses');
            }
        }
    }

    public function actionsettingmenu(Request $request)
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
 
            $get_user = $this->get_user($request);         
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $res_user = $get_user['results'][0]['detailadmin'][0];

            if($res_user['level'] == 'LV5677001'){
                $this->validate($request, [
                    'no_urut' => 'required|string',
                    'nama_menu' => 'required|string|max:200',
                    'nama_akses' => 'required|min:1|max:200',
                ]);

                $response[] = app('App\Http\Controllers\ApiControllerPengaturan')->actionsettingmenu($request);  
                $response = collect($response)->toJson();
                $response = json_decode($response,true);
                $response = $response[0]['original'];
                $status = $response['status_message'];
                $note = $response['note'];
    
                if($status == 'success'){
                    return redirect('/admin/settingmenu')->with($status,$note);
                }else{
                    if(isset($response['note']['nama_akses'])){
                        return redirect('/admin/settingmenu')->with($status,$response['note']['nama_akses'][0]);
                    }else{
                        return redirect('/admin/settingmenu')->with($status,$note);
                    }
                }
            }else{
                return redirect('/admin/dash')->with('error','Tidak ada akses');
            }
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
 
            $get_user = $this->get_user($request);         
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $res_user = $get_user['results'][0]['detailadmin'][0];

            $this->validate($request, [
                'nama' => 'required|string|max:200',
                'jenis' => 'required|string|max:200',
                'alamat' => 'required|string|max:200',
                'email' => 'required|email|max:200',
            ]);

            $response[] = app('App\Http\Controllers\ApiControllerPengaturan')->newcompany($request);  
            $response = collect($response)->toJson();
            $response = json_decode($response,true);
            $response = $response[0]['original'];
            $status = $response['status_message'];
            $note = $response['note'];

            if($status == 'success'){
                return redirect('/admin/listcompany')->with($status,$note);
            }else{
                if(isset($response['note']['nama'])){
                    return redirect('/admin/newcompany')->with($status,$response['note']['nama'][0]);
                }else{
                    return redirect('/admin/newcompany')->with($status,$note);
                }
            }
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
 
            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $res_user = $get_user['results'][0]['detailadmin'][0];

            $response[] = app('App\Http\Controllers\ApiControllerPengaturan')->editcompany($request);  
            $response = collect($response)->toJson();
            $response = json_decode($response,true);
            $response = $response[0]['original'];
            $status = $response['status_message'];
            $note = $response['note'];

            if($status == 'success'){
                return redirect('/admin/editcompany?d='.$request->id_data)->with($status,$note);
            }else{
                return redirect('/admin/editcompany?d='.$request->id_data)->with($status,$note);
            }
        }
    }

    public function deletecompany(Request $request)
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
 
            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $res_user = $get_user['results'][0]['detailadmin'][0];
            
            $request['id'] = $request['d'];

            $response[] = app('App\Http\Controllers\ApiControllerPengaturan')->deletecompany($request);  
            $response = collect($response)->toJson();
            $response = json_decode($response,true);
            $response = $response[0]['original'];
            $status = $response['status_message'];
            $note = $response['note'];

            if($status == 'success'){
                return redirect('/admin/listcompany')->with($status,$note);
            }else{
                return redirect('/admin/listcompany')->with($status,$note);
            }
        }
    }  

    public function uploadmanualbook(Request $request)
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
 
            $get_user = $this->get_user($request);           
            if(!$get_user OR $get_user['status_message'] == 'error'){return redirect('/admin/logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');}
            $res_user = $get_user['results'][0]['detailadmin'][0];

            $response[] = app('App\Http\Controllers\ApiControllerPengaturan')->uploadmanualbook($request);  
            $response = collect($response)->toJson();
            $response = json_decode($response,true);
            $response = $response[0]['original'];
            $status = $response['status_message'];
            $note = $response['note'];

            if($status == 'success'){
                return redirect("/admin/manualbook?d={$response['results']}")->with($status, $note);
            }else{
                return redirect("/admin/manualbook")->with($status,$note);
                // return redirect("/admin/manualbook?d=" . urlencode(json_encode($response['results'])))->with($status, $note);

            }
        }
    }
}
