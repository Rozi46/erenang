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

class ApiControllerPengaturan extends Controller
{
    
    // Pengaturan Level 
    public function getlevelakses(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'failed','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $results['menu'] = ListAkses::where('menu', 'Yes')->orderBy('no_urut', 'DESC')->get();
            foreach($results['menu'] as $key => $menu){
                $results['count_used'][$menu->id] = ListAkses::where('menu_index',$menu->id)->count();
                $results['submenu'][$menu->id] = ListAkses::where('menu_index', $menu->id)->where('submenu', 'Yes')->orderBy('no_urut', 'ASC')->get();
                foreach($results['submenu'][$menu->id] as $key => $submenu){
                    $results['action'][$submenu->id] = ListAkses::where('menu_index', $submenu->id)->where('action', 'Yes')->orderBy('no_urut', 'ASC')->get();
                    foreach($results['action'][$submenu->id] as $key => $action){
                        $results['subaction'][$action->id] = ListAkses::where('menu_index', $action->id)->where('subaction', 'Yes')->orderBy('no_urut', 'ASC')->get();
                    }
                }
            }                
            return response()->json(['status_message' => 'success','results' => $results]);
        }
    }

    public function actionsettingmenu(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'failed','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            if($viewadmin['level'] == 'LV5677001'){
                $validator = Validator::make($request->all(), [
                    'no_urut' => 'required|string',
                    'nama_menu' => 'required|string|max:200',
                    'nama_akses' => 'required|min:1|max:200|unique:db_list_akses',
                ]);

                if($validator->fails()){return response()->json(['status_message' => 'failed','note' => $validator->errors()]);}

                if($request->get('type_menu') == 'Menu'){
                    $menu = 'Yes';
                    $submenu = 'No';
                    $action = 'No';
                    $subaction = 'No';
                }elseif($request->get('type_menu') == 'SubMenu'){
                    $menu = 'No';
                    $submenu = 'Yes';
                    $action = 'No';
                    $subaction = 'No';
                }elseif($request->get('type_menu') == 'Action'){
                    $menu = 'No';
                    $submenu = 'No';
                    $action = 'Yes';
                    $subaction = 'No';
                }elseif($request->get('type_menu') == 'SubAction'){
                    $menu = 'No';
                    $submenu = 'No';
                    $action = 'No';
                    $subaction = 'Yes';
                }else{
                    $menu = 'Yes';
                    $submenu = 'No';
                    $action = 'No';
                    $subaction = 'No';
                }

                if($request->get('type_menu') != 'Menu'){
                    $validator = Validator::make($request->all(), [
                        'menu_index' => 'required|string',
                    ]);
    
                    if($validator->fails()){ return response()->json(['status_message' => 'failed','note' => $validator->errors()]);}
                }

                if($request->get('icon_menu') == ''){
                    $icon_menu = 'fa fa-align-right';
                }else{
                    $icon_menu = $request->get('icon_menu');
                }

                $savedata = ListAkses::create([
                    'id' => Str::uuid(),
                    'no_urut' => $request->get('no_urut'),
                    'nama_menu' => $request->get('nama_menu'),
                    'nama_akses' => str_replace(' ', '',$request->get('nama_akses')),
                    'menu_index' => $request->get('menu_index'),
                    'menu' => $menu,
                    'submenu' => $submenu,
                    'action' => $action,
                    'subaction' => $subaction,
                    'icon_menu' => $icon_menu,
                    'status_data' => 'Aktif',
                ]);

                if($savedata){
                    $listlevel = DB::table('db_level_admin')
                        ->select('level_name',DB::raw('code_data'))
                        ->groupBy('level_name','code_data')
                        ->orderBy('level_name', 'ASC')
                        ->get();
                    foreach($listlevel as $key => $level){
                        $uuidlevel = Uuid::uuid4();
                        if($level->code_data == 'LV5677001'){
                            $access_rights = 'Yes';
                        }else{
                            $access_rights = 'No';
                        }
                        LevelAdmin::create([
                            'id' => $uuidlevel,
                            'code_data' => $level->code_data,
                            'level_name' => $level->level_name,
                            'data_menu' => str_replace(' ', '',$request->get('nama_akses')),
                            'access_rights' => $access_rights
                        ]);
                    }
                    return response()->json(['status_message' => 'success','note' => 'Data berhasil disimpan','id_data' => Str::uuid(),'results' => $object]);
                }else{
                    return response()->json(['status_message' => 'failed','note' => 'Data gagal disimpan','results' => $object]);
                }
            }else{
                return response()->json(['status_message' => 'failed','note' => 'Tidak ada akses','results' => $object]);
            }
        }
    }

    public function delmenu(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'failed','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            if($viewadmin['level'] == 'LV5677001'){
                $getdata = ListAkses::where('id', $request->id)->first();
                if($getdata){
                    $listmenu = ListAkses::where('menu_index', $getdata->id)->orderBy('no_urut', 'ASC')->get();
                    foreach($listmenu as $key => $list){
                        $listsubmenu[$list->id] = ListAkses::where('menu_index', $list->id)->orderBy('no_urut', 'ASC')->get();
                        foreach($listsubmenu[$list->id] as $key => $listsub){
                            $listaction[$listsub->id] = ListAkses::where('menu_index', $listsub->id)->orderBy('no_urut', 'ASC')->get();
                            foreach($listaction[$listsub->id] as $key => $listact){
                                $listsubaction[$listact->id] = ListAkses::where('menu_index', $listact->id)->orderBy('no_urut', 'ASC')->get();
                                foreach($listsubaction[$listact->id] as $key => $listsubact){
                                    LevelAdmin::where('data_menu', $listsubact->nama_akses)->delete();
                                    ListAkses::where('menu_index', $listsubact->id)->delete();
                                }
                                LevelAdmin::where('data_menu', $listact->nama_akses)->delete();
                                ListAkses::where('menu_index', $listact->id)->delete();
                            }
                            LevelAdmin::where('data_menu', $listsub->nama_akses)->delete();
                            ListAkses::where('menu_index', $listsub->id)->delete();
                        }
                        LevelAdmin::where('data_menu', $list->nama_akses)->delete();
                        ListAkses::where('menu_index', $list->id)->delete();
                    }
                    LevelAdmin::where('data_menu', $getdata->nama_akses)->delete();
                    ListAkses::where('menu_index', $getdata->id)->delete();
                    ListAkses::where('id', $request->id)->delete();

                    return response()->json(['status_message' => 'success','note' => 'Data berhasil dihapus','results' => $object]);
                }else{
                    return response()->json(['status_message' => 'failed','note' => 'Data tidak ditemukan','results' => $object]);
                }
            }else{
                return response()->json(['status_message' => 'failed','note' => 'Tidak ada akses','results' => $object]);
            }
        }
    }

    public function listoplevel(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'failed','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_akses = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','users')->first();
            $level_sub_akses = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','listusers')->first();
            $level_sub_sub_akses = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','newusers')->first();
            
            if($level_akses->access_rights == 'Yes' && $level_sub_akses->access_rights == 'Yes' && $level_sub_sub_akses->access_rights == 'Yes'){
                $results = DB::table('db_level_admin')->select('level_name',DB::raw('code_data'))->groupBy('level_name','code_data')->orderBy('level_name', 'ASC')->get(); 
                return response()->json(['status_message' => 'success','results' => $results]);
            }else{
                return response()->json(['status_message' => 'failed','note' => 'Tidak ada akses','results' => $object]);
            } 
        }
    }

    // Data Perusahaan
    public function listcompany(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'failed','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            if($request->has('vd')){
                if($request->vd == ''){
                    $vd = '20';
                }else{
                    $vd = $request->vd;
                }
            }else{
                $vd = '20';
            }
            
            $results['listdata'] = Company::where(function($query) use ($request) {
                    $query->whereRaw('code_data ILIKE ?', ["%{$request->keysearch}%"])
                    ->orWhereRaw('nama_company ILIKE ?', ["%{$request->keysearch}%"])
                    ->orWhereRaw('jenis ILIKE ?', ["%{$request->keysearch}%"])
                    ->orWhereRaw('alamat ILIKE ?', ["%{$request->keysearch}%"])
                    ->orWhereRaw('email ILIKE ?', ["%{$request->keysearch}%"]);
                })
                ->orderBy('created_at', 'ASC')
                ->paginate($vd ?? 20);

            foreach($results['listdata'] as $key => $data){
                $results['count_used'][$data->id] = User::where('code_company', $data->code_data)->count();
            }
                
            return response()->json(['status_message' => 'success','note' => 'Proses data berhasil','count_all_data' => $results['listdata']->total(),'count_view_data' => $vd,'keysearch' => $request->keysearch,'results' => $results]);
        }
    }
                
    public function newcompany(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'failed','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $validator = Validator::make($request->all(), [
                'nama'   => 'required|string|max:200',
                'jenis'  => 'required|string|max:200',
                'alamat' => 'required|string|max:200',
                'email'  => 'required|email|max:200',
            ]);

            if ($validator->fails()) {
                return response()->json(['status_message' => 'failed','note' => $validator->errors(),'results' => $object], 422);
            }

            try {
                DB::beginTransaction();

                $otp = substr(str_shuffle(str_repeat('123456789', 4)), 0, 4);
                $dataAll = Company::orderBy('created_at', 'desc')->first();
                $countData = Company::count();

                if ($countData > 0 && $dataAll && isset($dataAll->code_data)) {
                    $lastNumber = (int) substr($dataAll->code_data, -4);
                    $incrementedNumber = $lastNumber + 1;
                } else {
                    $incrementedNumber = 1;
                }

                $formattedNumber = str_pad($incrementedNumber, 4, '0', STR_PAD_LEFT);
                $newCodeData = 'CP' . $otp . $formattedNumber;

                $savedata = Company::create([
                    'id'           => Str::uuid(),
                    'code_data'    => $newCodeData,
                    'nama_company' => $request->get('nama'),
                    'jenis'        => $request->get('jenis'),
                    'alamat'       => $request->get('alamat'),
                    'email'        => $request->get('email'),
                    'keterangan'   => 'SERVER',
                    'foto'         => null,
                ]);

                if (!$savedata) {
                    DB::rollBack();
                    return response()->json(['status_message' => 'failed','note' => 'Data gagal disimpan', 'results' => $object], 500);
                }

                $otpAct = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1);
                $newCodeData_activity = ltrim(Carbon::now()->format('Ymdhis') . $otpAct, '0');

                Activity::create([
                    'id'          => Str::uuid(),
                    'code_data'   => $newCodeData_activity,
                    'code_user'   => $viewadmin->code_data ?? null,
                    'activity'    => 'Tambah data perusahaan [' . $request->nama . ']',
                    'code_company'=> $viewadmin->code_company ?? null,
                ]);

                DB::commit();
                return response()->json(['status_message' => 'success','note' => 'Data berhasil disimpan','results' => $savedata], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['status_message' => 'failed','note' => 'Terjadi kesalahan: ' . $e->getMessage(),'results' => $object], 500);
            }
        }
    }

    public function viewcompany(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'failed','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $getdata['nama_company'] = Company::where('id', $request->id)->first();
            if($getdata['nama_company']){ 
                $count_used = User::where('code_company', $getdata['nama_company']->code_data)->count();            
                return response()->json(['status_message' => 'success','note' => 'Proses data berhasil','results' => $getdata,'count_used' => $count_used]);
            }else{
                return response()->json(['status_message' => 'failed','note' => 'Data tidak ditemukan','results' => $object]);
            }
        }
    }

    public function editcompany(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = array();
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'failed','note' => 'Terjadi kesalahan saat proses data']);
        }else{            
            $get_data['company'] = Company::where('id', $request->id_data)->first();

            $validator = Validator::make($request->all(), [
                'code_company'  => 'required|string|max:100',
                'nama_company'  => 'required|string|max:100',
                'jenis_company' => 'required|string|max:100',
                'alamat_company'=> 'required|string|max:100',
                'email_company' => 'required|string|email|max:200',
            ]);

            if ($validator->fails()) {
                return response()->json(['status_message' => 'failed','note' => $validator->errors() ]);
            }

            try {
                DB::beginTransaction();
                
                $update = Company::where('id', $request->id_data)
                    ->update([
                        'code_data'     => $request->get('code_company'),
                        'nama_company'  => $request->get('nama_company'),
                        'jenis'         => $request->get('jenis_company'),
                        'alamat'        => $request->get('alamat_company'),
                        'email'         => $request->get('email_company'),
                    ]);

                if (!$update) {
                    DB::rollBack();
                    return response()->json(['status_message' => 'failed','note' => 'Data gagal disimpan']);
                }

                if ($request->hasFile('logo_company')) {
                    $this->validate($request, [
                        'logo_company' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2548'
                    ]);

                    $imageName = 'PK-' . $request->code_company . '-' . time() . '.' . $request->logo_company->extension();
                    $request->logo_company->move(public_path('/themes/admin/AdminOne/image/public/'), $imageName);

                    Company::where('id', $request->id_data)->update(['foto' => $imageName]);

                    if (!empty($get_data['company']->foto)) {
                        File::delete(public_path('/themes/admin/AdminOne/image/public/' . $get_data['company']->foto));
                    }

                    $file = $request->file('logo_company');
                    $filenameOriginal = $file->getClientOriginalName();
                }

                $otp = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1);
                $newCodeData_activity = ltrim(Carbon::now()->format('Ymdhis') . $otp, '0');

                Activity::create([
                    'id'           => Str::uuid(),
                    'code_data'    => $newCodeData_activity,
                    'code_user'    => $viewadmin->code_data ?? null,
                    'activity'     => 'Update data perusahaan [' . $request->get('nama_company') . ']',
                    'code_company' => $viewadmin->code_company ?? null,
                ]);

                DB::commit();
                return response()->json([ 'status_message' => 'success','note' => 'Data berhasil disimpan','results' => $filenameOriginal ?? null]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['status_message' => 'failed','note' => 'Terjadi kesalahan: ' . $e->getMessage()]);
            }

        }
    }

    public function deletecompany(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();

        if (!$viewadmin) { 
            return response()->json(['status_message' => 'failed','note' => 'Terjadi kesalahan saat proses data' ]);
        } else {
            $company = Company::where('id', $request->id)->first();

            if (!$company) {
                return response()->json(['status_message' => 'failed', 'note' => 'Data tidak ditemukan', 'results' => $object ]);
            } else {
                try {
                    DB::beginTransaction();

                    $oldFoto = $company->foto;
                    $companyName = $company->nama_company;
                    $companyCode = $company->code_data;
                    
                    Company::where('id', $request->id)->delete();

                    if (!empty($oldFoto)) {
                        $path = public_path('/themes/admin/AdminOne/image/public/' . $oldFoto);
                        if (File::exists($path)) {
                            File::delete($path);
                        }
                    }

                    $otp = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1);
                    $newCodeData_activity = ltrim(Carbon::now()->format('Ymdhis') . $otp, '0');

                    Activity::create([
                        'id'            => Str::uuid(),
                        'code_data'     => $newCodeData_activity,
                        'code_user'     => $viewadmin->code_data ?? null,
                        'activity'      => 'Hapus data perusahaan [' . $companyName . ' - ' . $companyCode . ']',
                        'code_company'  => $viewadmin->code_company ?? null,
                    ]);

                    DB::commit();
                    return response()->json(['status_message' => 'success','note' => 'Data berhasil dihapus','results' => $object ]);
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json(['status_message' => 'failed','note' => 'Data gagal dihapus: ' . $e->getMessage(),'results' => $object]);
                }
            }
        }
    }


    // Manual Book 
    public function viewManualBook(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'failed','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $getdata['setting'] = Setting::where('id', '1')->first();
            if($getdata['setting']){           
                return response()->json(['status_message' => 'success','note' => 'Proses data berhasil','results' => $getdata]);
            }else{
                return response()->json(['status_message' => 'failed','note' => 'Data tidak ditemukan','results' => $object]);
            }
        }
    }

    public function uploadmanualbook(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'failed','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $getdata['setting'] = Setting::find(1);

            if ($getdata['setting']) {
                try {
                    if (!$request->hasFile('manual_book')) {
                        return response()->json(['status_message' => 'failed','note' => 'Manual book tidak ditemukan dalam permintaan.','results' => $object]);
                    }

                    $validator = Validator::make($request->all(), [
                        'manual_book' => 'required|mimes:pdf,doc,docx|max:20480', // max 20MB
                    ]);

                    if($validator->fails()){return response()->json(['status_message' => 'failed','note' => $validator->errors()]);}

                    DB::beginTransaction();

                    $oldFile = $getdata['setting']->manual_book;
                    $manualbookName = 'MB-' . time() . '.' . $request->manual_book->getClientOriginalExtension();
                    $request->manual_book->move(public_path('themes/admin/AdminOne/ManualBook/'), $manualbookName);

                    if ($oldFile) {
                        $path = public_path('themes/admin/AdminOne/ManualBook/' . $oldFile);
                        if (File::exists($path)) {
                            File::delete($path);
                        }
                    }

                    $getdata['setting']->update([
                        'manual_book' => $manualbookName,
                    ]);

                    $otp = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1);
                    $newCodeData_activity = ltrim(Carbon::now()->format('Ymdhis') . $otp, '0');

                    Activity::create([
                        'id'            => Str::uuid(),
                        'code_data'     => $newCodeData_activity,
                        'code_user'     => $viewadmin->code_data ?? null,
                        'activity'      => 'Ubah manual book aplikasi',
                        'code_company'  => $viewadmin->code_company ?? null,
                    ]);

                    DB::commit();

                    return response()->json(['status_message' => 'success','note' => 'Data berhasil disimpan','results' => $manualbookName
                    ]);
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json(['status_message' => 'failed','note' => 'Data gagal disimpan: ' . $e->getMessage(),'results' => $object]);
                }
            } else {
                return response()->json(['status_message' => 'failed','note' => 'Data tidak ditemukan','results' => $object]);
            }

        } 

    }

    public function downloadmanualbook(Request $request)
    {
        $object = [];            
        $filePath = public_path('/themes/admin/AdminOne/ManualBook/' . $request['d']);
        if (file_exists($filePath)) {
            return response()->download($filePath);
        } else {
            return response()->json(['status_message' => 'failed','note' => 'Data tidak ditemukan','results' => $object]);
        }
    }
}
