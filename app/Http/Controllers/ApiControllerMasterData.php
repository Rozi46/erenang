<?php

namespace App\Http\Controllers;

require '../vendor/autoload.php';

use App\Http\Controllers\Controller;
use App\Models\{Setting, Company, User, LevelAdmin, ListAkses, Activity, Atlet, Club, Kategori, Event, KelompokUmur};
use Illuminate\Http\{Request, UploadedFile, Response};
use Illuminate\Support\Facades\{Hash, Validator, File, Http, Route, Session, Auth, DB, Lang};
use Illuminate\Support\{Carbon, Str};
use Illuminate\Database\Query\Builder;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Ramsey\Uuid\Uuid;

class ApiControllerMasterData extends Controller
{
    // Isi Combobox-select2
    public function listopgaya(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $results = Kategori::orderBy('nama_gaya', 'ASC')->get();
            return response()->json(['status_message' => 'success','results' => $results]);
        }
    }

    public function listopku(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $results = KelompokUmur::orderBy('code_kelompok', 'ASC')->get();
            return response()->json(['status_message' => 'success','results' => $results]);
        }
    }

    public function listopclub(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $results = Club::orderBy('nama_club', 'ASC')->get();
            return response()->json(['status_message' => 'success','results' => $results]);
        }
    }

    // Atlet
    public function listatlet(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','masterdata')->first();
            $level_sub_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','listatlet')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','exportlistatlet')->first();
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
            
            // $results['listdata'] = Atlet::where(function($query) use ($request) {
            //         $query->whereRaw('code_data ILIKE ?', ["%{$request->keysearch}%"])
            //         ->orWhereRaw('nis ILIKE ?', ["%{$request->keysearch}%"])
            //         ->orWhereRaw('nama ILIKE ?', ["%{$request->keysearch}%"])
            //         ->orWhereRaw('tempat_lahir ILIKE ?', ["%{$request->keysearch}%"]);
            //     })
            //     ->orderBy('nama', 'ASC')
            //     ->paginate($vd ?? 20);

            // foreach($results['listdata'] as $key => $data){
            //     // $results['count_used'][$data->code_data] = Barang::where('kode_jenis', $data->code_data)->count();
            //     $results['count_used'][$data->code_data] = 0;                
            //     $results['detail_club'][$data->code_data] = Club::where('code_data', $data->code_club)->first();
            // }

            $results['listdata'] = Atlet::with([
                    'club:id,code_data,nama_club'
                ])
                ->withCount('registrasi')
                ->when($request->keysearch, function ($query) use ($request) {
                    $query->where(function ($q) use ($request) {
                        $q->where('code_data', 'ILIKE', "%{$request->keysearch}%")
                        ->orWhere('nis', 'ILIKE', "%{$request->keysearch}%")
                        ->orWhere('nama', 'ILIKE', "%{$request->keysearch}%")
                        ->orWhere('tempat_lahir', 'ILIKE', "%{$request->keysearch}%");
                    });
                })
                ->orderBy('nama', 'ASC')
                ->paginate($vd ?? 20);

                
            return response()->json(['status_message' => 'success','note' => 'Proses data berhasil','count_all_data' => $results['listdata']->total(),'count_view_data' => $vd,'keysearch' => $request->keysearch,'results' => $results]);
        }
    }

    public function newatlet(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','listatlet')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','newatlet')->first();
            
            if($level_menu->access_rights == 'No' OR $level_action->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }

            $validator = Validator::make($request->all(), [
                'nis'           => 'required|string|max:200|unique:db_athletes',
                'nama'          => 'required|string|max:200',
                'gender'        => 'required|string|max:200',
                'tempat_lahir'  => 'required|string|max:200',
                'code_club'     => 'required|string|max:200',
            ]);

            if($validator->fails()){
                return response()->json(['status_message' => 'error','note' => $validator->errors()]);
            }

            try {
                DB::beginTransaction();

                $otp = substr(str_shuffle(str_repeat('123456789', 4)), 0, 4);
                $dataAll = Atlet::orderBy('created_at', 'desc')->first();
                $countData = Atlet::count();

                if ($countData > 0 && $dataAll && isset($dataAll->code_data)) {
                    $lastNumber = (int) substr($dataAll->code_data, -4);
                    $incrementedNumber = $lastNumber + 1;
                } else {
                    $incrementedNumber = 1;
                }

                $formattedNumber = str_pad($incrementedNumber, 4, '0', STR_PAD_LEFT);
                $newCodeData = 'AT' . $otp . $formattedNumber;

                // $tanggal_lahir = Carbon::parse($request->get('tanggal_lahir'))->format('Y-m-d');

                $bulan = [
                    'Januari' => 'January',
                    'Februari' => 'February',
                    'Maret' => 'March',
                    'April' => 'April',
                    'Mei' => 'May',
                    'Juni' => 'June',
                    'Juli' => 'July',
                    'Agustus' => 'August',
                    'September' => 'September',
                    'Oktober' => 'October',
                    'November' => 'November',
                    'Desember' => 'December',
                ];

                $input = $request->tanggal_lahir;
                $input = str_replace(array_keys($bulan), array_values($bulan), $input);

                $tanggal_lahir = Carbon::parse($input)->format('Y-m-d');

                $savedata = Atlet::create([
                    'id'            => Str::uuid(),
                    'code_data'     => $newCodeData,
                    'code_club'     => $request->get('code_club'),
                    'nis'           => $request->get('nis'),
                    'nama'          => $request->get('nama'),
                    'gender'        => $request->get('gender'),
                    'tempat_lahir'  => $request->get('tempat_lahir'),
                    'tanggal_lahir' => $tanggal_lahir,
                    'foto'          => null,
                ]);

                $otpAct = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1);
                $newCodeData_activity = ltrim(Carbon::now()->format('Ymdhis') . $otpAct, '0');

                Activity::create([
                    'id'          => Str::uuid(),
                    'code_data'   => $newCodeData_activity,
                    'code_user'   => $viewadmin->code_data ?? null,
                    'activity'    => 'Tambah data atlet [' . $request->get('nama') . ' - ' . $newCodeData . ']',
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

    public function viewatlet(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','masterdata')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','listatlet')->first();
            
            if($level_menu->access_rights == 'No' OR $level_action->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }

            $getdata['atlet'] = atlet::withCount('registrasi')->where('code_data', $request->code_data)->first();
            if($getdata['atlet']){        
                return response()->json(['status_message' => 'success','note' => 'Proses data berhasil','results' => $getdata]);
            }else{
                return response()->json(['status_message' => 'error','note' => 'Data tidak ditemukan','results' => $object]);
            }
        }

    }

    public function editatlet(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','listatlet')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','editatlet')->first();
            
            if($level_menu->access_rights == 'No' OR $level_action->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }

            $get_data['atlet'] = Atlet::where('code_data', $request->code_data)->first();
            if(!$get_data['atlet']){
                return response()->json(['status_message' => 'error','note' => 'Data tidak ditemukan','results' => $object]);
            }else{
                $validator = Validator::make($request->all(), [
                    'nis'           => 'required|string|max:200',
                    'nama'          => 'required|string|max:200',
                    'gender'        => 'required|string|max:200',
                    'tempat_lahir'  => 'required|string|max:200',
                    'code_club'     => 'required|string|max:200',
                ]);

                if($validator->fails()){return response()->json(['status_message' => 'error','note' => $validator->errors()]);}

                try {
                    DB::beginTransaction();

                    $bulan = [
                        'Januari' => 'January',
                        'Februari' => 'February',
                        'Maret' => 'March',
                        'April' => 'April',
                        'Mei' => 'May',
                        'Juni' => 'June',
                        'Juli' => 'July',
                        'Agustus' => 'August',
                        'September' => 'September',
                        'Oktober' => 'October',
                        'November' => 'November',
                        'Desember' => 'December',
                    ];

                    $input = $request->tanggal_lahir;
                    $input = str_replace(array_keys($bulan), array_values($bulan), $input);

                    $tanggal_lahir = Carbon::parse($input)->format('Y-m-d');

                    Atlet::where('code_data', $request->get('code_data'))
                        ->update([
                            'code_club'     => $request->get('code_club'),
                            'nis'           => $request->get('nis'),
                            'nama'          => $request->get('nama'),
                            'gender'        => $request->get('gender'),
                            'tempat_lahir'  => $request->get('tempat_lahir'),
                            'tanggal_lahir' => $tanggal_lahir,
                        ]);                      

                    if ($request->hasFile('logo_atlet')) {
                        $this->validate($request, [
                            'logo_atlet' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2548'
                        ]);

                        $imageName = 'AT-' . $request->code_data . '-' . time() . '.' . $request->logo_atlet->extension();
                        $request->logo_atlet->move(public_path('/themes/admin/AdminOne/image/public/'), $imageName);

                        Atlet::where('code_data', $request->code_data)->update(['foto' => $imageName]);

                        if (!empty($get_data['atlet']->foto)) {
                            File::delete(public_path('/themes/admin/AdminOne/image/public/' . $get_data['atlet']->foto));
                        }

                        $file = $request->file('logo_atlet');
                        $filenameOriginal = $file->getClientOriginalName();
                    }

                    $otp = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1);
                    $newCodeData_activity = ltrim(Carbon::now()->format('Ymdhis') . $otp, '0');

                    Activity::create([
                        'id'            => Str::uuid(),
                        'code_data'     => $newCodeData_activity,
                        'code_user'     => $viewadmin->code_data,
                        'activity'      => 'Update data atlet ['.$get_data['atlet']->nama.' - '.$get_data['atlet']->code_data.']',
                        'code_company'  => $viewadmin->code_company ?? null,
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

    public function deleteatlet(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','listatlet')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','deleteatlet')->first();
            if($level_menu->access_rights == 'No' OR $level_action->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }

            $getdata = Atlet::where('code_data', $request->code_data)->first();
            if(!$getdata){
                return response()->json(['status_message' => 'error','note' => 'Data tidak ditemukan','results' => $object]);
            }else{
                try {
                    DB::beginTransaction();

                    $oldFoto = $getdata->foto;
                    $nameAtlet = $getdata->nama;
                    $codeAtlet = $getdata->code_data;
                    
                    Atlet::where('code_data', $request->code_data)->delete();

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
                        'code_user'     => $viewadmin->code_data,
                        'activity'      => 'Hapus data atlet ['.$nameAtlet.' - '.$codeAtlet.']',
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

    // Club
    public function listclub(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','masterdata')->first();
            $level_sub_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','listclub')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','exportlistclub')->first();
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
            
            // $results['listdata'] = Club::where(function($query) use ($request) {
            //         $query->whereRaw('code_data ILIKE ?', ["%{$request->keysearch}%"])
            //         ->orWhereRaw('nama_club ILIKE ?', ["%{$request->keysearch}%"])
            //         ->orWhereRaw('kota_asal ILIKE ?', ["%{$request->keysearch}%"])
            //         ->orWhereRaw('kontak ILIKE ?', ["%{$request->keysearch}%"]);
            //     })
            //     ->orderBy('nama_club', 'ASC')
            //     ->paginate($vd ?? 20);

            // foreach($results['listdata'] as $key => $data){
            //     $results['count_used'][$data->code_data] = Atlet::where('code_club', $data->code_data)->count();
            // }

            $results['listdata'] = Club::withCount('atlet')
                ->when($request->keysearch, function ($query) use ($request) {
                    $query->where(function ($q) use ($request) {
                        $q->where('code_data', 'ILIKE', "%{$request->keysearch}%")
                        ->orWhere('nama_club', 'ILIKE', "%{$request->keysearch}%")
                        ->orWhere('kota_asal', 'ILIKE', "%{$request->keysearch}%")
                        ->orWhere('kontak', 'ILIKE', "%{$request->keysearch}%");
                    });
                })
                ->orderBy('nama_club', 'ASC')
                ->paginate($vd ?? 20);
                
            return response()->json(['status_message' => 'success','note' => 'Proses data berhasil','count_all_data' => $results['listdata']->total(),'count_view_data' => $vd,'keysearch' => $request->keysearch,'results' => $results]);
        }
    }

    public function newclub(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','listclub')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','newclub')->first();
            
            if($level_menu->access_rights == 'No' OR $level_action->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }

            $validator = Validator::make($request->all(), [
                'nama_club' => 'required|string|max:200|unique:db_clubs',
                'kota_asal' => 'required|string|max:200',
                'kontak' => 'required|string|max:200',
            ]);

            if($validator->fails()){
                return response()->json(['status_message' => 'error','note' => $validator->errors()]);
            }

            try {
                DB::beginTransaction();

                $otp = substr(str_shuffle(str_repeat('123456789', 4)), 0, 4);
                $dataAll = Club::orderBy('created_at', 'desc')->first();
                $countData = Club::count();

                if ($countData > 0 && $dataAll && isset($dataAll->code_data)) {
                    $lastNumber = (int) substr($dataAll->code_data, -4);
                    $incrementedNumber = $lastNumber + 1;
                } else {
                    $incrementedNumber = 1;
                }

                $formattedNumber = str_pad($incrementedNumber, 4, '0', STR_PAD_LEFT);
                $newCodeData = 'CL' . $otp . $formattedNumber;

                $savedata = Club::create([
                    'id'        => Str::uuid(),
                    'code_data' => $newCodeData,
                    'nama_club' => $request->get('nama_club'),
                    'kota_asal' => $request->get('kota_asal'),
                    'kontak'    => $request->get('kontak'),
                    'logo'      => null,
                ]);

                $otpAct = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1);
                $newCodeData_activity = ltrim(Carbon::now()->format('Ymdhis') . $otpAct, '0');

                Activity::create([
                    'id'          => Str::uuid(),
                    'code_data'   => $newCodeData_activity,
                    'code_user'   => $viewadmin->code_data ?? null,
                    'activity'    => 'Tambah data club [' . $request->get('nama_club') . ' - ' . $newCodeData . ']',
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

    public function viewclub(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','masterdata')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','listclub')->first();
            
            if($level_menu->access_rights == 'No' OR $level_action->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }

            $getdata['club'] = Club::withCount('atlet')->where('code_data', $request->code_data)->first();
            if($getdata['club']){            
                return response()->json(['status_message' => 'success','note' => 'Proses data berhasil','results' => $getdata]);
            }else{
                return response()->json(['status_message' => 'error','note' => 'Data tidak ditemukan','results' => $object]);
            }
        }

    }

    public function editclub(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','listclub')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','editclub')->first();
            
            if($level_menu->access_rights == 'No' OR $level_action->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }

            $get_data['club'] = Club::where('code_data', $request->code_data)->first();
            if(!$get_data['club']){
                return response()->json(['status_message' => 'error','note' => 'Data tidak ditemukan','results' => $object]);
            }else{
                $validator = Validator::make($request->all(), [
                    'nama_club' => 'required|string|max:200',
                    'kota_asal' => 'required|string|max:200',
                    'kontak'    => 'required|string|max:200',
                ]);

                if($validator->fails()){return response()->json(['status_message' => 'error','note' => $validator->errors()]);}

                if($request->nama_club != $get_data['club']->nama_club){
                    $validator = Validator::make($request->all(),[
                        'nama_club' => 'required|string|max:200|unique:db_clubs',
                    ]);

                    if($validator->fails()){return response()->json(['status_message' => 'error','note' => $validator->errors(),'results' => $object]);}
                }

                try {
                    DB::beginTransaction();

                    Club::where('code_data', $request->get('code_data'))
                        ->update([
                            'nama_club' => ucfirst($request->get('nama_club')),
                            'kota_asal'   => $request->get('kota_asal'),
                            'kontak'   => $request->get('kontak'),
                        ]);                       

                    if ($request->hasFile('logo_club')) {
                        $this->validate($request, [
                            'logo_club' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2548'
                        ]);

                        $imageName = 'LC-' . $request->code_data . '-' . time() . '.' . $request->logo_club->extension();
                        $request->logo_club->move(public_path('/themes/admin/AdminOne/image/public/'), $imageName);

                        Club::where('code_data', $request->code_data)->update(['logo' => $imageName]);

                        if (!empty($get_data['club']->logo)) {
                            File::delete(public_path('/themes/admin/AdminOne/image/public/' . $get_data['club']->logo));
                        }

                        $file = $request->file('logo_club');
                        $filenameOriginal = $file->getClientOriginalName();
                    }

                    $otp = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1);
                    $newCodeData_activity = ltrim(Carbon::now()->format('Ymdhis') . $otp, '0');

                    Activity::create([
                        'id'            => Str::uuid(),
                        'code_data'     => $newCodeData_activity,
                        'code_user'     => $viewadmin->code_data,
                        'activity'      => 'Update data club ['.$get_data['club']->nama_club.' - '.$get_data['club']->code_data.']',
                        'code_company'  => $viewadmin->code_company ?? null,
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

    public function deleteclub(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','listclub')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','deleteclub')->first();
            if($level_menu->access_rights == 'No' OR $level_action->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }

            $getdata = Club::where('code_data', $request->code_data)->first();
            if(!$getdata){
                return response()->json(['status_message' => 'error','note' => 'Data tidak ditemukan','results' => $object]);
            }else{
                try {
                    DB::beginTransaction();

                    $oldFoto = $getdata->logo;
                    $nameClub = $getdata->nama_club;
                    $codeClub = $getdata->code_data;
                    
                    Club::where('code_data', $request->code_data)->delete();

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
                        'code_user'     => $viewadmin->code_data,
                        'activity'      => 'Hapus data club ['.$nameClub.' - '.$codeClub.']',
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

    // Kategori - Gaya Renang
    public function listkategori(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','masterdata')->first();
            $level_sub_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','listkategori')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','exportkategori')->first();
            if($request->type == 'export'){
                if($level_action->access_rights == 'No'){
                    return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
                }
            }

            if($level_menu->access_rights == 'No' OR $level_sub_menu->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }

            $vd = intval($request->vd ?? 20);
            $vd = max(1, min($vd, 100)); // nilai minimal 1, maksimal 100
            
            $results['listdata'] = Kategori::where(function($query) use ($request) {
                    $query->whereRaw('code_data ILIKE ?', ["%{$request->keysearch}%"])
                    ->orWhereRaw('nama_gaya ILIKE ?', ["%{$request->keysearch}%"])
                    ->orWhereRaw('istilah ILIKE ?', ["%{$request->keysearch}%"]);
                })
                ->orderBy('nama_gaya', 'ASC')
                ->paginate($vd ?? 20);

            foreach($results['listdata'] as $key => $data){
                // $results['count_used'][$data->code_data] = Barang::where('kode_jenis', $data->code_data)->count();
                $results['count_used'][$data->code_data] = 0;
            }
                
            return response()->json(['status_message' => 'success','note' => 'Proses data berhasil','count_all_data' => $results['listdata']->total(),'count_view_data' => $vd,'keysearch' => $request->keysearch,'results' => $results]);
        }
    }

    public function newkategori(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','listkategori')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','newkategori')->first();
            
            if($level_menu->access_rights == 'No' OR $level_action->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }

            $validator = Validator::make($request->all(), [
                'nama_gaya' => 'required|string|max:200|unique:db_swimming_styles',
            ]);

            if($validator->fails()){return response()->json(['status_message' => 'error','note' => $validator->errors()]);}

            try {
                DB::beginTransaction();

                $otp = substr(str_shuffle(str_repeat('123456789', 4)), 0, 4);
                $dataAll = Kategori::orderBy('created_at', 'desc')->first();
                $countData = Kategori::count();

                if ($countData > 0 && $dataAll && isset($dataAll->code_data)) {
                    $lastNumber = (int) substr($dataAll->code_data, -4);
                    $incrementedNumber = $lastNumber + 1;
                } else {
                    $incrementedNumber = 1;
                }

                $formattedNumber = str_pad($incrementedNumber, 4, '0', STR_PAD_LEFT);
                $newCodeData = 'GR' . $otp . $formattedNumber;

                $savedata = Kategori::create([
                    'id'        => Str::uuid(),
                    'code_data' => $newCodeData,
                    'nama_gaya' => $request->get('nama_gaya'),
                    'istilah'   => $request->get('istilah'),
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
                    'activity'    => 'Tambah data kategori [' . $request->get('nama_gaya') . ' - ' . $newCodeData . ']',
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

    public function viewkategori(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','masterdata')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','listkategori')->first();
            
            if($level_menu->access_rights == 'No' OR $level_action->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }

            $getdata['kategori'] = Kategori::where('code_data', $request->code_data)->first();
            if($getdata['kategori']){ 
                // $count_used = Barang::where('kode_jenis', $getdata['kategori']->code_data)->count();   
                $count_used = 0;            
                return response()->json(['status_message' => 'success','note' => 'Proses data berhasil','results' => $getdata,'count_used' => $count_used]);
            }else{
                return response()->json(['status_message' => 'error','note' => 'Data tidak ditemukan','results' => $object]);
            }
        }

    }

    public function editkategori(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','listkategori')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','editkategori')->first();
            
            if($level_menu->access_rights == 'No' OR $level_action->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }

            $getdata = Kategori::where('code_data', $request->code_data)->first();
            if(!$getdata){
                return response()->json(['status_message' => 'error','note' => 'Data tidak ditemukan','results' => $object]);
            }else{
                $validator = Validator::make($request->all(), [
                    'nama_gaya' => 'required|string|max:200',
                ]);

                if($validator->fails()){return response()->json(['status_message' => 'error','note' => $validator->errors()]);}

                if($request->nama_gaya != $getdata->nama_gaya){
                    $validator = Validator::make($request->all(),[
                        'nama_gaya' => 'required|string|max:200|unique:db_swimming_styles'
                    ]);

                    if($validator->fails()){return response()->json(['status_message' => 'error','note' => $validator->errors(),'results' => $object]);}
                }

                try {
                    DB::beginTransaction();

                    $updatedata = Kategori::where('code_data', $request->get('code_data'))
                        ->update([
                            'nama_gaya' => ucfirst($request->get('nama_gaya')),
                            'istilah'   => $request->get('istilah'),
                        ]);

                    if (!$updatedata) {
                        DB::rollBack();
                        return response()->json(['status_message' => 'error','note' => 'Data gagal disimpan','results' => $object]);
                    }

                    $otp = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1);
                    $newCodeData_activity = ltrim(Carbon::now()->format('Ymdhis') . $otp, '0');

                    Activity::create([
                        'id'            => Str::uuid(),
                        'code_data'     => $newCodeData_activity,
                        'code_user'     => $viewadmin->code_data,
                        'activity'      => 'Update data kategori ['.$getdata->nama_gaya.' - '.$getdata->code_data.']',
                        'code_company'  => $viewadmin->code_company ?? null,
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

    public function deletekategori(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','listkategori')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','deletekategori')->first();
            if($level_menu->access_rights == 'No' OR $level_action->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }

            $getdata = Kategori::where('code_data', $request->code_data)->first();
            if(!$getdata){
                return response()->json(['status_message' => 'error','note' => 'Data tidak ditemukan','results' => $object]);
            }else{
                try {
                    DB::beginTransaction();

                    $DelData = $getdata->delete();
                    if (!$DelData) {
                        DB::rollBack();
                        return response()->json(['status_message' => 'error','note' => 'Data gagal dihapus','results' => $object]);
                    }

                    $otp = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1);
                    $newCodeData_activity = ltrim(Carbon::now()->format('Ymdhis') . $otp, '0');

                    Activity::create([
                        'id'            => Str::uuid(),
                        'code_data'     => $newCodeData_activity,
                        'code_user'     => $viewadmin->code_data,
                        'activity'      => 'Hapus data kategori ['.$getdata->nama_gaya.' - '.$getdata->code_data.']',
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

    // Kelompok Umur
    public function listku(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','masterdata')->first();
            $level_sub_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','listku')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','exportku')->first();
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
            
            $results['listdata'] = KelompokUmur::where(function($query) use ($request) {
                    $query->whereRaw('code_data ILIKE ?', ["%{$request->keysearch}%"])
                    ->orWhereRaw('code_kelompok ILIKE ?', ["%{$request->keysearch}%"])
                    ->orWhereRaw('nama_kelompok ILIKE ?', ["%{$request->keysearch}%"])
                    ->orWhereRaw('min_usia::text ILIKE ?', ["%{$request->keysearch}%"])
                    ->orWhereRaw('max_usia::text ILIKE ?', ["%{$request->keysearch}%"]);
                })
                ->orderBy('created_at', 'DESC')
                ->paginate($vd ?? 20);

            foreach($results['listdata'] as $key => $data){
                $results['count_used'][$data->code_data] = Event::where('code_kategori', $data->code_data)->count();
            }
                
            return response()->json(['status_message' => 'success','note' => 'Proses data berhasil','count_all_data' => $results['listdata']->total(),'count_view_data' => $vd,'keysearch' => $request->keysearch,'results' => $results]);
        }
    }

    public function newku(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','listku')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','newku')->first();
            
            if($level_menu->access_rights == 'No' OR $level_action->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }

            $validator = Validator::make($request->all(), [
                'code_kelompok' => 'required|string|max:200',
                'nama_kelompok' => 'required|string|max:200|unique:db_age_groups',
                'min_usia'      => 'required|string|max:200',
                'max_usia'      => 'required|string|max:200',
            ]);

            if($validator->fails()){return response()->json(['status_message' => 'error','note' => $validator->errors()]);}

            try {
                DB::beginTransaction();

                $otp = substr(str_shuffle(str_repeat('123456789', 4)), 0, 4);
                $dataAll = KelompokUmur::orderBy('created_at', 'desc')->first();
                $countData = KelompokUmur::count();

                if ($countData > 0 && $dataAll && isset($dataAll->code_data)) {
                    $lastNumber = (int) substr($dataAll->code_data, -4);
                    $incrementedNumber = $lastNumber + 1;
                } else {
                    $incrementedNumber = 1;
                }

                $formattedNumber = str_pad($incrementedNumber, 4, '0', STR_PAD_LEFT);
                $newCodeData = 'KU' . $otp . $formattedNumber;

                $savedata = KelompokUmur::create([
                    'id'        => Str::uuid(),
                    'code_data' => $newCodeData,
                    'code_kelompok' => $request->get('code_kelompok'),
                    'nama_kelompok' => $request->get('nama_kelompok'),
                    'min_usia' => $request->get('min_usia'),
                    'max_usia'   => $request->get('max_usia'),
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
                    'activity'    => 'Tambah data kelompok umur [' . $request->get('nama_kelompok') . ' - ' . $newCodeData . ']',
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

    public function viewku(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','masterdata')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','listku')->first();
            
            if($level_menu->access_rights == 'No' OR $level_action->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }

            $getdata['ku'] = KelompokUmur::where('code_data', $request->code_data)->first();
            if($getdata['ku']){ 
                $count_used = Event::where('code_kategori', $getdata['ku']->code_data)->count();          
                return response()->json(['status_message' => 'success','note' => 'Proses data berhasil','results' => $getdata,'count_used' => $count_used]);
            }else{
                return response()->json(['status_message' => 'error','note' => 'Data tidak ditemukan','results' => $object]);
            }
        }

    }

    public function editku(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','listku')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','editku')->first();
            
            if($level_menu->access_rights == 'No' OR $level_action->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }

            $getdata = KelompokUmur::where('code_data', $request->code_data)->first();
            if(!$getdata){
                return response()->json(['status_message' => 'error','note' => 'Data tidak ditemukan','results' => $object]);
            }else{
                $validator = Validator::make($request->all(), [
                    'code_kelompok' => 'required|string|max:200',
                    'nama_kelompok' => 'required|string|max:200',
                    'min_usia'      => 'required|string|max:200',
                    'max_usia'      => 'required|string|max:200',
                ]);

                if($validator->fails()){return response()->json(['status_message' => 'error','note' => $validator->errors()]);}

                if($request->nama_kelompok != $getdata->nama_kelompok){
                    $validator = Validator::make($request->all(),[
                        'code_kelompok' => 'required|string|max:200',
                        'nama_kelompok' => 'required|string|max:200|unique:db_age_groups',
                        'min_usia'      => 'required|string|max:200',
                        'max_usia'      => 'required|string|max:200',
                    ]);

                    if($validator->fails()){return response()->json(['status_message' => 'error','note' => $validator->errors(),'results' => $object]);}
                }

                try {
                    DB::beginTransaction();

                    $updatedata = KelompokUmur::where('code_data', $request->get('code_data'))
                        ->update([
                            'code_kelompok' => $request->get('code_kelompok'),
                            'nama_kelompok' => ucfirst($request->get('nama_kelompok')),
                            'min_usia'      => $request->get('min_usia'),
                            'max_usia'      => $request->get('max_usia'),
                        ]);

                    if (!$updatedata) {
                        DB::rollBack();
                        return response()->json(['status_message' => 'error','note' => 'Data gagal disimpan','results' => $object]);
                    }

                    $otp = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1);
                    $newCodeData_activity = ltrim(Carbon::now()->format('Ymdhis') . $otp, '0');

                    Activity::create([
                        'id'            => Str::uuid(),
                        'code_data'     => $newCodeData_activity,
                        'code_user'     => $viewadmin->code_data,
                        'activity'      => 'Update data kelompok umur ['.$getdata->nama_kelompok.' - '.$getdata->code_data.']',
                        'code_company'  => $viewadmin->code_company ?? null,
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

    public function deleteku(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','listku')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','deleteku')->first();
            if($level_menu->access_rights == 'No' OR $level_action->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }

            $getdata = KelompokUmur::where('code_data', $request->code_data)->first();
            if(!$getdata){
                return response()->json(['status_message' => 'error','note' => 'Data tidak ditemukan','results' => $object]);
            }else{
                try {
                    DB::beginTransaction();

                    $DelData = $getdata->delete();
                    if (!$DelData) {
                        DB::rollBack();
                        return response()->json(['status_message' => 'error','note' => 'Data gagal dihapus','results' => $object]);
                    }

                    $otp = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1);
                    $newCodeData_activity = ltrim(Carbon::now()->format('Ymdhis') . $otp, '0');

                    Activity::create([
                        'id'            => Str::uuid(),
                        'code_data'     => $newCodeData_activity,
                        'code_user'     => $viewadmin->code_data,
                        'activity'      => 'Hapus data kelompok umur ['.$getdata->nama_kelompok.' - '.$getdata->code_data.']',
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
}
