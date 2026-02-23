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

class ApiControllerMasterPengguna extends Controller
{
    // Data Admin
    public function editadmin(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            if($request->nama != $viewadmin->nama){
                $validator = Validator::make($request->all(),['full_name'=>'required|min:1|max:30']);
                if($validator->fails()){return response()->json(['status_message' => 'error','note' => $validator->errors(),'results' => $object]);}
            }
            if($request->no_hp != $viewadmin->no_hp){
                $validator = Validator::make($request->all(),['phone_number'=>'required|min:1|max:30']);
                if($validator->fails()){return response()->json(['status_message' => 'error','note' => $validator->errors(),'results' => $object]);}
            }
            if($request->email != $viewadmin->email){
                $validator = Validator::make($request->all(),['email'=>'required|min:1|max:30|unique:db_users']);
                if($validator->fails()){return response()->json(['status_message' => 'error','note' => $validator->errors(),'results' => $object]);}
            }

            try {
                if ($request->hasFile('image_admin')) {
                    $this->validate($request, [
                        'image_admin' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2548'
                    ]);
                }

                DB::beginTransaction();

                User::where('id', $viewadmin->id)->update([
                    'full_name'     => $request->get('full_name'),
                    'phone_number'  => $request->get('phone_number'),
                    'email'         => $request->get('email'),
                ]);

                $imageName = null;
                if ($request->hasFile('image_admin')) {
                    $imageName = 'PP-'.$request->id.'-'.time().'.'.$request->image_admin->extension();
                    $request->image_admin->move(public_path('/themes/admin/AdminOne/image/upload/'), $imageName);

                    User::where('id', $viewadmin->id)->update([
                        'image' => $imageName,
                    ]);

                    if (!empty($viewadmin->image)) {
                        File::delete(public_path('/themes/admin/AdminOne/image/upload/'.$viewadmin->image));
                    }
                }

                $otp = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1);
                $newCodeData_activity = ltrim(Carbon::now()->format('Ymdhis') . $otp, '0');
                Activity::create([
                    'id' => Str::uuid(),
                    'code_data'     => $newCodeData_activity,
                    'code_user'     => $viewadmin->code_data,
                    'activity'      => 'Ubah data admin ['.$viewadmin->full_name.' - '.$viewadmin->code_data.']',
                    'code_company'  => $viewadmin->code_company,
                ]);

                DB::commit();
                return response()->json(['status_message' => 'success','note' => 'Data berhasil disimpan','results' => $imageName ?? 'Tanpa gambar']);

            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['status_message' => 'error','note' => 'Data gagal disimpan: ' . $e->getMessage(),'results' =>  $object]);
            }
        }
    }
    
    public function editpassadmin(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $old_password = $request->old_password;
            $password = $request->password;    
            $validator = Validator::make($request->all(), [
                'old_password' => 'required|string|max:30',
                'new_password' => 'required|string|max:30',
            ]);
    
            if($validator->fails()){
                return response()->json(['status_message' => 'error','note' => $validator->errors(),'results' => $object]);
            }
            
            if(Hash::check($request->old_password,$viewadmin->password)){
                $new_password = bcrypt($request->new_password); 
                
                try { 
                    DB::beginTransaction();
                    User::Where('code_company',$viewadmin->code_company)->where('id', $viewadmin->id)
                    ->update([
                        'password' => $new_password,
                    ]);

                    $otp = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1);
                    $newCodeData_activity = ltrim(Carbon::now()->format('Ymdhis') . $otp, '0');
                    Activity::create([
                        'id'            => Str::uuid(),
                        'code_data'     => $newCodeData_activity,
                        'code_user'     => $viewadmin->code_data,
                        'activity'      => 'Ubah password data admin ['.$viewadmin->full_name.' - '.$viewadmin->code_data.']',
                        'code_company'  => $viewadmin->code_company,
                    ]);

                    DB::commit();
                    return response()->json(['status_message' => 'success','note' => 'Data berhasil disimpan','results' => $object]);
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json(['status_message' => 'error','note' => 'Data gagal disimpan ' . $e->getMessage(),'results' => $object]);
                }
            }else{
                return response()->json(['status_message' => 'error','note' => 'Kata sandi salah','results' => $object]);
            }
        }
    } 

    // Data Pengguna
    public function listusers(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','users')->first();
            $sub_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','listusers')->first();
            $action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','exportusers')->first();
            if($request->type == 'export'){
                if($action->access_rights == 'No'){
                    return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
                }
            }

            if($menu->access_rights == 'No' OR $sub_menu->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }
            
            if($request->has('vd')){
                if($request->vd == ''){
                    $vd = '20';
                }else{
                    $vd = $request->vd;
                }
            }else{
                $vd = '20';
            }       

            if($viewadmin->level == 'LV5677001'){
                $results['list'] = User::with([
                        'company:id,code_data,nama_company',
                        'levelAdmin:id,code_data,level_name'
                    ])
                    ->where('tipe_login', 'User')
                    ->where('code_company', $viewadmin->code_company)
                    ->when($request->keysearch, function ($query) use ($request) {
                        $query->where(function ($q) use ($request) {
                            $q->where('full_name', 'ILIKE', "%{$request->keysearch}%")
                            ->orWhere('code_data', 'ILIKE', "%{$request->keysearch}%")
                            ->orWhere('phone_number', 'ILIKE', "%{$request->keysearch}%")
                            ->orWhere('email', 'ILIKE', "%{$request->keysearch}%");
                        });
                    })
                    ->orderBy('full_name', 'ASC')
                    ->paginate($vd ?? 20);
            }else{
                $results['list'] = User::with([
                        'company:id,code_data,nama_company',
                        'levelAdmin:id,code_data,level_name'
                    ])
                    ->where('tipe_login', 'User')                        
                    ->where('level', '!=', 'LV5677001')
                    ->where('code_company', $viewadmin->code_company)
                    ->when($request->keysearch, function ($query) use ($request) {
                        $query->where(function ($q) use ($request) {
                            $q->where('full_name', 'ILIKE', "%{$request->keysearch}%")
                            ->orWhere('code_data', 'ILIKE', "%{$request->keysearch}%")
                            ->orWhere('phone_number', 'ILIKE', "%{$request->keysearch}%")
                            ->orWhere('email', 'ILIKE', "%{$request->keysearch}%");
                        });
                    })
                    ->orderBy('full_name', 'ASC')
                    ->paginate($vd ?? 20);
            }
                
            return response()->json(['status_message' => 'success','note' => 'Proses data berhasil','count_all_data' => $results['list']->total(),'count_view_data' => $vd,'keysearch' => $request->keysearch,'results' => $results]);
        }
    }

    public function viewusers(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{ 
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','users')->first();
            $level_sub_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','listusers')->first();
            
            if($level_menu->access_rights == 'No' OR $level_sub_menu->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }
                      
            $dataadmin = User::where('code_company',$viewadmin->code_company)->where('id', $request->id)->first();
            if($dataadmin){
                $resultsdata['detailadmin'] = array();
                array_push($resultsdata['detailadmin'], $dataadmin);
                $leveladmin = User::where('code_data','=',$dataadmin->level)->get();
                $resultsdata['leveladmin'] = array();
                array_push($resultsdata['leveladmin'], $leveladmin);
                array_push($object, $resultsdata);
    
                return response()->json(['status_message' => 'success','note' => 'Proses data berhasil','results' => $object],200);
            }else{
                return response()->json(['status_message' => 'error','note' => 'Data tidak ditemukan','results' => $object]);
            }
        } 

    }

    public function newusers(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');        
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        $ceklevel = LevelAdmin::where('code_data', $request->level)->first();
        if(!$ceklevel){
            return response()->json(['status_message' => 'error','note' => 'Data level tidak terdaftar']);
        }else{
            if(!$viewadmin){
                return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
            }else{
                $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','listusers')->first();
                $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','newusers')->first();
                
                if($level_menu->access_rights == 'No' OR $level_action->access_rights == 'No'){
                    return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
                }

                $validator = Validator::make($request->all(), [
                    'full_name'     => 'required|string|max:200',
                    'phone_number'  => 'required|string|max:200',
                    'email'         => 'required|string|email|max:200|unique:db_users',
                    'password'      => 'required|min:1|max:200',
                    'level'         => 'required|string|max:30',
                ]);

                if($validator->fails()){return response()->json(['status_message' => 'error','note' => $validator->errors()]);}

                try {
                    DB::beginTransaction();

                    $otp = substr(str_shuffle(str_repeat('123456789', 4)), 0, 4);
                    $dataAll = User::orderBy('created_at', 'desc')->first();
                    $countData = User::count();

                    if ($countData > 0 && $dataAll && isset($dataAll->code_data)) {
                        $lastNumber = (int) substr($dataAll->code_data, -4);
                        $incrementedNumber = $lastNumber + 1;
                    } else {
                        $incrementedNumber = 1;
                    }

                    $formattedNumber = str_pad($incrementedNumber, 4, '0', STR_PAD_LEFT);
                    $newCodeData = 'US' . $otp . $formattedNumber;

                    $savedata = User::create([
                        'id'            => Str::uuid(),
                        'code_data'     => $newCodeData,
                        'full_name'     => $request->get('full_name'),
                        'email'         => $request->get('email'),
                        'phone_number'  => $request->get('phone_number'),
                        'password'      => bcrypt($request->password),
                        'level'         => $request->get('level'),
                        'image'         => 'no_img',
                        'status_data'   => 'Aktif',
                        'tipe_user'     => 'User',
                        'tipe_login'    => 'User',
                        'code_company'  => $viewadmin->code_company,
                    ]);

                    if (!$savedata) {
                        DB::rollBack();
                        return response()->json(['status_message' => 'error','note' => 'Data gagal disimpan', 'results' => $object], 500);
                    }

                    $otpAct = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1);
                    $newCodeData_activity = ltrim(Carbon::now()->format('Ymdhis') . $otpAct, '0');

                    Activity::create([
                        'id'          => Str::uuid(),
                        'code_data'   => $newCodeData_activity,
                        'code_user'   => $viewadmin->code_data ?? null,
                        'activity'    => 'Tambah data pengguna ['.$request->full_name.' - '.$newCodeData.']',
                        'code_company'=> $viewadmin->code_company ?? null,
                    ]);

                    DB::commit();
                    return response()->json(['status_message' => 'success','note' => 'Data berhasil disimpan','results' => $savedata], 200);
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan: ' . $e->getMessage(),'results' => $object], 500);
                }
            }         
        } 
    }

    public function editusers(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');        
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        $ceklevel = LevelAdmin::where('code_data', $request->level)->first();
        if(!$ceklevel){  
            return response()->json(['status_message' => 'error','note' => 'Data level tidak terdaftar']);    
        }else{  
            if(!$viewadmin){
                return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
            }else{
                $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','listusers')->first();
                $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','editusers')->first();
                
                if($level_menu->access_rights == 'No' OR $level_action->access_rights == 'No'){
                    return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
                }

                $getdata = User::where('code_company',$viewadmin->code_company)->where('id', $request->id)->first();
                if(!$getdata){
                    return response()->json(['status_message' => 'error','note' => 'Data tidak ditemukan','results' => $object]);
                }else{
                    $validator = Validator::make($request->all(), [
                        'full_name'     => 'required|string|max:200',
                        'phone_number'  => 'required|string|max:200',
                        'email'         => 'required|string|email|max:200',
                        'level'         => 'required|string|max:30',
                        'status_data'   => 'required|string|max:30',
                    ]);

                    if($validator->fails()){return response()->json(['status_message' => 'error','note' => $validator->errors()]);}

                    if($request->email != $getdata->email){
                        $validator = Validator::make($request->all(),['email'=>'required|string|email|max:200|unique:db_users']);
                        if($validator->fails()){
                            return response()->json(['status_message' => 'error','note' => $validator->errors(),'results' => $object]);
                        }
                    }

                    if($getdata->id == 'bd050931-d837-11eb-8038-204747ab6caa'){
                        return response()->json(['status_message' => 'error','note' => 'Data tidak bisa ubah','results' => $object]);
                    }else{
                        try {
                            DB::beginTransaction();

                            $updatedata = User::where('code_company',$viewadmin->code_company)->where('id', $request->get('id'))
                                ->update([
                                    'full_name'     => $request->get('full_name'),
                                    'phone_number'  => $request->get('phone_number'),
                                    'email'         => $request->get('email'),
                                    'level'         => $request->get('level'),
                                    'status_data'   => $request->get('status_data'),
                                ]);

                            if (!$updatedata) {
                                DB::rollBack();
                                return response()->json(['status_message' => 'error','note' => 'Data gagal disimpan','results' => $object]);
                            }

                            $otp = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1);
                            $newCodeData_activity = ltrim(Carbon::now()->format('Ymdhis') . $otp, '0');

                            Activity::create([
                                'id'           => Str::uuid(),
                                'code_data'    => $newCodeData_activity,
                                'code_user'    => $viewadmin->code_data,
                                'activity'     => 'Ubah data pengguna ['.$getdata->full_name.' - '.$getdata->code_data.']',
                                'code_company' => $viewadmin->code_company,
                            ]);

                            DB::commit();
                            return response()->json(['status_message' => 'success','note' => 'Data berhasil disimpan','id_data' => Str::uuid(),'results' => $object]);

                        } catch (\Exception $e) {
                            DB::rollBack();
                            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan: '.$e->getMessage(),'results' => $object], 500);
                        }
                    }                
                }            
            }         
        }
    }

    public function deleteusers(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','listusers')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','deleteusers')->first();
            if($level_menu->access_rights == 'No' OR $level_action->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }
            $dataadmin = User::Where('code_company',$viewadmin->code_company)->where('id', $request->id)->first();
            if(!$dataadmin){
                return response()->json(['status_message' => 'error','note' => 'Data tidak ditemukan','results' => $object]);
            }else{
                if($dataadmin->code_data == 'US35790001'){
                    return response()->json(['status_message' => 'error','note' => 'Data gagal dihapus','results' => $object]);
                }else{
                    try {
                        DB::beginTransaction();

                        $DelData = $dataadmin->delete();
                        if (!$DelData) {
                            DB::rollBack();
                            return response()->json(['status_message' => 'error','note' => 'Data gagal dihapus','results' => $object]);
                        }

                        $otp = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1);
                        $newCodeData_activity = ltrim(Carbon::now()->format('Ymdhis') . $otp, '0');

                        Activity::create([
                            'id'           => Str::uuid(),
                            'code_data'    => $newCodeData_activity,
                            'code_user'    => $viewadmin->code_data,
                            'activity'     => 'Hapus data pengguna ['.$dataadmin->full_name.' - '.$dataadmin->code_data.']',
                            'code_company' => $viewadmin->code_company,
                        ]);

                        // hapus file foto user kalau ada
                        if ($dataadmin->image && $dataadmin->image !== 'no_img') {
                            File::delete(public_path('/image/upload/'.$dataadmin->image));
                        }

                        DB::commit();
                        return response()->json(['status_message' => 'success','note' => 'Data berhasil dihapus','results' => $object]);

                    } catch (\Exception $e) {
                        DB::rollBack();
                        return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan: '.$e->getMessage(),'results' => $object], 500);
                    }
                }
            }
        }
    }

    // Data Level Pengguna
    public function listlevelusers(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','users')->first();
            $level_sub_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','levelusers')->first();
            
            if($level_menu->access_rights == 'No' OR $level_sub_menu->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            } 

            if($request->has('vd')){
                if($request->vd == ''){
                    $vd = '20';
                }else{
                    $vd = $request->vd;
                }
            }else{
                $vd = '20';
            }        

            if($viewadmin->level == 'LV5677001'){
                $results = DB::table('db_level_admin')
                    ->select('level_name',DB::raw('code_data'))
                    ->whereRaw('code_data ILIKE ?', ["%{$request->keysearch}%"])
                    ->orWhereRaw('level_name ILIKE ?', ["%{$request->keysearch}%"])
                    ->groupBy('level_name','code_data')
                    ->orderBy('level_name', 'ASC')
                    ->paginate($vd ?? 20);
            }else{
                $query = DB::table('db_level_admin')
                    ->select('level_name', 'code_data')
                    ->when($viewadmin->level != 'LV5677001', function ($q) {
                        $q->where('code_data', '!=', 'LV5677001');
                    })
                    ->when($request->keysearch, function ($q) use ($request) {
                        $q->where(function ($qq) use ($request) {
                            $qq->whereRaw('code_data ILIKE ?', ["%{$request->keysearch}%"])
                            ->orWhereRaw('level_name ILIKE ?', ["%{$request->keysearch}%"]);
                        });
                    })
                    ->groupBy('level_name','code_data')
                    ->orderBy('level_name', 'ASC');

                $results = $query->paginate($vd ?? 20);
            }
                
            return response()->json(['status_message' => 'success','note' => 'Proses data berhasil','count_all_data' => $results->total(),'count_view_data' => $vd,'keysearch' => $request->keysearch,'results' => $results]);
        }
    }

    public function viewlevel(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','users')->first();
            $level_sub_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','levelusers')->first();
            
            if($level_menu->access_rights == 'No' OR $level_sub_menu->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            } 
            $getdata = LevelAdmin::where('code_data', $request->code_data)->first();
            if($getdata){
                $count_used = User::where('level', $getdata->code_data)->count();
                $results = DB::table('db_level_admin')->where('code_data', $request->code_data)->orderBy('level_name', 'DESC')->get();
                    
                return response()->json(['status_message' => 'success','note' => 'Proses data berhasil','level_name' => $getdata->level_name,'code_data' => $getdata->code_data,'count_used' => $count_used,'results' => $results]);
            }else{
                return response()->json(['status_message' => 'error','note' => 'Data tidak ditemukan','results' => $object]);
            }
        }
    } 

    public function actionlevel(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();

        if(!$viewadmin){
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }

        // CEK AKSES MENU
        $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','users')->first();
        $level_sub_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','levelusers')->first();

        if(($level_menu->access_rights ?? 'No') === 'No' || ($level_sub_menu->access_rights ?? 'No') === 'No'){
            return response()->json(['status_message' => 'error','note' => 'Tidak ada akses']);
        }

        // CEK DATA LEVEL
        $cekdata = LevelAdmin::where('code_data', $request->code_data)->first();

        if($cekdata){
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','editlevelusers')->first();

            if(($level_action->access_rights ?? 'No') === 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses']);
            }

            if($request->level_name != $cekdata->level_name){
                $validator = Validator::make($request->all(),[
                    'level_name' => 'required|min:1|max:200|unique:db_level_admin,level_name'
                ]);

                if($validator->fails()){
                    return response()->json(['status_message' => 'error','note' => $validator->errors()]);
                }
            }

            $newCodeData = $request->code_data;

        }else{
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','newlevelusers')->first();

            if(($level_action->access_rights ?? 'No') === 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses']);
            }

            $validator = Validator::make($request->all(),[
                'level_name' => 'required|min:1|max:200|unique:db_level_admin,level_name'
            ]);

            if($validator->fails()){
                return response()->json(['status_message' => 'error','note' => $validator->errors()]);
            }

            // generate code level
            $otp = substr(str_shuffle(str_repeat('123456789',4)),0,4);

            $dataAll = LevelAdmin::orderBy('created_at','desc')->first();
            $countData = LevelAdmin::count();

            if($countData>0 && $dataAll && isset($dataAll->code_data)){
                $lastNumber = (int) substr($dataAll->code_data,-3);
                $incrementedNumber = $lastNumber+1;
            }else{
                $incrementedNumber = 1;
            }

            $formattedNumber = str_pad($incrementedNumber,3,'0',STR_PAD_LEFT);
            $newCodeData = 'LV'.$otp.$formattedNumber;
        }

        // AMBIL MENU DARI FORM
        $menus = collect($request->all())->except(['u','token','code_data','level_name','_token']);
        $menus = $menus->map(fn($v)=> $v==='Yes'?'Yes':'No');

        // SIMPAN MENU LEVEL
        foreach ($menus as $data_menu => $access){
            LevelAdmin::updateOrCreate(
                [
                    'code_data' => $newCodeData,
                    'data_menu' => $data_menu
                ],
                [   
                    'id'            => Str::uuid(), // ← WAJIB untuk insert baru
                    'level_name'    => $request->level_name,
                    'access_rights' => $access
                ]
            );
        }

        // ACTIVITY LOG
        $otp = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'),0,1);
        $newCodeData_activity = ltrim(Carbon::now()->format('Ymdhis').$otp,'0');

        Activity::create([
            'id' => Str::uuid(),
            'code_data' => $newCodeData_activity,
            'code_user' => $viewadmin->code_data ?? null,
            'activity' => $cekdata
                ? 'Ubah data level pengguna ['.$request->level_name.' - '.$newCodeData.']'
                : 'Tambah data level pengguna ['.$request->level_name.' - '.$newCodeData.']',
            'code_company' => $viewadmin->code_company ?? null
        ]);

        return response()->json(['status_message' => 'success','note' => 'Data berhasil disimpan','id_data' => $newCodeData]);
    }


    public function deletelevel(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','levelusers')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','deletelevelusers')->first();
            if($level_menu->access_rights == 'No' OR $level_action->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }

            $getdata = LevelAdmin::where('code_data', $request->code_data)->first();            
            if(!$getdata){
                return response()->json(['status_message' => 'error', 'note' => 'Data tidak ditemukan', 'results' => $object ]);
            }

            if($getdata->code_data == 'LV5677001'){
                return response()->json(['status_message' => 'error','note' => 'Data gagal dihapus','results' => $object]);
            }

            try {
                DB::beginTransaction();

                LevelAdmin::where('code_data', $request->code_data)->delete(); 

                $otp = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1);
                $newCodeData_activity = ltrim(Carbon::now()->format('Ymdhis') . $otp, '0');

                Activity::create([
                    'id'            => Str::uuid(),
                    'code_data'     => $newCodeData_activity,
                    'code_user'     => $viewadmin->code_data ?? null,
                    'activity'      => 'Hapus data level pengguna ['.$getdata->level_name.' - '.$getdata->code_data.']',
                    'code_company'  => $viewadmin->code_company ?? null,
                ]);

                DB::commit();
                return response()->json(['status_message' => 'success','note' => 'Data berhasil dihapus','results' => $object ]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['status_message' => 'error','note' => 'Data gagal dihapus: ' . $e->getMessage(),'results' => $object]);
            }
        }
    }

    // Data Aktifitas Pengguna
    public function activityusers(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{ 
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','users')->first();
            $level_sub_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','activityusers')->first(); 
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','exportactivityusers')->first();
            if($request->type == 'export'){
                if($level_action->access_rights == 'No'){
                    return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
                }
            }
            
            if($level_menu->access_rights == 'No' OR $level_sub_menu->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            } 
            if($request->has('vd')){
                if($request->vd == ''){
                    $vd = '20';
                }else{
                    $vd = $request->vd;
                }
            }else{
                $vd = '20';
            }
            
            $dateyear = Carbon::now()->modify("-1 year")->format('Y-m-d') . ' 00:00:00';
            $datefilterstart = Carbon::now()->modify("-30 days")->format('Y-m-d') . ' 00:00:00';
            $datefilterend = Carbon::now()->modify("+1 days")->format('Y-m-d') . ' 23:59:59';

            if($request->searchdate != ''){
                $searchdate = explode ("sd",$request->searchdate);
                $datefilterstart = Carbon::parse($searchdate[0])->format('Y-m-d') . ' 00:00:00';
                $datefilterend = Carbon::parse($searchdate[1])->format('Y-m-d') . ' 23:59:59';
            }     

            if($viewadmin->level == 'LV5677001'){
                $results = Activity::with([
                        'user:id,code_data,full_name,email',
                        'company:id,code_data'
                    ])
                    ->whereBetween('created_at', [$datefilterstart, $datefilterend])
                    ->where('code_company', $viewadmin->code_company)
                    ->where(function ($query) use ($request) {
                        $query->whereHas('user', function ($q) use ($request) {
                            $q->where('full_name', 'ILIKE', "%{$request->keysearch}%")
                            ->orWhere('code_data', 'ILIKE', "%{$request->keysearch}%")
                            ->orWhere('email', 'ILIKE', "%{$request->keysearch}%");
                        })
                        ->orWhere('activity', 'ILIKE', "%{$request->keysearch}%");
                    })
                    ->orderBy('created_at', 'DESC')
                    ->paginate($vd ?? 20);
            }else{
                $results = Activity::with([
                        'user:id,code_data,full_name,email,level',
                        'company:id,code_data'
                    ])
                    ->whereBetween('created_at', [$datefilterstart, $datefilterend])
                    ->where('code_company', $viewadmin->code_company)
                    ->when($viewadmin->level != 'LV5677001', function ($q) {
                        $q->whereHas('user', function ($uq) {
                            $uq->where('level', '!=', 'LV5677001');
                        });
                    })
                    ->when($request->keysearch, function ($q) use ($request) {
                        $q->where(function ($query) use ($request) {
                            $query->whereHas('user', function ($uq) use ($request) {
                                $uq->where('full_name', 'ILIKE', "%{$request->keysearch}%")
                                ->orWhere('code_data', 'ILIKE', "%{$request->keysearch}%")
                                ->orWhere('email', 'ILIKE', "%{$request->keysearch}%");
                            })
                            ->orWhere('activity', 'ILIKE', "%{$request->keysearch}%");
                        });
                    })
                    ->orderBy('created_at', 'DESC')
                    ->paginate($vd ?? 20);
            }
                
            return response()->json(['status_message' => 'success','note' => 'Proses data berhasil','count_all_data' => $results->total(),'count_view_data' => $vd,'keysearch' => $request->keysearch,'results' => $results]); 
        }
    }

    // Untuk isi data pengguna pada form
    public function listoppengguna(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $results = User::where('code_company',$viewadmin->code_company)->orderBy('full_name', 'ASC')->get();                
            return response()->json(['status_message' => 'success','results' => $results,'jmlhdata' => count($results)]);
        }
    }
}
