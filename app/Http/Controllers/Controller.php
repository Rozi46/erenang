<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Carbon;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    protected $process;
    
    public function get_user($request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{ 
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;

            $get_user[] = app('App\Http\Controllers\ApiController')->getadmin($request);  
            $get_user = collect($get_user)->toJson();
            $get_user = json_decode($get_user,true);
            $get_user = $get_user[0]['original'];

            return $get_user;
        }
    }
    
    public function get_setting($request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{ 
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;

            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;

            $get_setting[] = app('App\Http\Controllers\ApiController')->getSetting($request);  
            $get_setting = collect($get_setting)->toJson();
            $get_setting = json_decode($get_setting,true);
            $get_setting = $get_setting[0]['original'];

            return $get_setting;
        }
    }

    public function get_akses($request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{ 
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;

            $list_akses[] = app('App\Http\Controllers\ApiControllerPengaturan')->getlevelakses($request);  
            $list_akses = collect($list_akses)->toJson();
            $list_akses = json_decode($list_akses,true);
            $list_akses = $list_akses[0]['original'];

            return $list_akses;
        }
    }

    public function get_op_level($request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{ 
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;

            $results[] = app('App\Http\Controllers\ApiControllerPengaturan')->listoplevel($request);  
            $results = collect($results)->toJson();
            $results = json_decode($results,true);
            $results = $results[0]['original'];

            return $results;
        }
    }

    // public function backup_database() 
    // {
    //     date_default_timezone_set('Asia/Jakarta');
    //     $filename = "backup-" . Carbon::now()->format('Y-m') . ".sql";        
    //     $command = "mysqldump  --host=" . env('DB_HOST') . " --user=" . env('DB_USERNAME') ." --password=" . env('DB_PASSWORD') . " " . env('DB_DATABASE') . " -c>$filename 2>&1";
    //     $returnVar = NULL;
    //     $output  = NULL;
    
    //     exec($command, $output, $returnVar);        
    // }

    public function backup_database()
    {
        date_default_timezone_set('Asia/Jakarta');
        $filename = "backup-" . \Carbon\Carbon::now()->format('Y-m') . ".sql";

        $host = env('DB_HOST');
        $port = env('DB_PORT', 5432);
        $database = env('DB_DATABASE');
        $username = env('DB_USERNAME');
        $password = env('DB_PASSWORD');

        // Format command untuk PostgreSQL
        $command = "PGPASSWORD=\"$password\" pg_dump -h $host -p $port -U $username -F p -c $database > $filename 2>&1";

        $returnVar = null;
        $output = null;

        exec($command, $output, $returnVar);

        if ($returnVar === 0) {
            return response()->json(['success' => true, 'file' => $filename]);
        } else {
            return response()->json(['success' => false, 'error' => $output]);
        }
    }

    public function restore_database($backupFile)
    {
        date_default_timezone_set('Asia/Jakarta');

        $host = env('DB_HOST');
        $port = env('DB_PORT', 5432);
        $database = env('DB_DATABASE');
        $username = env('DB_USERNAME');
        $password = env('DB_PASSWORD');

        // Pastikan file backup ada
        if (!file_exists($backupFile)) {
            return response()->json(['success' => false, 'error' => 'File backup tidak ditemukan']);
        }

        // Command restore PostgreSQL
        $command = "PGPASSWORD=\"$password\" psql -h $host -p $port -U $username -d $database -f $backupFile 2>&1";

        $returnVar = null;
        $output = null;

        exec($command, $output, $returnVar);

        if ($returnVar === 0) {
            return response()->json(['success' => true, 'message' => 'Database berhasil di-restore']);
        } else {
            return response()->json(['success' => false, 'error' => $output]);
        }
    }
    
    public function generateCode($length = 4, $type = 'letters') {
        switch ($type) {
            case 'letters': // huruf saja A-Z
                $pool = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                return substr(str_shuffle(str_repeat($pool, $length)), 0, $length);

            case 'numbers': // angka saja 0-9
                return str_pad(mt_rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);

            case 'mixed': // huruf + angka
                return strtoupper(Str::random($length));

            default:
                throw new InvalidArgumentException("Type harus 'letters', 'numbers', atau 'mixed'");
        }
    }
    // Contoh pemakaian
    // $kodeHuruf  = generateCode(4, 'letters'); // misal: "XZQP"
    // $kodeAngka  = generateCode(4, 'numbers'); // misal: "0385"
    // $kodeCampur = generateCode(6, 'mixed');   // misal: "A9C7XZ"

    public function get_op_gaya($request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{ 
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;

            $results[] = app('App\Http\Controllers\ApiControllerMasterData')->listopgaya($request);  
            $results = collect($results)->toJson();
            $results = json_decode($results,true);
            $results = $results[0]['original'];

            return $results;
        }
    }    

    public function get_op_ku($request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{ 
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;

            $results[] = app('App\Http\Controllers\ApiControllerMasterData')->listopku($request);  
            $results = collect($results)->toJson();
            $results = json_decode($results,true);
            $results = $results[0]['original'];

            return $results;
        }
    }  

    public function get_op_championship($request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{ 
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;

            $results[] = app('App\Http\Controllers\ApiControllerChampionship')->listopchampionship($request);  
            $results = collect($results)->toJson();
            $results = json_decode($results,true);
            $results = $results[0]['original'];

            return $results;
        }
    } 
    
    public function get_op_club($request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{ 
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;

            $results[] = app('App\Http\Controllers\ApiControllerMasterData')->listopclub($request);  
            $results = collect($results)->toJson();
            $results = json_decode($results,true);
            $results = $results[0]['original'];

            return $results;
        }
    }

    public function get_op_championshipRegister($request)
    {
    	if(!session()->has('key_token_renang') || !session()->has('admin_login_renang')){
    		return redirect('logout')->with('error','Terjadi kesalahan!!! silahkan hubungi kami');
    	}else{ 
            date_default_timezone_set('Asia/Jakarta');
            $url_api =  env('APP_API');
            $admin_login = session('admin_login_renang');
            $key_token = session('key_token_renang');
            $load_app = $request->load;
            $request['u'] = $admin_login;
            $request['token'] = $key_token;

            $results[] = app('App\Http\Controllers\ApiControllerRegister')->listopchampionship($request);  
            $results = collect($results)->toJson();
            $results = json_decode($results,true);
            $results = $results[0]['original'];

            return $results;
        }
    } 

    // penyusunan lane
    public function centeredLaneOrder(int $laneCount): array
    {
        $center = (int) ceil($laneCount / 2);

        $order = [$center];

        for ($i = 1; count($order) < $laneCount; $i++) {
            if ($center - $i >= 1) {
                $order[] = $center - $i;
            }
            if ($center + $i <= $laneCount) {
                $order[] = $center + $i;
            }
        }

        return $order;
    }
}
