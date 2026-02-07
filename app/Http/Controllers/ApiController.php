<?php

namespace App\Http\Controllers;

require '../vendor/autoload.php';

use App\Http\Controllers\Controller;
use App\Models\{Setting, Company, User, LevelAdmin, ListAkses, Activity};
use Illuminate\Http\{Request, UploadedFile, Response};
use Illuminate\Support\Facades\{Hash, Validator, File, Http, Route, Session, Auth, DB, Lang};
use Illuminate\Support\{Carbon, Str};
use Illuminate\Database\Query\Builder;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Ramsey\Uuid\Uuid;

class ApiController extends Controller
{
    // Admin Login
    public function login(Request $request)
    {
        $url_api =  env('ART_API');
        $url_app =  env('APP_URL');
        $object = [];
        $email = $request->email;
        $password = $request->password;
        $validator = Validator::make($request->all(), [
            'email'     => 'required|min:1|max:200',
            'password'  => 'required|min:1|max:200',
        ]);

        if($validator->fails()){return response()->json(['status_message' => 'failed','note' => $validator->errors()]);}

        $credentials = $request->only('email', 'password');
        $getdata = User::where('email',$email)->first();
        $getstatusdata = User::where('email',$email)->where('status_data','Aktif')->first();

        if($getdata){
            if($getstatusdata){
                if(Hash::check($password,$getdata->password)){
                    $resultsdata['detailadmin'] = array();
                    array_push($resultsdata['detailadmin'], $getdata);
                    $leveladmin = LevelAdmin::where('code_data','=',$getdata->level)->get();
                    $resultsdata['leveladmin'] = array();
                    array_push($resultsdata['leveladmin'], $leveladmin);
                    array_push($object, $resultsdata);

                    if($request->url() == $url_app.'/admin/login'){
                        $link_akses = ''; // 'Online'
                    }else{
                        $link_akses = 'Offline';
                    }
                    
                    if($getdata->level != 'LV5677001'){
                        $otp = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1);
                        $newCodeData_activity = ltrim(Carbon::now()->format('Ymdhis') . $otp, '0');
                        Activity::create([
                            'id'            => Str::uuid(),
                            'code_data'     => $newCodeData_activity,
                            'code_user'     => $getdata->code_data,
                            'activity'      => 'Masuk ke sistem '.$link_akses,
                            'code_company'  => $getdata->code_company,
                        ]);
                    }

                    if (!$token = JWTAuth::attempt($credentials)) {
                        return response()->json(['error' => 'Unauthorized'], 401);
                    }

                    User::where('code_data', $getdata->code_data)->update([
                        'key_token' => $token,
                    ]);
                    return response()->json(['status_message' => 'success','note' => 'Berhasil masuk ke sistem','key_token' => $token,'results' => $object],200)->withCookie(cookie('jwt_token', $token, 120));
                }else{
                    return response()->json(['status_message' => 'failed','note' => 'Kata sandi salah','results' => $object]);
                }
            }else{
                return response()->json(['status_message' => 'failed','note' => 'Data pengguna tidak aktif','results' => $object]);
            }
        }else{
            return response()->json(['status_message' => 'failed','note' => 'Data tidak terdaftar','results' => $object]);
        }
    }

    public function logout(Request $request)
    {
        $url_api =  env('ART_API');
        $url_app =  env('APP_URL');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if($viewadmin && $request->token != null){
            if($request->url() == $url_app.'/admin/logout'){
                $link_akses = ''; // 'Online'
            }else{
                $link_akses = 'Offline';
            }
                    
            if($viewadmin->level != 'LV5677001'){
                $otp = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1);
                $newCodeData_activity = ltrim(Carbon::now()->format('Ymdhis') . $otp, '0');
                Activity::create([
                    'id'            => Str::uuid(),
                    'code_data'     => $newCodeData_activity,
                    'code_user'     => $viewadmin->code_data,
                    'activity'      => 'Keluar dari sistem '.$link_akses,
                    'code_company'  => $viewadmin->code_company,
                ]);
            }

            try {
                JWTAuth::invalidate(JWTAuth::getToken());
            } catch (TokenExpiredException $e) {
                // Token kadaluarsa, tidak perlu panic
            } catch (TokenInvalidException $e) {
                // Token tidak valid, bisa abaikan
            } catch (JWTException $e) {
                // Token tidak ditemukan atau error lainnya
            }

            User::where('id', $viewadmin->id)->update([
                'key_token' => null,
            ]);
    
            Session::flush();    
            return response()->json( ['status_message' => 'success','note' => 'Berhasil keluar ke sistem','code_data' => $viewadmin->code_data,]);
        }else{
            return response()->json( ['status_message' => 'failed','note' => 'Terjadi kesalahan saat keluar ke sistem','code_data' => $object]);
        }                
    }

    public function getdash(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'failed','note' => 'Terjadi kesalahan saat proses data']);   
        }else{   
            $thn_now = Carbon::now()->format('Y');
            $bln_now = Carbon::now()->format('m');
            $hari_now = Carbon::now()->format('d');

            $results['thn_now'] = $thn_now;
            $results['bln_now'] = $bln_now;

            if($request->has('vd')){
                if($request->vd == ''){
                    $vd = '20';
                }else{
                    $vd = $request->vd;
                }
            }else{
                $vd = '20';
            } 

            // $results['total_hutang'] = Hutang::Where('code_company',$viewadmin->code_company)->sum('sisa'); 
            // $results['total_hutang_count'] = Hutang::Where('code_company',$viewadmin->code_company)->Where('sisa', '<>', 0)->count();        
            // $results['count_pembelian'] = Pembelian::Where('code_company',$viewadmin->code_company)->count(); 

            // $results['summary_pembelian_hari'] = Pembelian::Where('code_company',$viewadmin->code_company)
            //     ->whereDay('tanggal',$hari_now)
            //     ->whereMonth('tanggal',$bln_now)
            //     ->whereYear('tanggal',$thn_now)
            //     ->where(function($query) use ($request) {
            //         $query->Where('status_transaksi','Proses')
            //         ->OrWhere('status_transaksi','Finish');
            //     })
            //     ->Where('status_transaksi','!=','Input')->count();

            // $results['summary_pembelian_nilai_hari'] = Pembelian::Where('code_company',$viewadmin->code_company)
            //     ->whereDay('tanggal',$hari_now)
            //     ->whereMonth('tanggal',$bln_now)
            //     ->whereYear('tanggal',$thn_now)
            //     ->where(function($query) use ($request) {
            //         $query->Where('status_transaksi','Proses')
            //         ->OrWhere('status_transaksi','Finish');
            //     })
            //     ->Where('status_transaksi','!=','Input')
            //     ->sum('grand_total');
                    
            // $results['summary_pembelian_bln'] = Pembelian::Where('code_company',$viewadmin->code_company)
            //     ->whereMonth('tanggal',$bln_now)
            //     ->whereYear('tanggal',$thn_now)
            //     ->where(function($query) use ($request) {
            //         $query->Where('status_transaksi','Proses')
            //         ->OrWhere('status_transaksi','Finish');
            //     })
            //     ->Where('status_transaksi','!=','Input')->count();

            // $results['summary_pembelian_nilai_bln'] = Pembelian::Where('code_company',$viewadmin->code_company)
            //     ->whereMonth('tanggal',$bln_now)
            //     ->whereYear('tanggal',$thn_now)
            //     ->where(function($query) use ($request) {
            //         $query->Where('status_transaksi','Proses')
            //         ->OrWhere('status_transaksi','Finish');
            //     })
            //     ->Where('status_transaksi','!=','Input')
            //     ->sum('grand_total');
            
            // $results['summary_pembelian_thn'] = Pembelian::Where('code_company',$viewadmin->code_company)
            //     ->whereYear('tanggal',$thn_now)
            //     ->where(function($query) use ($request) {
            //         $query->Where('status_transaksi','Proses')
            //         ->OrWhere('status_transaksi','Finish');
            //     })
            //     ->Where('status_transaksi','!=','Input')->count();

            // $results['summary_pembelian_nilai_thn'] = Pembelian::Where('code_company',$viewadmin->code_company)
            //     ->whereYear('tanggal',$thn_now)
            //     ->where(function($query) use ($request) {
            //         $query->Where('status_transaksi','Proses')
            //         ->OrWhere('status_transaksi','Finish');
            //     })
            //     ->Where('status_transaksi','!=','Input')
            //     ->sum('grand_total');
                   
            // $results['total_piutang'] = Piutang::Where('code_company',$viewadmin->code_company)->sum('sisa');
            // $results['total_piutang_count'] = Piutang::Where('code_company',$viewadmin->code_company)->Where('sisa', '<>', 0)->count(); 
            // $results['count_penjualan'] = Penjualan::Where('code_company',$viewadmin->code_company)->count();

            // $results['summary_penjualan_hari'] = Penjualan::Where('code_company',$viewadmin->code_company)
            //     ->whereDay('tanggal',$hari_now)
            //     ->whereMonth('tanggal',$bln_now)
            //     ->whereYear('tanggal',$thn_now)
            //     ->where(function($query) use ($request) {
            //         $query->Where('status_transaksi','Proses')
            //         ->OrWhere('status_transaksi','Finish');
            //     })
            //     ->Where('status_transaksi','!=','Input')->count();

            // $results['summary_penjualan_nilai_hari'] = Penjualan::Where('code_company',$viewadmin->code_company)
            //     ->whereDay('tanggal',$hari_now)
            //     ->whereMonth('tanggal',$bln_now)
            //     ->whereYear('tanggal',$thn_now)
            //     ->where(function($query) use ($request) {
            //         $query->Where('status_transaksi','Proses')
            //         ->OrWhere('status_transaksi','Finish');
            //     })
            //     ->Where('status_transaksi','!=','Input')
            //     ->sum('grand_total');
                    
            // $results['summary_penjualan_bln'] = Penjualan::Where('code_company',$viewadmin->code_company)
            //     ->whereMonth('tanggal',$bln_now)
            //     ->whereYear('tanggal',$thn_now)
            //     ->where(function($query) use ($request) {
            //         $query->Where('status_transaksi','Proses')
            //         ->OrWhere('status_transaksi','Finish');
            //     })
            //     ->Where('status_transaksi','!=','Input')->count();

            // $results['summary_penjualan_nilai_bln'] = Penjualan::Where('code_company',$viewadmin->code_company)
            //     ->whereMonth('tanggal',$bln_now)
            //     ->whereYear('tanggal',$thn_now)
            //     ->where(function($query) use ($request) {
            //         $query->Where('status_transaksi','Proses')
            //         ->OrWhere('status_transaksi','Finish');
            //     })
            //     ->Where('status_transaksi','!=','Input')
            //     ->sum('grand_total');
            
            // $results['summary_penjualan_thn'] = Penjualan::Where('code_company',$viewadmin->code_company)
            //     ->whereYear('tanggal',$thn_now)
            //     ->where(function($query) use ($request) {
            //         $query->Where('status_transaksi','Proses')
            //         ->OrWhere('status_transaksi','Finish');
            //     })
            //     ->Where('status_transaksi','!=','Input')->count();

            // $results['summary_penjualan_nilai_thn'] = Penjualan::Where('code_company',$viewadmin->code_company)
            //     ->whereYear('tanggal',$thn_now)
            //     ->where(function($query) use ($request) {
            //         $query->Where('status_transaksi','Proses')
            //         ->OrWhere('status_transaksi','Finish');
            //     })
            //     ->Where('status_transaksi','!=','Input')
            //     ->sum('grand_total');
            
            //     // for ($x = 1; $x <= 31; $x++) {
            //     //     $results['po_'.$x] = Pembelian::Where('code_company',$viewadmin->code_company)->Where('status_transaksi','!=','Input')->Where('tanggal',$thn_now.'-'.$bln_now.'-'.$x)->count();
            //     //     $results['so_'.$x] = Penjualan::Where('code_company',$viewadmin->code_company)->Where('status_transaksi','!=','Input')->Where('tanggal',$thn_now.'-'.$bln_now.'-'.$x)->count();
            //     //     $results['summary_po_'.$x] = Pembelian::Where('code_company',$viewadmin->code_company)->Where('status_transaksi','!=','Input')->Where('tanggal',$thn_now.'-'.$bln_now.'-'.$x)->sum('grand_total');
            //     //     $results['summary_so_'.$x] = Penjualan::Where('code_company',$viewadmin->code_company)->Where('status_transaksi','!=','Input')->Where('tanggal',$thn_now.'-'.$bln_now.'-'.$x)->sum('grand_total');
            //     //     $results['po_thn'.$x] = Pembelian::Where('code_company',$viewadmin->code_company)->Where('status_transaksi','!=','Input')->WhereYear('tanggal',$thn_now)->WhereMonth('tanggal',$x)->count();
            //     //     $results['so_thn'.$x] = Penjualan::Where('code_company',$viewadmin->code_company)->Where('status_transaksi','!=','Input')->WhereYear('tanggal',$thn_now)->WhereMonth('tanggal',$x)->count();
            //     //     $results['summary_po_thn'.$x] = Pembelian::Where('code_company',$viewadmin->code_company)->Where('status_transaksi','!=','Input')->WhereYear('tanggal',$thn_now)->WhereMonth('tanggal',$x)->sum('grand_total');
            //     //     $results['summary_so_thn'.$x] = Penjualan::Where('code_company',$viewadmin->code_company)->Where('status_transaksi','!=','Input')->WhereYear('tanggal',$thn_now)->WhereMonth('tanggal',$x)->sum('grand_total');
            //     // }

            // $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $bln_now, $thn_now);

            // for ($x = 1; $x <= $daysInMonth; $x++) {
            //     $date = sprintf('%04d-%02d-%02d', $thn_now, $bln_now, $x);

            //     $results['po_'.$x] = Pembelian::where('code_company', $viewadmin->code_company)
            //         ->where('status_transaksi', '!=', 'Input')
            //         ->where('tanggal', $date)
            //         ->count();

            //     $results['so_'.$x] = Penjualan::where('code_company', $viewadmin->code_company)
            //         ->where('status_transaksi', '!=', 'Input')
            //         ->where('tanggal', $date)
            //         ->count();

            //     $results['summary_po_'.$x] = Pembelian::where('code_company', $viewadmin->code_company)
            //         ->where('status_transaksi', '!=', 'Input')
            //         ->where('tanggal', $date)
            //         ->sum('grand_total');

            //     $results['summary_so_'.$x] = Penjualan::where('code_company', $viewadmin->code_company)
            //         ->where('status_transaksi', '!=', 'Input')
            //         ->where('tanggal', $date)
            //         ->sum('grand_total');
            // }

            // for ($x = 1; $x <= 12; $x++) {
            //     $results['po_thn'.$x] = Pembelian::where('code_company',$viewadmin->code_company)
            //         ->where('status_transaksi','!=','Input')
            //         ->whereYear('tanggal',$thn_now)
            //         ->whereMonth('tanggal',$x)
            //         ->count();

            //     $results['so_thn'.$x] = Penjualan::where('code_company',$viewadmin->code_company)
            //         ->where('status_transaksi','!=','Input')
            //         ->whereYear('tanggal',$thn_now)
            //         ->whereMonth('tanggal',$x)
            //         ->count();

            //     $results['summary_po_thn'.$x] = Pembelian::where('code_company',$viewadmin->code_company)
            //         ->where('status_transaksi','!=','Input')
            //         ->whereYear('tanggal',$thn_now)
            //         ->whereMonth('tanggal',$x)
            //         ->sum('grand_total');

            //     $results['summary_so_thn'.$x] = Penjualan::where('code_company',$viewadmin->code_company)
            //         ->where('status_transaksi','!=','Input')
            //         ->whereYear('tanggal',$thn_now)
            //         ->whereMonth('tanggal',$x)
            //         ->sum('grand_total');
            // }

            // $results['total_summary_po'] = Pembelian::Where('code_company',$viewadmin->code_company)->Where('status_transaksi','!=','Input')->WhereYear('tanggal',$thn_now)->sum('grand_total');
            // $results['total_summary_so'] = Penjualan::Where('code_company',$viewadmin->code_company)->Where('status_transaksi','!=','Input')->WhereYear('tanggal',$thn_now)->sum('grand_total');
            // $results['total_po_thn'] = Pembelian::Where('code_company',$viewadmin->code_company)->Where('status_transaksi','!=','Input')->WhereYear('tanggal',$thn_now)->count();
            // $results['total_so_thn'] = Penjualan::Where('code_company',$viewadmin->code_company)->Where('status_transaksi','!=','Input')->WhereYear('tanggal',$thn_now)->count();

            return response()->json(['status_message' => 'success','results' => $results]);
        }
    }

    public function getadmin(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'failed','note' => 'Terjadi kesalahan saat proses data']);   
        }else{ 
            $object['data_company'] = Company::select('nama_company','jenis','alamat','keterangan','foto')->where('code_data', $viewadmin->code_company)->first();
            $resultsdata['detailadmin'] = array();
            array_push($resultsdata['detailadmin'], $viewadmin);

            $leveladmin = LevelAdmin::where('code_data','=',$viewadmin->level)->get();
            $resultsdata['leveladmin'] = array();
            array_push($resultsdata['leveladmin'], $leveladmin);
            array_push($object, $resultsdata);

            return response()->json(['status_message' => 'success','results' => $object],200);
        } 
    }  

    public function getSetting(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'failed','note' => 'Terjadi kesalahan saat proses data']);   
        }else{ 
            $object['data_setting'] = Setting::where('id','1')->first();
            return response()->json(['status_message' => 'success','results' => $object],200);
        } 
    } 
}
