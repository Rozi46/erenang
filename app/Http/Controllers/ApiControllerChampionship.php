<?php

namespace App\Http\Controllers;

require '../vendor/autoload.php';

use App\Http\Controllers\Controller;
use App\Models\{Setting, Company, User, LevelAdmin, ListAkses, Activity, Kategori, KelompokUmur, Atlet, Championship, Event, Heat, HeatLine, Registrasi};
use Illuminate\Http\{Request, UploadedFile, Response};
use Illuminate\Support\Facades\{Hash, Validator, File, Http, Route, Session, Auth, DB, Lang};
use Illuminate\Support\{Carbon, Str};
use Illuminate\Database\Query\Builder;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Ramsey\Uuid\Uuid;

class ApiControllerChampionship extends Controller
{
    // Isi Combobox-select2
    public function listopchampionship(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{         
            $results = Championship::select('code_data','nama_kejuaraan')->whereDate('tanggal_mulai', '>', Carbon::today())->orderBy('nama_kejuaraan', 'ASC')->get();
            return response()->json(['status_message' => 'success','results' => $results]);
        }
    } 

    // Championship
    public function listchampionship(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','kejuaraan')->first();
            $level_sub_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','listchampionship')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','exportchampionship')->first();
            if($request->type == 'export'){
                if($level_action->access_rights == 'No'){
                    return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
                }
            }

            if($level_menu->access_rights == 'No' OR $level_sub_menu->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }

            $vd = intval($request->vd ?? 20);
            $vd = max(1, min($vd, 100));
            
            $results['listdata'] = Championship::where(function($query) use ($request) {
                    $query->whereRaw('code_data ILIKE ?', ["%{$request->keysearch}%"])
                    ->orWhereRaw('nama_kejuaraan ILIKE ?', ["%{$request->keysearch}%"])
                    ->orWhereRaw('lokasi ILIKE ?', ["%{$request->keysearch}%"]);
                })
                ->orderBy('nama_kejuaraan', 'ASC')
                ->paginate($vd ?? 20);

            foreach($results['listdata'] as $key => $data){
                $results['count_used'][$data->code_data] = Event::where('code_kejuaraan', $data->code_data)->count();             
            }
                
            return response()->json(['status_message' => 'success','note' => 'Proses data berhasil','count_all_data' => $results['listdata']->total(),'count_view_data' => $vd,'keysearch' => $request->keysearch,'results' => $results]);
        }
    }

    public function newchampionship(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','listchampionship')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','newchampionship')->first();
            
            if($level_menu->access_rights == 'No' OR $level_action->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }

            $validator = Validator::make($request->all(), [
                'nama_kejuaraan'    => 'required|string|max:200|unique:db_championships',
                'lokasi'            => 'required|string|max:200',
                'jumlah_line'       => 'required|string|max:200',
                'tanggal_mulai'     => 'required|date',
                'tanggal_selesai'   => 'required|date|after_or_equal:tanggal_mulai',
            ]);

            if($validator->fails()){
                return response()->json(['status_message' => 'error','note' => $validator->errors()]);
            }

            try {
                DB::beginTransaction();

                $otp = substr(str_shuffle(str_repeat('123456789', 4)), 0, 4);
                $dataAll = Championship::orderBy('created_at', 'desc')->first();
                $countData = Championship::count();

                if ($countData > 0 && $dataAll && isset($dataAll->code_data)) {
                    $lastNumber = (int) substr($dataAll->code_data, -4);
                    $incrementedNumber = $lastNumber + 1;
                } else {
                    $incrementedNumber = 1;
                }

                $formattedNumber = str_pad($incrementedNumber, 4, '0', STR_PAD_LEFT);
                $newCodeData = 'CH' . $otp . $formattedNumber;

                $tanggal_mulai = Carbon::parse($request->get('tanggal_mulai'))->format('Y-m-d');               
                $tanggal_selesai = Carbon::parse($request->get('tanggal_selesai'))->format('Y-m-d');

                $savedata = Championship::create([
                    'id'                => Str::uuid(),
                    'code_data'         => $newCodeData,
                    'nama_kejuaraan'    => $request->get('nama_kejuaraan'),
                    'lokasi'            => $request->get('lokasi'),
                    'jumlah_line'       => $request->get('jumlah_line'),
                    'tanggal_mulai'     => $tanggal_mulai,
                    'tanggal_selesai'   => $tanggal_selesai,
                ]);

                $otpAct = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1);
                $newCodeData_activity = ltrim(Carbon::now()->format('Ymdhis') . $otpAct, '0');

                Activity::create([
                    'id'          => Str::uuid(),
                    'code_data'   => $newCodeData_activity,
                    'code_user'   => $viewadmin->code_data ?? null,
                    'activity'    => 'Tambah data kejuaraan [' . $request->get('nama_kejuaraan') . ' - ' . $newCodeData . ']',
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

    public function viewchampionship(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','kejuaraan')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','listchampionship')->first();
            
            if($level_menu->access_rights == 'No' OR $level_action->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }

            $getdata['championship'] = Championship::where('code_data', $request->code_data)->first();
            if($getdata['championship']){ 
                $count_used = Event::where('code_kejuaraan', $getdata['championship']->code_data)->count();        
                return response()->json(['status_message' => 'success','note' => 'Proses data berhasil','results' => $getdata,'count_used' => $count_used]);
            }else{
                return response()->json(['status_message' => 'error','note' => 'Data tidak ditemukan','results' => $object]);
            }
        }

    }

    public function editchampionship(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','listchampionship')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','editchampionship')->first();
            
            if($level_menu->access_rights == 'No' OR $level_action->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }

            $get_data['championship'] = Championship::where('code_data', $request->code_data)->first();
            if(!$get_data['championship']){
                return response()->json(['status_message' => 'error','note' => 'Data tidak ditemukan','results' => $object]);
            }else{
                $validator = Validator::make($request->all(), [
                    'nama_kejuaraan'    => 'required|string|max:200',
                    'lokasi'            => 'required|string|max:200',
                    'jumlah_line'       => 'required|string|max:200',
                    'tanggal_mulai'     => 'required|date',
                    'tanggal_selesai'   => 'required|date|after_or_equal:tanggal_mulai',
                ]);

                if($validator->fails()){return response()->json(['status_message' => 'error','note' => $validator->errors()]);}

                if($request->nama_kejuaraan != $get_data['championship']->nama_kejuaraan){
                    $validator = Validator::make($request->all(),[
                        'nama_kejuaraan' => 'required|string|max:200|unique:db_championships',
                    ]);

                    if($validator->fails()){return response()->json(['status_message' => 'error','note' => $validator->errors(),'results' => $object]);}
                }

                try {
                    DB::beginTransaction();

                    $tanggal_mulai = Carbon::parse($request->get('tanggal_mulai'))->format('Y-m-d');               
                    $tanggal_selesai = Carbon::parse($request->get('tanggal_selesai'))->format('Y-m-d');

                    Championship::where('code_data', $request->get('code_data'))
                        ->update([
                            'nama_kejuaraan'    => $request->get('nama_kejuaraan'),
                            'lokasi'            => $request->get('lokasi'),
                            'jumlah_line'       => $request->get('jumlah_line'),
                            'tanggal_mulai'     => $tanggal_mulai,
                            'tanggal_selesai'   => $tanggal_selesai,
                        ]);  

                    $otp = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1);
                    $newCodeData_activity = ltrim(Carbon::now()->format('Ymdhis') . $otp, '0');

                    Activity::create([
                        'id'            => Str::uuid(),
                        'code_data'     => $newCodeData_activity,
                        'code_user'     => $viewadmin->code_data,
                        'activity'      => 'Update data kejuaraan ['.$get_data['championship']->nama_kejuaraan.' - '.$get_data['championship']->code_data.']',
                        'code_company'  => $viewadmin->code_company ?? null,
                    ]);

                    DB::commit();
                    return response()->json(['status_message' => 'success','note' => 'Data berhasil disimpan','results' => $object]);

                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan: '.$e->getMessage(),'results' => $object], 500);
                }
            }
        }

    }

    public function deletechampionship(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','listchampionship')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','deletechampionship')->first();
            if($level_menu->access_rights == 'No' OR $level_action->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }

            $getdata = Championship::where('code_data', $request->code_data)->first();
            if(!$getdata){
                return response()->json(['status_message' => 'error','note' => 'Data tidak ditemukan','results' => $object]);
            }else{
                try {
                    DB::beginTransaction();
                    
                    Championship::where('code_data', $request->code_data)->delete();

                    $otp = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1);
                    $newCodeData_activity = ltrim(Carbon::now()->format('Ymdhis') . $otp, '0');

                    Activity::create([
                        'id'            => Str::uuid(),
                        'code_data'     => $newCodeData_activity,
                        'code_user'     => $viewadmin->code_data,
                        'activity'      => 'Hapus data kejuaraan ['.$getdata->nama_kejauaraan.' - '.$getdata->code_data.']',
                        'code_company'  => $viewadmin->code_company ?? null,
                    ]);

                    DB::commit();
                    return response()->json(['status_message' => 'success','note' => 'Data berhasil dihapus','results' => $object]);
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan: '.$e->getMessage(),'results' => $object], 500);
                }
            }
        }
    }

    // Event
    public function listevent(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','kejuaraan')->first();
            $level_sub_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','listevent')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','exportevent')->first();
            if($request->type == 'export'){
                if($level_action->access_rights == 'No'){
                    return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
                }
            }

            if($level_menu->access_rights == 'No' OR $level_sub_menu->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }

            $vd = intval($request->vd ?? 20);
            $vd = max(1, min($vd, 100));
            
            $results['listdata'] = Event::where(function($query) use ($request) {
                    $query->whereRaw('code_data ILIKE ?', ["%{$request->keysearch}%"])
                    ->orWhereRaw('code_event ILIKE ?', ["%{$request->keysearch}%"])
                    ->orWhereRaw('code_gaya ILIKE ?', ["%{$request->keysearch}%"])
                    ->orWhereRaw("CAST(jarak AS TEXT) ILIKE ?", ["%{$request->keysearch}%"])
                    ->orWhereRaw('code_kategori ILIKE ?', ["%{$request->keysearch}%"])
                    ->orWhereRaw('gender ILIKE ?', ["%{$request->keysearch}%"])
                    ->orWhereRaw('code_kejuaraan ILIKE ?', ["%{$request->keysearch}%"]);
                })
                ->orderBy('created_at', 'DESC')
                ->paginate($vd ?? 20);

            foreach($results['listdata'] as $key => $data){
                $results['count_used'][$data->code_data] = Heat::where('code_event', $data->code_data)->count();     
                $results['detail_gaya'][$data->code_data] = Kategori::where('code_data', $data->code_gaya)->first(); 
                $results['detail_ku'][$data->code_data] = KelompokUmur::where('code_data', $data->code_kategori)->first();
                $results['detail_kejuaraan'][$data->code_data] = Championship::where('code_data', $data->code_kejuaraan)->first();
            }
                
            return response()->json(['status_message' => 'success','note' => 'Proses data berhasil','count_all_data' => $results['listdata']->total(),'count_view_data' => $vd,'keysearch' => $request->keysearch,'results' => $results]);
        }
    }

    public function newevent(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','listevent')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','newevent')->first();
            
            if($level_menu->access_rights == 'No' OR $level_action->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }

            $validator = Validator::make($request->all(), [
                'code_event'        => 'required|string|max:200',
                'code_gaya'         => 'required|string|max:200',
                'jarak'             => 'required|string|max:200',
                'code_kategori'     => 'required|string|max:200',
                'gender'            => 'required|string|max:200',
                'tanggal'           => 'required|date',
                'code_kejuaraan'    => 'required|string|max:200',
            ]);

            if($validator->fails()){
                return response()->json(['status_message' => 'error','note' => $validator->errors()]);
            }

            try {
                DB::beginTransaction();

                $otp = substr(str_shuffle(str_repeat('123456789', 4)), 0, 4);
                $dataAll = Event::orderBy('created_at', 'desc')->first();
                $countData = Event::count();

                if ($countData > 0 && $dataAll && isset($dataAll->code_data)) {
                    $lastNumber = (int) substr($dataAll->code_data, -4);
                    $incrementedNumber = $lastNumber + 1;
                } else {
                    $incrementedNumber = 1;
                }

                $formattedNumber = str_pad($incrementedNumber, 4, '0', STR_PAD_LEFT);
                $newCodeData = 'EV' . $otp . $formattedNumber;

                $tanggal = Carbon::parse($request->get('tanggal'))->format('Y-m-d');     

                $savedata = Event::create([
                    'id'                => Str::uuid(),
                    'code_data'         => $newCodeData,
                    'code_event'        => $request->get('code_event'),
                    'code_gaya'         => $request->get('code_gaya'),
                    'jarak'             => $request->get('jarak'),
                    'code_kategori'     => $request->get('code_kategori'),
                    'gender'            => $request->get('gender'),
                    'tanggal'           => $tanggal,
                    'code_kejuaraan'    => $request->get('code_kejuaraan'),
                ]);

                $otpAct = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1);
                $newCodeData_activity = ltrim(Carbon::now()->format('Ymdhis') . $otpAct, '0');

                Activity::create([
                    'id'          => Str::uuid(),
                    'code_data'   => $newCodeData_activity,
                    'code_user'   => $viewadmin->code_data ?? null,
                    'activity'    => 'Tambah data nomor lomba [' . $request->get('code_event') . ' - ' . $newCodeData . ']',
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

    public function viewevent(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','kejuaraan')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','listevent')->first();
            
            if($level_menu->access_rights == 'No' OR $level_action->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }

            $getdata['event'] = Event::where('code_data', $request->code_data)->first();
            if($getdata['event']){ 
                $count_used = Heat::where('code_event', $getdata['event']->code_data)->count();   
                return response()->json(['status_message' => 'success','note' => 'Proses data berhasil','results' => $getdata,'count_used' => $count_used]);
            }else{
                return response()->json(['status_message' => 'error','note' => 'Data tidak ditemukan','results' => $object]);
            }
        }

    }

    public function editevent(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','listevent')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','editevent')->first();
            
            if($level_menu->access_rights == 'No' OR $level_action->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }

            $get_data['event'] = Event::where('code_data', $request->code_data)->first();
            if(!$get_data['event']){
                return response()->json(['status_message' => 'error','note' => 'Data tidak ditemukan','results' => $object]);
            }else{
                $validator = Validator::make($request->all(), [
                    'code_event'        => 'required|string|max:200',
                    'code_gaya'         => 'required|string|max:200',
                    'jarak'             => 'required|string|max:200',
                    'code_kategori'     => 'required|string|max:200',
                    'gender'            => 'required|string|max:200',
                    'tanggal'           => 'required|date',
                    'code_kejuaraan'    => 'required|string|max:200',
                ]);

                if($validator->fails()){return response()->json(['status_message' => 'error','note' => $validator->errors()]);}

                try {
                    DB::beginTransaction();

                    $tanggal = Carbon::parse($request->get('tanggal'))->format('Y-m-d');  
                    
                    Event::where('code_data', $request->get('code_data'))
                        ->update([
                            'code_event'        => $request->get('code_event'),
                            'code_gaya'         => $request->get('code_gaya'),
                            'jarak'             => $request->get('jarak'),
                            'code_kategori'     => $request->get('code_kategori'),
                            'gender'            => $request->get('gender'),
                            'tanggal'           => $tanggal,
                            'code_kejuaraan'    => $request->get('code_kejuaraan'),
                        ]);  

                    Activity::create([
                        'id'            => Str::uuid(),
                        'code_data'     => ltrim(now()->format('YmdHis') . $this->generateCode(1, 'letters'), '0'),
                        'code_user'     => $viewadmin->code_data,
                        'activity'      => 'Update data nomor lomba ['.$get_data['event']->code_event.' - '.$get_data['event']->code_data.']',
                        'code_company'  => $viewadmin->code_company ?? null,
                    ]);

                    DB::commit();
                    return response()->json(['status_message' => 'success','note' => 'Data berhasil disimpan','results' => $object]);

                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan: '.$e->getMessage(),'results' => $object], 500);
                }
            }
        }

    }

    public function deleteevent(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','listevent')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','deleteevent')->first();
            if($level_menu->access_rights == 'No' OR $level_action->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }

            $getdata = Event::where('code_data', $request->code_data)->first();
            if(!$getdata){
                return response()->json(['status_message' => 'error','note' => 'Data tidak ditemukan','results' => $object]);
            }else{
                try {
                    DB::beginTransaction();
                    
                    Event::where('code_data', $request->code_data)->delete();                    

                    Activity::create([
                        'id'            => Str::uuid(),
                        'code_data'     => ltrim(now()->format('YmdHis') . $this->generateCode(1, 'letters'), '0'),
                        'code_user'     => $viewadmin->code_data,
                        'activity'      => 'Hapus data nomor lomba ['.$getdata->code_event.' - '.$getdata->code_data.']',
                        'code_company'  => $viewadmin->code_company ?? null,
                    ]);

                    DB::commit();
                    return response()->json(['status_message' => 'success','note' => 'Data berhasil dihapus','results' => $object]);
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan: '.$e->getMessage(),'results' => $object], 500);
                }
            }
        }
    }

    public function generateheat(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','listevent')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','editevent')->first();
            if($level_menu->access_rights == 'No' OR $level_action->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }

            $getdata['event'] = Event::where('code_data', $request->code_data)->first();
            
            if(!$getdata['event']){
                return response()->json(['status_message' => 'error','note' => 'Data tidak ditemukan','results' => $object]);
            }else{
                DB::beginTransaction();
                try {
                    $participants = Registrasi::with('atlet')->whereJsonContains('code_event', $getdata['event']->code_data)->where('status', 'verified')->get();

                    if ($participants->isEmpty()) {
                        throw new \Exception('Belum ada peserta verified');
                    }

                    $sorted = $participants->sortBy(fn ($r) => $r->atlet->best_time ?? 9999)->values();

                    $totalAthletes = $sorted->count();
                    $champion = Championship::where('code_data', $getdata['event']->code_kejuaraan)->firstOrFail();
                    $laneCount = (int) $champion->jumlah_line;
                    $heatCount = (int) ceil($totalAthletes / $laneCount);
                    $lastHeatCount = $totalAthletes % $laneCount ?: $laneCount;

                    Heat::where('code_event', $getdata['event']->code_data)
                        ->each(function ($heat) {
                            $heat->heatLines()->delete();
                            $heat->delete();
                        });

                    $index = 0;

                    for ($heatNo = 1; $heatNo <= $heatCount; $heatNo++) {
                        $isLastHeat = $heatNo === $heatCount;
                        $take = $isLastHeat ? $lastHeatCount : $laneCount;
                        $slice = $sorted->slice($index, $take)->values();

                        // Lane order
                        $laneOrder = array_slice($this->centeredLaneOrder($laneCount), 0, $slice->count());

                        /** ================= Heat ================= */
                        $otpHeat = substr(str_shuffle('123456789'), 0, 4);
                        $lastHeat = Heat::orderByDesc('code_data')->first();
                        $nextNumberHeat = $lastHeat ? ((int) substr($lastHeat->code_data, -4)) + 1 : 1;
                        $newCodeDataHeat = 'HT' . now()->format('Y') . str_pad($nextNumberHeat, 4, '0', STR_PAD_LEFT);

                        $heat = Heat::create([
                            'id'         => Str::uuid(),
                            'code_data'  => $newCodeDataHeat,
                            'code_event' => $getdata['event']->code_data,
                            'nomor_seri' => $heatNo,
                        ]);

                        /** ================= Heat Line ================= */
                        foreach ($slice as $i => $reg) {
                            $otpHeatLine = substr(str_shuffle('123456789'), 0, 4);
                            $lastHeatLine = HeatLine::orderByDesc('code_data')->first();
                            $nextNumberHeatLine = $lastHeatLine ? ((int) substr($lastHeatLine->code_data, -4)) + 1 : 1;
                            $newCodeDataHeatLine = 'HL' . now()->format('Y') . str_pad($nextNumberHeatLine, 4, '0', STR_PAD_LEFT);

                            HeatLine::create([
                                'id'           => Str::uuid(),
                                'code_data'    => $newCodeDataHeatLine,
                                'code_heat'    => $heat->code_data,
                                'code_athlete' => $reg->atlet->code_data,
                                'line_number'  => $laneOrder[$i] ?? ($i + 1),
                                'best_time'    => '00:00.00',   // $reg->atlet->best_time
                                'hasil'        => '00:00.00',
                                'ranking'      => 0,
                            ]);
                        }

                        $index += $slice->count();
                    }

                    Activity::create([
                        'id'           => Str::uuid(),
                        'code_data'    => ltrim(now()->format('YmdHis') . $this->generateCode(1, 'letters'), '0'),
                        'code_user'    => $viewadmin->code_data,
                        'activity'     => 'Generate heat event [' . $getdata['event']->code_event . ']',
                        'code_company' => $viewadmin->code_company,
                    ]);

                    DB::commit();
                    return response()->json(['status_message' => 'success','note' => 'Data heat berhasil digenerate','results' => $object]);
                } catch (\Throwable $e) {
                    DB::rollBack();
                    return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan: ' . $e->getMessage(),'results' => $object], 500);}
            }
        }
    }

    // Heat
    public function listheat(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','kejuaraan')->first();
            $level_sub_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','listheat')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','exportheat')->first();
            if($request->type == 'export'){
                if($level_action->access_rights == 'No'){
                    return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
                }
            }

            if($level_menu->access_rights == 'No' OR $level_sub_menu->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }

            $vd = intval($request->vd ?? 20);
            $vd = max(1, min($vd, 100));
            
            // $results['listdata'] = Heat::where(function($query) use ($request) {
            //         $query->whereRaw('code_data ILIKE ?', ["%{$request->keysearch}%"])
            //         ->orWhereRaw('code_event ILIKE ?', ["%{$request->keysearch}%"])
            //         ->orWhereRaw("CAST(nomor_seri AS TEXT) ILIKE ?", ["%{$request->keysearch}%"]);
            //     })
            //     ->orderBy('nomor_seri', 'ASC')
            //     ->paginate($vd ?? 20);

            // foreach($results['listdata'] as $key => $data){
            //     $results['count_used'][$data->code_data] = HeatLine::where('code_heat', $data->code_data)->count();
            //     $results['detail_event'][$data->code_data] = Event::where('code_data', $data->code_event)->first(); 
            //     $results['detail_champion'][$data->code_data] = Championship::where('code_data', $results['detail_event'][$data->code_data]->code_kejuaraan)->first(); 
            // }


            $results['listdata'] = Heat::with(['event.championship'])
                ->withCount(['heatLines as count_used'])
                ->where(function ($query) use ($request) {
                    $query->where('code_data', 'ILIKE', "%{$request->keysearch}%")
                        ->orWhere('code_event', 'ILIKE', "%{$request->keysearch}%")
                        ->orWhereRaw('CAST(nomor_seri AS TEXT) ILIKE ?', ["%{$request->keysearch}%"]);
                })
                ->orderBy('nomor_seri', 'ASC')
                ->paginate($vd ?? 20);

                
            return response()->json(['status_message' => 'success','note' => 'Proses data berhasil','count_all_data' => $results['listdata']->total(),'count_view_data' => $vd,'keysearch' => $request->keysearch,'results' => $results]);
        }
    }

    // HeatLine
    public function listheatline(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','kejuaraan')->first();
            $level_sub_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','listheatline')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','exportheatline')->first();
            if($request->type == 'export'){
                if($level_action->access_rights == 'No'){
                    return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
                }
            }

            if($level_menu->access_rights == 'No' OR $level_sub_menu->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }

            $vd = intval($request->vd ?? 20);
            $vd = max(1, min($vd, 100));
            
            $results['listdata'] = HeatLine::where(function($query) use ($request) {
                    $query->whereRaw('code_data ILIKE ?', ["%{$request->keysearch}%"])
                    ->orWhereRaw('code_heat ILIKE ?', ["%{$request->keysearch}%"])
                    ->orWhereRaw('code_athlete ILIKE ?', ["%{$request->keysearch}%"])
                    ->orWhereRaw("CAST(line_number AS TEXT) ILIKE ?", ["%{$request->keysearch}%"])                    
                    ->orWhereRaw('best_time ILIKE ?', ["%{$request->keysearch}%"])
                    ->orWhereRaw('hasil ILIKE ?', ["%{$request->keysearch}%"])
                    ->orWhereRaw("CAST(ranking AS TEXT) ILIKE ?", ["%{$request->keysearch}%"]);
                })
                ->orderBy('ranking', 'ASC')
                ->paginate($vd ?? 20);

            foreach($results['listdata'] as $key => $data){
                // $results['count_used'][$data->code_data] = Barang::where('kode_jenis', $data->code_data)->count();
                $results['count_used'][$data->code_data] = 0;      
                $results['detail_heat'][$data->code_data] = Heat::where('code_data', $data->code_heat)->first(); 
                $results['detail_atlet'][$data->code_data] = Atlet::where('code_data', $data->code_athlete)->first(); 
            }
                
            return response()->json(['status_message' => 'success','note' => 'Proses data berhasil','count_all_data' => $results['listdata']->total(),'count_view_data' => $vd,'keysearch' => $request->keysearch,'results' => $results]);
        }
    }
}
