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

            // $results['list'] = DB::table('db_users')
            //     ->where('tipe_login','User')
            //     ->where('code_company',$viewadmin->code_company)
            //     ->where(function($query) use ($request) {
            //         $query->whereRaw('full_name ILIKE ?', ["%{$request->keysearch}%"])
            //         ->orWhereRaw('code_data ILIKE ?', ["%{$request->keysearch}%"])
            //         ->orWhereRaw('phone_number ILIKE ?', ["%{$request->keysearch}%"])
            //         ->orWhereRaw('email ILIKE ?', ["%{$request->keysearch}%"]);
            //     })
            //     ->orderBy('full_name', 'ASC')
            //     ->paginate($vd ?? 20);

            // foreach($results['list'] as $key => $list){
            //     $results['detail_perusahaan'][$list->id] = Company::select('nama_company')->where('code_data', $list->code_company)->first();
            //     $results['detail_level'][$list->id] = LevelAdmin::where('code_data', $list->level)->first();
            // }

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

            $results = DB::table('db_level_admin')
                ->select('level_name',DB::raw('code_data'))
                ->whereRaw('code_data ILIKE ?', ["%{$request->keysearch}%"])
                ->orWhereRaw('level_name ILIKE ?', ["%{$request->keysearch}%"])
                ->groupBy('level_name','code_data')
                ->orderBy('level_name', 'ASC')
                ->paginate($vd ?? 20);
                
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
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','users')->first();
            $level_sub_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','levelusers')->first();

            if($level_menu->access_rights == 'No' OR $level_sub_menu->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }
                
            $cekdata = LevelAdmin::where('code_data', $request->code_data)->first();

            if($cekdata){
                $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','editlevelusers')->first();
            
                if($level_action->access_rights == 'No'){
                    return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
                }

                if($request->level_name != $cekdata->level_name){
                    $validator = Validator::make($request->all(),['level_name'=>'required|min:1|max:200|unique:db_level_admin']);
                    if($validator->fails()){return response()->json(['status_message' => 'error','note' => $validator->errors(),'results' => $object]);}
                }
                $newCodeData = $request->code_data;
            }else{
                $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','newlevelusers')->first();
            
                if($level_action->access_rights == 'No'){
                    return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
                }

                $validator = Validator::make($request->all(), [
                    'level_name' => 'required|min:1|max:200|unique:db_level_admin',
                ]);

                if($validator->fails()){return response()->json(['status_message' => 'error','note' => $validator->errors()]);}                

                $otp = substr(str_shuffle(str_repeat('123456789', 4)), 0, 4);
                $dataAll = LevelAdmin::orderBy('created_at', 'desc')->first();
                $countData = LevelAdmin::count();

                if ($countData > 0 && $dataAll && isset($dataAll->code_data)) {
                    $lastNumber = (int) substr($dataAll->code_data, -3);
                    $incrementedNumber = $lastNumber + 1;
                } else {
                    $incrementedNumber = 1;
                }

                $formattedNumber = str_pad($incrementedNumber, 3, '0', STR_PAD_LEFT);
                $newCodeData = 'LV' . $otp . $formattedNumber;
            }

            $users = $request->users;

            $listusers = $request->listusers;
            $newusers = $request->newusers;
            $editusers = $request->editusers;
            $deleteusers = $request->deleteusers;
            $exportusers = $request->exportusers;

            $levelusers = $request->levelusers;
            $newlevelusers = $request->newlevelusers;
            $editlevelusers = $request->editlevelusers;
            $deletelevelusers = $request->deletelevelusers;

            $activityusers = $request->activityusers;
            $exportactivityusers = $request->exportactivityusers;

            if($users == 'No'){
                $listusers = 'No';
                $newusers = 'No';
                $editusers = 'No';
                $deleteusers = 'No';
                $exportusers = 'No';
                
                $levelusers = 'No';
                $newlevelusers = 'No';
                $editlevelusers = 'No';
                $deletelevelusers = 'No';
                
                $activityusers = 'No';
                $exportactivityusers = 'No';
            }

            if($listusers == 'No'){
                $newusers = 'No';
                $editusers = 'No';
                $deleteusers = 'No';
                $exportusers = 'No';
            }

            if($levelusers == 'No'){
                $newlevelusers = 'No';
                $editlevelusers = 'No';
                $deletelevelusers = 'No';
            }

            if($activityusers == 'No'){
                $exportactivityusers = 'No';
            }

            $masterdata = $request->masterdata;

            $listatlet= $request->listatlet;
            $newatlet = $request->newatlet;
            $editatlet = $request->editatlet;
            $deleteatlet = $request->deleteatlet;
            $exportatlet = $request->exportatlet;

            $listbarang= $request->listbarang;
            $newbarang = $request->newbarang;
            $editbarang = $request->editbarang;
            $deletebarang = $request->deletebarang;
            $exportbarang = $request->exportbarang;

            $listjasa= $request->listjasa;
            $newjasa = $request->newjasa;
            $editjasa = $request->editjasa;
            $deletejasa = $request->deletejasa;
            $exportjasa = $request->exportjasa;

            $listsatuan = $request->listsatuan;
            $newsatuan = $request->newsatuan;
            $editsatuan = $request->editsatuan;
            $deletesatuan = $request->deletesatuan;
            $exportsatuan = $request->exportsatuan;

            $listkategori = $request->listkategori;
            $newkategori = $request->newkategori;
            $editkategori = $request->editkategori;
            $deletekategori = $request->deletekategori;
            $exportkategori = $request->exportkategori;

            $listmerk = $request->listmerk;
            $newmerk = $request->newmerk;
            $editmerk = $request->editmerk;
            $deletemerk = $request->deletemerk;
            $exportmerk = $request->exportmerk;

            $listsupplier = $request->listsupplier;
            $newsupplier = $request->newsupplier;
            $editsupplier = $request->editsupplier;
            $deletesupplier = $request->deletesupplier;
            $exportsupplier = $request->exportsupplier;

            $listcustomer = $request->listcustomer;
            $newcustomer = $request->newcustomer;
            $editcustomer = $request->editcustomer;
            $deletecustomer = $request->deletecustomer;
            $exportcustomer = $request->exportcustomer;

            $listkaryawan = $request->listkaryawan;
            $newkaryawan = $request->newkaryawan;
            $editkaryawan = $request->editkaryawan;
            $deletekaryawan = $request->deletekaryawan;
            $exportkaryawan = $request->exportkaryawan;

            $listgudang= $request->listgudang;
            $newgudang = $request->newgudang;
            $editgudang = $request->editgudang;
            $deletegudang = $request->deletegudang;
            $exportgudang = $request->exportgudang;

            $listcabang= $request->listcabang;
            $newcabang = $request->newcabang;
            $editcabang = $request->editcabang;
            $deletecabang = $request->deletecabang;
            $exportcabang = $request->exportcabang;

            if($masterdata == 'No'){
                $listatlet= 'No';
                $newatlet = 'No';
                $editatlet = 'No';
                $deleteatlet = 'No';

                $listbarang= 'No';
                $newbarang = 'No';
                $editbarang = 'No';
                $deletebarang = 'No';

                $listjasa= 'No';
                $newjasa = 'No';
                $editjasa = 'No';
                $deletejasa = 'No';
    
                $listsatuan = 'No';
                $newsatuan = 'No';
                $editsatuan = 'No';
                $deletesatuan = 'No';
    
                $listkategori = 'No';
                $newkategori = 'No';
                $editkategori = 'No';
                $deletekategori = 'No';
                $exportkategori = 'No';
    
                $listmerk = 'No';
                $newmerk = 'No';
                $editmerk = 'No';
                $deletemerk = 'No';
                $exportmerk = 'No';
    
                $listsupplier = 'No';
                $newsupplier = 'No';
                $editsupplier = 'No';
                $deletesupplier = 'No';
                $exportsupplier = 'No';
    
                $listcustomer = 'No';
                $newcustomer = 'No';
                $editcustomer = 'No';
                $deletecustomer = 'No';
                $exportcustomer = 'No';
    
                $listkaryawan = 'No';
                $newkaryawan = 'No';
                $editkaryawan = 'No';
                $deletekaryawan = 'No';
                $exportkaryawan = 'No';
    
                $listgudang= 'No';
                $newgudang = 'No';
                $editgudang = 'No';
                $deletegudang = 'No';
                $exportgudang = 'No';
    
                $listcabang= 'No';
                $newcabang = 'No';
                $editcabang = 'No';
                $deletecabang = 'No';
                $exportcabang = 'No';
            }

            if($listatlet == 'No'){
                $newatlet = 'No';
                $editatlet = 'No';
                $deleteatlet = 'No';    
            }

            if($listbarang == 'No'){
                $newbarang = 'No';
                $editbarang = 'No';
                $deletebarang = 'No';    
            }

            if($listjasa == 'No'){
                $newjasa = 'No';
                $editjasa = 'No';
                $deletejasa = 'No';    
            }
            
            if($listsatuan == 'No'){
                $newsatuan = 'No';
                $editsatuan = 'No';
                $deletesatuan = 'No';   
            }

            if($listkategori == 'No'){
                $newkategori = 'No';
                $editkategori = 'No';
                $deletekategori = 'No';
                $exportkategori = 'No';
            }
            
            if($listmerk == 'No'){
                $newmerk = 'No';
                $editmerk = 'No';
                $deletemerk = 'No';
                $exportmerk = 'No';
            }
            
            if($listsupplier == 'No'){
                $newsupplier = 'No';
                $editsupplier = 'No';
                $deletepegawai = 'No';
                $exportsupplier = 'No';
            }
            
            if($listcustomer == 'No'){
                $newcustomer = 'No';
                $editcustomer = 'No';
                $deletecustomer = 'No';
                $exportcustomer = 'No';
            }
            
            if($listkaryawan == 'No'){
                $newkaryawan = 'No';
                $editkaryawan = 'No';
                $deletekaryawan = 'No';
                $exportkaryawan = 'No';
            }
            
            if($listgudang == 'No'){
                $newgudang = 'No';
                $editgudang = 'No';
                $deletegudang = 'No';
                $exportgudang = 'No';
            }
            
            if($listcabang == 'No'){
                $newcabang = 'No';
                $editcabang = 'No';
                $deletecabang = 'No';
                $exportcabang = 'No';
            }

            $menufinance = $request->menufinance;

            $menupenerimaankas = $request->menupenerimaankas;
            $inputpenerimaankas = $request->inputpenerimaankas;
            $historypenerimaankas = $request->historypenerimaankas;
            $exportpenerimaankas = $request->exportpenerimaankas;

            $menupengeluarankas = $request->menupengeluarankas;
            $inputpengeluarankas = $request->inputpengeluarankas;
            $historypengeluarankas = $request->historypengeluarankas;
            $exportpengeluarankas = $request->exportpengeluarankas;

            $menupembayaranhutang = $request->menupembayaranhutang;
            $inputpembayaranhutang = $request->inputpembayaranhutang;
            $historypembayaranhutang = $request->historypembayaranhutang;
            $exportpembayaranhutang = $request->exportpembayaranhutang;

            $menupembayaranpiutang = $request->menupembayaranpiutang;
            $inputpembayaranpiutang = $request->inputpembayaranpiutang;
            $historypembayaranpiutang = $request->historypembayaranpiutang;
            $exportpembayaranpiutang = $request->exportpembayaranpiutang;
            
            $historykas = $request->historykas;
            $exportkas = $request->exportkas;

            $menuhutang = $request->menuhutang;
            $exportlisthutang = $request->exportlisthutang;
            $exportkartuhutang = $request->exportkartuhutang;

            $menutagihan = $request->menutagihan;
            $exportlisttagihan = $request->exportlisttagihan;
            $exportkartupiutang = $request->exportkartupiutang;

            $menuppn = $request->menutagihan;
            $exportppn = $request->exportppn;

            $rekappembelianpenjualan = $request->rekappembelianpenjualan;
            $exportrekappembelianpenjualan = $request->exportrekappembelianpenjualan;

            if($menufinance == 'No'){
                $menupenerimaankas = 'No';
                $inputpenerimaankas = 'No';
                $historypenerimaankas = 'No';
                $exportpenerimaankas = 'No';

                $menupengeluarankas = 'No';
                $inputpengeluarankas = 'No';
                $historypengeluarankas = 'No';
                $exportpengeluarankas = 'No';
    
                $menupembayaranhutang = 'No';
                $inputpembayaranhutang = 'No';
                $historypembayaranhutang = 'No';
                $exportpembayaranhutang = 'No';
    
                $menupembayaranpiutang = 'No';
                $inputpembayaranpiutang = 'No';
                $historypembayaranpiutang = 'No';
                $exportpembayaranpiutang = 'No';
            
                $historykas = 'No';
                $exportkas = 'No';

                $menuhutang = 'No';
                $exportlisthutang = 'No';
                $exportkartuhutang = 'No';

                $menutagihan = 'No';
                $exportlisttagihan = 'No';
                $exportkartupiutang = 'No';

                $menuppn = 'No';
                $exportppn = 'No';

                $rekappembelianpenjualan = 'No';
                $exportrekappembelianpenjualan = 'No';
            }
            
            if($menupenerimaankas == 'No'){
                $inputpenerimaankas = 'No';
                $historypenerimaankas = 'No';
                $exportpenerimaankas = 'No';
            }
            
            if($menupengeluarankas == 'No'){
                $inputpengeluarankas = 'No';
                $historypengeluarankas = 'No';
                $exportpengeluarankas = 'No';
            }
            
            if($menupembayaranhutang == 'No'){
                $inputpembayaranhutang = 'No';
                $historypembayaranhutang = 'No';
                $exportpembayaranhutang = 'No';
            }
            
            if($menupembayaranpiutang == 'No'){
                $inputpembayaranpiutang = 'No';
                $historypembayaranpiutang = 'No';
                $exportpembayaranpiutang = 'No';
            }
            
            if($historykas == 'No'){
                $exportkas = 'No';
            }
            
            if($menuhutang == 'No'){
                $exportlisthutang = 'No';
                $exportkartuhutang = 'No';
            }
            
            if($menutagihan == 'No'){
                $exportlisttagihan = 'No';
                $exportkartupiutang = 'No';
            }
            
            if($menuppn == 'No'){
                $exportppn = 'No';
            }
            
            if($rekappembelianpenjualan == 'No'){
                $exportrekappembelianpenjualan = 'No';
            }

            $menugudang = $request->menugudang;

            $menupenerimaanbarang = $request->menupenerimaanbarang;
            $inputpenerimaanbarang = $request->inputpenerimaanbarang;
            $historypenerimaanbarang = $request->historypenerimaanbarang;
            $exportpenerimaanbarang = $request->exportpenerimaanbarang;

            $menupengirimanbarang = $request->menupengirimanbarang;
            $inputpengirimanbarang  = $request->inputpengirimanbarang;
            $historypengirimanbarang  = $request->historypengirimanbarang;
            $exportpengirimanbarang  = $request->exportpengirimanbarang;

            $penyesuaianstockbarang = $request->penyesuaianstockbarang;
            $inputpenyesuaianstockbarang  = $request->inputpenyesuaianstockbarang;
            $historypenyesuaianstockbarang  = $request->historypenyesuaianstockbarang;
            $exportpenyesuaianstockbarang  = $request->exportpenyesuaianstockbarang;

            $historystockbarang = $request->historystockbarang;
            $exporthistorystockbarang = $request->exporthistorystockbarang;

            $persediaanbarang = $request->persediaanbarang;
            $exportpersediaanbarang = $request->exportpersediaanbarang;

            $mutasikirim = $request->mutasikirim;
            $inputmutasikirim = $request->inputmutasikirim;
            $historymutasikirim = $request->historymutasikirim;
            $exportmutasikirim = $request->exportmutasikirim;

            $mutasiterima = $request->mutasiterima;
            $inputmutasiterima = $request->inputmutasiterima;
            $historymutasiterima = $request->historymutasiterima;
            $exportmutasiterima = $request->exportmutasiterima;

            if($menugudang == 'No'){
                $menupenerimaanbarang = 'No';
                $inputpenerimaanbarang = 'No';
                $historypenerimaanbarang = 'No';
                $exportpenerimaanbarang = 'No';
    
                $menupengirimanbarang = 'No';
                $inputpengirimanbarang  = 'No';
                $historypengirimanbarang  = 'No';
                $exportpengirimanbarang  = 'No';
            }

            if($menupenerimaanbarang == 'No'){
                $inputpenerimaanbarang = 'No';
                $historypenerimaanbarang = 'No';
                $exportpenerimaanbarang = 'No';
            }

            if($menupengirimanbarang == 'No'){
                $inputpengirimanbarang  = 'No';
                $historypengirimanbarang  = 'No';
                $exportpengirimanbarang  = 'No';
            }

            if($penyesuaianstockbarang == 'No'){
                $inputpenyesuaianstockbarang  = 'No';
                $historypenyesuaianstockbarang  = 'No';
                $exportpenyesuaianstockbarang  = 'No';
            }

            if($historystockbarang == 'No'){
                $exporthistorystockbarang  = 'No';
            }

            if($persediaanbarang == 'No'){
                $exportpersediaanbarang  = 'No';
            }

            if($mutasikirim == 'No'){
                $inputmutasikirim  = 'No';
                $historymutasikirim  = 'No';
                $exportmutasikirim  = 'No';
            }

            if($mutasiterima == 'No'){
                $inputmutasiterima  = 'No';
                $historymutasiterima  = 'No';
                $exportmutasiterima  = 'No';
            }

            $menupenjualan = $request->menupenjualan;

            $menupenjualanbarang = $request->menupenjualanbarang;
            $inputpenjualanbarang = $request->inputpenjualanbarang;
            $historypenjualanbarang = $request->historypenjualanbarang;
            $exportpenjualanbarang = $request->exportpenjualanbarang;

            $menupenjualanretur = $request->menupenjualanretur;
            $inputpenjualanretur= $request->inputpenjualanretur;
            $historypenjualanretur = $request->historypenjualanretur;
            $exportpenjualanretur = $request->exportpenjualanretur;

            if($menupenjualan == 'No'){
                $menupenjualanbarang = 'No';
                $inputpenjualanbarang = 'No';
                $historypenjualanbarang = 'No';
                $exportpenjualanbarang = 'No';
    
                $menupenjualanretur = 'No';
                $inputpenjualanretur  = 'No';
                $historypenjualanretur  = 'No';
                $exportpenjualanretur  = 'No';
            }

            if($menupenjualanbarang == 'No'){
                $inputpenjualanbarang = 'No';
                $historypenjualanbarang = 'No';
                $exportpenjualanbarang = 'No';
            }

            if($menupenjualanretur == 'No'){
                $inputpenjualanretur  = 'No';
                $historypenjualanretur  = 'No';
                $exportpenjualanretur  = 'No';
            }

            $menupembelian = $request->menupembelian;

            $menupembelianbarang = $request->menupembelianbarang;
            $inputpembelianbarang = $request->inputpembelianbarang;
            $historypembelianbarang = $request->historypembelianbarang;
            $exportpembelianbarang = $request->exportpembelianbarang;

            $menupembelianretur = $request->menupembelianretur;
            $inputpembelianretur= $request->inputpembelianretur;
            $historypembelianretur = $request->historypembelianretur;
            $exportpembelianretur = $request->exportpembelianretur;

            if($menupembelian == 'No'){
                $menupembelianbarang = 'No';
                $inputpembelianbarang = 'No';
                $historypembelianbarang = 'No';
                $exportpembelianbarang = 'No';
    
                $menupembelianretur = 'No';
                $inputpembelianretur  = 'No';
                $historypembelianretur  = 'No';
                $exportpembelianretur  = 'No';
            }

            if($menupembelianbarang == 'No'){
                $inputpembelianbarang = 'No';
                $historypembelianbarang = 'No';
                $exportpembelianbarang = 'No';
            }

            if($menupembelianretur == 'No'){
                $inputpembelianretur  = 'No';
                $historypembelianretur  = 'No';
                $exportpembelianretur  = 'No';
            }

            $datamenu = array(
                "exportpembelianretur"=>"$exportpembelianretur",
                "historypembelianretur"=>"$historypembelianretur",
                "inputpembelianretur"=>"$inputpembelianretur",
                "menupembelianretur"=>"$menupembelianretur",

                "exportpembelianbarang"=>"$exportpembelianbarang",
                "historypembelianbarang"=>"$historypembelianbarang",
                "inputpembelianbarang"=>"$inputpembelianbarang",
                "menupembelianbarang"=>"$menupembelianbarang",
                "menupembelian"=>"$menupembelian",

                "exportpenjualanretur"=>"$exportpenjualanretur",
                "historypenjualanretur"=>"$historypenjualanretur",
                "inputpenjualanretur"=>"$inputpenjualanretur",
                "menupenjualanretur"=>"$menupenjualanretur",

                "exportpenjualanbarang"=>"$exportpenjualanbarang",
                "historypenjualanbarang"=>"$historypenjualanbarang",
                "inputpenjualanbarang"=>"$inputpenjualanbarang",
                "menupenjualanbarang"=>"$menupenjualanbarang",
                "menupenjualan"=>"$menupenjualan", 

                "exportmutasiterima"=>"$exportmutasiterima",
                "historymutasiterima"=>"$historymutasiterima",
                "inputmutasiterima"=>"$inputmutasiterima",
                "mutasiterima"=>"$mutasiterima",

                "exportmutasikirim"=>"$exportmutasikirim",
                "historymutasikirim"=>"$historymutasikirim",
                "inputmutasikirim"=>"$inputmutasikirim",
                "mutasikirim"=>"$mutasikirim",
                
                "exportpersediaanbarang"=>"$exportpersediaanbarang",
                "persediaanbarang"=>"$persediaanbarang",

                "exporthistorystockbarang"=>"$exporthistorystockbarang",
                "historystockbarang"=>"$historystockbarang",

                "exportpenyesuaianstockbarang"=>"$exportpenyesuaianstockbarang",
                "historypenyesuaianstockbarang"=>"$historypenyesuaianstockbarang",
                "inputpenyesuaianstockbarang"=>"$inputpenyesuaianstockbarang",
                "penyesuaianstockbarang"=>"$penyesuaianstockbarang",

                "exportpengirimanbarang"=>"$exportpengirimanbarang",
                "historypengirimanbarang"=>"$historypengirimanbarang",
                "inputpengirimanbarang"=>"$inputpengirimanbarang",
                "menupengirimanbarang"=>"$menupengirimanbarang",

                "exportpenerimaanbarang"=>"$exportpenerimaanbarang",
                "historypenerimaanbarang"=>"$historypenerimaanbarang",
                "inputpenerimaanbarang"=>"$inputpenerimaanbarang",
                "menupenerimaanbarang"=>"$menupenerimaanbarang",
                "menugudang"=>"$menugudang", 
                
                "exportrekappembelianpenjualan"=>"$exportrekappembelianpenjualan",
                "rekappembelianpenjualan"=>"$rekappembelianpenjualan",            
                
                "exportppn"=>"$exportppn",
                "menuppn"=>"$menuppn",       
                 
                "exportkartupiutang"=>"$exportkartupiutang",
                "exportlisttagihan"=>"$exportlisttagihan",  
                "menutagihan"=>"$menutagihan",           
                 
                "exportkartuhutang"=>"$exportkartuhutang",       
                "exportlisthutang"=>"$exportlisthutang", 
                "menuhutang"=>"$menuhutang",         
                
                "exportkas"=>"$exportkas",
                "historykas"=>"$historykas",
                
                "exportpembayaranpiutang"=>"$exportpembayaranpiutang",
                "historypembayaranpiutang"=>"$historypembayaranpiutang",
                "inputpembayaranpiutang"=>"$inputpembayaranpiutang",
                "menupembayaranpiutang"=>"$menupembayaranpiutang",

                "exportpembayaranhutang"=>"$exportpembayaranhutang",
                "historypembayaranhutang"=>"$historypembayaranhutang",
                "inputpembayaranhutang"=>"$inputpembayaranhutang",
                "menupembayaranhutang"=>"$menupembayaranhutang",

                "exportpengeluarankas"=>"$exportpengeluarankas",
                "historypengeluarankas"=>"$historypengeluarankas",
                "inputpengeluarankas"=>"$inputpengeluarankas",
                "menupengeluarankas"=>"$menupengeluarankas",

                "exportpenerimaankas"=>"$exportpenerimaankas",
                "historypengeluarankas"=>"$historypengeluarankas",
                "inputpenerimaankas"=>"$inputpenerimaankas",
                "menupenerimaankas"=>"$menupenerimaankas",
                "menufinance"=>"$menufinance",

                "exportcabang"=>"$exportcabang",
                "deletecabang"=>"$deletecabang",
                "editcabang"=>"$editcabang",
                "newcabang"=>"$newcabang",
                "listcabang"=>"$listcabang",

                "exportgudang"=>"$exportgudang",
                "deletegudang"=>"$deletegudang",
                "editgudang"=>"$editgudang",
                "newgudang"=>"$newgudang",
                "listgudang"=>"$listgudang",

                "exportkaryawan"=>"$exportkaryawan",
                "deletekaryawan"=>"$deletekaryawan",
                "editkaryawan"=>"$editkaryawan",
                "newkaryawan"=>"$newkaryawan",
                "listkaryawan"=>"$listkaryawan",

                "exportcustomer"=>"$exportcustomer",
                "deletecustomer"=>"$deletecustomer",
                "editcustomer"=>"$editcustomer",
                "newcustomer"=>"$newcustomer",
                "listcustomer"=>"$listcustomer",

                "exportsupplier"=>"$exportsupplier",
                "deletesupplier"=>"$deletesupplier",
                "editsupplier"=>"$editsupplier",
                "newsupplier"=>"$newsupplier",
                "listsupplier"=>"$listsupplier",

                "exportmerk"=>"$exportmerk",
                "deletemerk"=>"$deletemerk",
                "editmerk"=>"$editmerk",
                "newmerk"=>"$newmerk",
                "listmerk"=>"$listmerk",

                "exportkategori"=>"$exportkategori",
                "deletekategori"=>"$deletekategori",
                "editkategori"=>"$editkategori",
                "newkategori"=>"$newkategori",
                "listkategori"=>"$listkategori",

                "exportsatuan"=>"$exportsatuan",
                "deletesatuan"=>"$deletesatuan",
                "editsatuan"=>"$editsatuan",
                "newsatuan"=>"$newsatuan",
                "listsatuan"=>"$listsatuan",

                "exportjasa"=>"$exportjasa",
                "deletejasa"=>"$deletejasa",
                "editjasa"=>"$editjasa",
                "newjasa"=>"$newjasa",
                "listjasa"=>"$listjasa",

                "exportbarang"=>"$exportbarang",
                "deletebarang"=>"$deletebarang",
                "editbarang"=>"$editbarang",
                "newbarang"=>"$newbarang",
                "listbarang"=>"$listbarang",

                "exportatlet"=>"$exportatlet",
                "deleteatlet"=>"$deleteatlet",
                "editatlet"=>"$editatlet",
                "newatlet"=>"$newatlet",
                "listatlet"=>"$listatlet",

                "masterdata"=>"$masterdata",

                "exportactivityusers"=>"$exportactivityusers",
                "activityusers"=>"$activityusers",

                "deletelevelusers"=>"$deletelevelusers",
                "editlevelusers"=>"$editlevelusers",
                "newlevelusers"=>"$newlevelusers",
                "levelusers"=>"$levelusers",

                "exportusers"=>"$exportusers",
                "deleteusers"=>"$deleteusers",
                "editusers"=>"$editusers",
                "newusers"=>"$newusers",
                "listusers"=>"$listusers",

                "users"=>"$users",
            );
         
            if($cekdata){
                foreach ($datamenu as $data_menu => $access_rights) {
                    LevelAdmin::where('code_data', $newCodeData)->where('data_menu', $data_menu)
                    ->update([
                        'level_name'    => $request->get('level_name'),
                        'access_rights' => $access_rights,
                    ]);
                }

                $otp = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1);
                $newCodeData_activity = ltrim(Carbon::now()->format('Ymdhis') . $otp, '0');

                Activity::create([
                    'id'            => Str::uuid(),
                    'code_data'     => $newCodeData_activity,
                    'code_user'     => $viewadmin->code_data ?? null,
                    'activity'      => 'Ubah data level pengguna ['.$cekdata->level_name.' - '.$cekdata->code_data.']',
                    'code_company'  => $viewadmin->code_company ?? null,
                ]);
            }else{
                foreach ($datamenu as $data_menu => $access_rights) {                    
                    LevelAdmin::create([
                        'id'            => Str::uuid(),
                        'code_data'     => $newCodeData,
                        'level_name'    => $request->get('level_name'),
                        'data_menu'     => $data_menu,
                        'access_rights' => $access_rights
                    ]);
                }

                $otp = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1);
                $newCodeData_activity = ltrim(Carbon::now()->format('Ymdhis') . $otp, '0');

                Activity::create([
                    'id'            => Str::uuid(),
                    'code_data'     => $newCodeData_activity,
                    'code_user'     => $viewadmin->code_data ?? null,
                    'activity'      => 'Tambah data level pengguna ['.$request->level_name.' - '.$newCodeData.']',
                    'code_company'  => $viewadmin->code_company ?? null,
                ]);
            }
            return response()->json(['status_message' => 'success','note' => 'Data berhasil disimpan','id_data' => Str::uuid(),'results' => $object]);
        }
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

            // query mentah
            // $results = DB::table('db_activity')
            //     ->join('db_users', 'db_activity.code_user', '=', 'db_users.code_data')
            //     ->select('db_activity.created_at','db_users.code_data','db_users.full_name','db_activity.activity')
            //     ->whereBetween('db_activity.created_at', [$datefilterstart, $datefilterend])
            //     ->Where('db_users.code_company',$viewadmin->code_company)
            //     ->where(function($query) use ($request) {
            //         $query->whereRaw('db_users.full_name ILIKE ?', ["%{$request->keysearch}%"])
            //         ->orWhereRaw('db_users.code_data ILIKE ?', ["%{$request->keysearch}%"])
            //         ->orWhereRaw('db_users.email ILIKE ?', ["%{$request->keysearch}%"])
            //         ->orWhereRaw('db_activity.activity ILIKE ?', ["%{$request->keysearch}%"]);
            //     })
            //     ->orderBy('db_activity.created_at', 'DESC')
            //     ->paginate($vd ?? 20);

            // eloquen
            $results = Activity::with([
                    'user:id,code_data,full_name,email',
                    'company:id,code_data'
                ])
                ->whereBetween('created_at', [$datefilterstart, $datefilterend])
                ->where('code_company', $viewadmin->code_company)
                ->where(function ($query) use ($request) {
                    // whereHas -> Cari di relasi
                    $query->whereHas('user', function ($q) use ($request) {
                        $q->where('full_name', 'ILIKE', "%{$request->keysearch}%")
                        ->orWhere('code_data', 'ILIKE', "%{$request->keysearch}%")
                        ->orWhere('email', 'ILIKE', "%{$request->keysearch}%");
                    })
                    ->orWhere('activity', 'ILIKE', "%{$request->keysearch}%");
                })
                ->orderBy('created_at', 'DESC')
                ->paginate($vd ?? 20);
                
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
