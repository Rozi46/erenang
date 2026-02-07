<?php

namespace App\Http\Controllers;

require '../vendor/autoload.php';

use App\Http\Controllers\Controller;
use App\Models\{Setting, Company, User, LevelAdmin, ListAkses, Activity, Atlet, Club, Championship, Event, Registrasi, KelompokUmur};
use Illuminate\Http\{Request, UploadedFile, Response};
use Illuminate\Support\Facades\{Hash, Validator, File, Http, Route, Session, Auth, DB, Lang};
use Illuminate\Support\{Carbon, Str};
use Illuminate\Database\Query\Builder;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Ramsey\Uuid\Uuid;

class ApiControllerRegister extends Controller
{
    public function listopatlet(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            // $results = Atlet::where('code_club',$request->code_club)->orderBy('nama', 'ASC')->get();
            // return response()->json(['status_message' => 'success','results' => $results]);
            $results = Atlet::select('code_data','nama')->where('code_club', $request->code_club)->orderBy('nama', 'ASC')->get();
            return response()->json($results);
        }
    }

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

    public function listopevent(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $getdata['championship'] = Championship::where('code_data',$request->code_championship)->first();
            $getdata['athlete'] = Atlet::where('code_data',$request->code_atlet)->first();

            $umur = Carbon::parse($getdata['athlete']->tanggal_lahir)->floatDiffInYears($getdata['championship']->tanggal_mulai);
            $umur = floor($umur); // ambil angka bulat sebelum koma
            
            $results = DB::table('db_events')
                ->Select('db_events.code_data', 'db_events.code_event')
                ->join('db_age_groups', 'db_events.code_kategori', '=', 'db_age_groups.code_data')
                ->where('db_age_groups.min_usia', '>=', $umur)
                ->where('db_events.code_kejuaraan', $request->code_championship)
                ->orderBy('db_events.code_event', 'ASC')
                ->get();

            return response()->json($results);
        }
    }

    public function saveregister(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','menuregister')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','inputregister')->first();                
            if($level_menu->access_rights == 'No' OR $level_action->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }
            $validator = Validator::make($request->all(), [
                'code_club'         => 'required|string|max:200',
                'code_atlete'       => 'required|string|max:200',
                'code_championship' => 'required|string|max:200',
                'code_event'        => 'required|array|min:1',
                'code_event.*'       => 'string|max:200',
            ]);
            if($validator->fails()){
                return response()->json(['status_message' => 'error','note' => $validator->errors()]);
            }

            $getdata['champion'] = Championship::Where('code_data',$request->code_championship)->first();
            $getdata['event'] = Event::Where('code_data',$request->code_event)->first();
            $getdata['atlet'] = Atlet::Where('code_data',$request->code_atlete)->first();

            $yearnow = Carbon::now()->year;

            $otp = substr(str_shuffle(str_repeat('123456789', 4)), 0, 4);
            $dataAll = Registrasi::WhereYear('created_at','=', $yearnow)->orderBy('created_at', 'DESC')->first();                   
            $countData = Registrasi::WhereYear('created_at','=', $yearnow)->count();  
            if ($countData > 0 && $dataAll && isset($dataAll->code_data)) {
                $lastNumber = (int) substr($dataAll->code_data, -4);
                $incrementedNumber = $lastNumber + 1;
            } else {
                $incrementedNumber = 1;
            }
            $formattedNumber = str_pad($incrementedNumber, 4, '0', STR_PAD_LEFT);
            $yearnow = ltrim(Carbon::now()->format('Ymdhis'), '0');
            $newCodeData = 'RGS-' .$yearnow .$otp .$formattedNumber;

            // if($count['suratcuti'] == 0){   
                try {DB::beginTransaction();

                    Registrasi::create([
                        'id'                => Str::uuid(),
                        'code_data'         => $newCodeData,
                        'code_champion'     => $request->code_championship,
                        'code_athlete'      => $request->code_atlete,
                        'code_event'        => json_encode($request->code_event),
                        'code_age_group'    => '-',
                        'status'            => 'pending',
                        'payment_status'    => 'not_required',
                        'document'          => json_encode(['-']),
                        'notes'             => '-',
                        'submitted_at'      => now(),
                        'verified_at'       => now(),
                        'code_user'         => $viewadmin->code_data ?? null,
                    ]); 

                    $otpAct = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1);
                    $newCodeData_activity = ltrim(Carbon::now()->format('Ymdhis') .$otpAct, '0');
                    Activity::create([
                        'id'          => Str::uuid(),
                        'code_data'   => $newCodeData_activity,
                        'code_user'   => $viewadmin->code_data ?? null,
                        'activity'    => 'Input pendaftaran [' . $getdata['atlet']->nama . ' - ' . $newCodeData . ']',
                        'code_company'=> $viewadmin->code_company ?? null,
                    ]);
                    
                    DB::commit();
                    return response()->json(['status_message' => 'success','note' => 'Data berhasil disimpan','results' => $object]);
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json(['status_message' => 'error','note' => 'Data gagal disimpan ' . $e->getMessage(),'results' => $object]);
                }
            // }else{
            //     return response()->json(['status_message' => 'error','note' => 'Data nomor surat cuti sudah terdaftars']);
            // }
        } 
    }

    public function historyregister(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','menuregister')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','histroryregister')->first();                
            if($level_menu->access_rights == 'No' OR $level_action->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }

            $vd = intval($request->vd ?? 20);
            $vd = max(1, min($vd, 100));
            
            $results['listdata'] = Registrasi::where(function($query) use ($request) {
                $query->whereRaw('code_data ILIKE ?', ["%{$request->keysearch}%"])
                ->orWhereRaw('code_athlete ILIKE ?', ["%{$request->keysearch}%"])
                ->orWhereRaw('code_event::text ILIKE ?', ["%{$request->keysearch}%"])  // bertipe JSON
                ->orWhereRaw('status ILIKE ?', ["%{$request->keysearch}%"]);
            })
            ->orderBy('created_at', 'ASC')
            ->paginate($vd ?? 20);

            foreach($results['listdata'] as $key => $data){
                // $results['count_used'][$data->code_data] = Event::where('code_kejuaraan', $data->code_data)->count();
                $results['count_used'][$data->code_data] = 0;
                $results['detail_champion'][$data->code_data] = Championship::where('code_data', $data->code_champion)->first();
                $results['detail_atlet'][$data->code_data] = Atlet::where('code_data', $data->code_athlete)->first();
                $results['detail_club'][$data->code_data] = Club::where('code_data', $results['detail_atlet'][$data->code_data]->code_club)->first();   
                // $results['detail_event'][$data->code_data] = Event::where('code_data', $data->code_event)->first();

                // //EVENT (JSON ARRAY)
                // $eventCodes = is_array($data->code_event)
                //     ? $data->code_event
                //     : json_decode($data->code_event, true);

                // $results['detail_event'][$data->code_data] =Event::whereIn('code_data', $eventCodes)->get();

                //KU (AMBIL DARI SEMUA EVENT)
                // $kodeKategori = $results['detail_event'][$data->code_data]->pluck('code_kategori')->unique()->values();
                // $results['detail_ku'][$data->code_data] =KelompokUmur::whereIn('code_data', $kodeKategori)->get();

                //EVENT (JSON ARRAY)
                $eventCodes = is_array($data->code_event)
                    ? $data->code_event
                    : json_decode($data->code_event, true);
                // Ambil event + KU sekaligus
                $results['detail_event'][$data->code_data] =
                    Event::with('kelompokUmur')
                        ->whereIn('code_data', $eventCodes)
                        ->get();
            }
                
            return response()->json(['status_message' => 'success','note' => 'Proses data berhasil','count_all_data' => $results['listdata']->total(),'count_view_data' => $vd,'keysearch' => $request->keysearch,'results' => $results]);
        } 
    }

    public function viewregister(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','menuregister')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','histroryregister')->first();                
            if($level_menu->access_rights == 'No' OR $level_action->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }
            
            $getdata['register'] = Registrasi::where('code_data', $request->code_data)->first();
            if($getdata['register']){   
                $getdata['atlet'] = Atlet::where('code_data',$getdata['register']->code_athlete)->first();  
                $getdata['club'] = Club::where('code_data',$getdata['atlet']->code_club)->first();
                $getdata['champion'] = Championship::where('code_data',$getdata['register']->code_champion)->first();
                $getdata['event'] = Event::where('code_data',$getdata['register']->code_event)->first();
                
                return response()->json(['status_message' => 'success','note' => 'Proses data berhasil','results' => $getdata]);
            }else{
                return response()->json(['status_message' => 'error','note' => 'Data tidak ditemukan','results' => $object]);
            }
        } 
    }

    public function editregister(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','menuregister')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','editregister')->first();                
            if($level_menu->access_rights == 'No' OR $level_action->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }
            $validator = Validator::make($request->all(), [
                'nama_club'         => 'required|string|max:200',
                'nama_atlet'       => 'required|string|max:200',
                'nama_kejuaraan' => 'required|string|max:200',
                'code_event'        => 'required|array|min:1',
                'code_event.*'       => 'string|max:200',
            ]);
            if($validator->fails()){
                return response()->json(['status_message' => 'error','note' => $validator->errors()]);
            }  
  
            try {DB::beginTransaction();
                Registrasi::where('code_data', $request->get('code_data'))
                    ->update([
                        'code_event'    => json_encode($request->code_event),
                    ]);

                $otpAct = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1);
                $newCodeData_activity = ltrim(Carbon::now()->format('Ymdhis') .$otpAct, '0');
                Activity::create([
                    'id'          => Str::uuid(),
                    'code_data'   => $newCodeData_activity,
                    'code_user'   => $viewadmin->code_data ?? null,
                    'activity'    => 'Update pendaftaran [' .  $request->get('nama_atlet') . ' - ' . $request->get('code_data') . ']',
                    'code_company'=> $viewadmin->code_company ?? null,
                ]);
                
                DB::commit();
                return response()->json(['status_message' => 'success','note' => 'Data berhasil disimpan','results' => $object]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['status_message' => 'error','note' => 'Data gagal disimpan ' . $e->getMessage(),'results' => $object]);
            }
        } 
    }

    public function verifiedregister(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','menuregister')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','editregister')->first();                
            if($level_menu->access_rights == 'No' OR $level_action->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }
            // $validator = Validator::make($request->all(), [
            //     'nama_club'         => 'required|string|max:200',
            //     'nama_atlet'       => 'required|string|max:200',
            //     'nama_kejuaraan' => 'required|string|max:200',
            //     'code_event'        => 'required|array|min:1',
            //     'code_event.*'       => 'string|max:200',
            // ]);
            // if($validator->fails()){
            //     return response()->json(['status_message' => 'error','note' => $validator->errors()]);
            // }  
            
            $getdata['register'] = Registrasi::where('code_data', $request->code_data)->first();
            if($getdata['register']){   
                $getdata['atlet'] = Atlet::where('code_data',$getdata['register']->code_athlete)->first();  
                $getdata['club'] = Club::where('code_data',$getdata['atlet']->code_club)->first();
                $getdata['champion'] = Championship::where('code_data',$getdata['register']->code_champion)->first();
                $getdata['event'] = Event::where('code_data',$getdata['register']->code_event)->first();
            }
  
            try {DB::beginTransaction();
                Registrasi::where('code_data', $request->code_data)
                    ->update([
                        'verified_at'   => now(),
                        'status'        => 'verified',
                    ]);

                $otpAct = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1);
                $newCodeData_activity = ltrim(Carbon::now()->format('Ymdhis') .$otpAct, '0');
                Activity::create([
                    'id'          => Str::uuid(),
                    'code_data'   => $newCodeData_activity,
                    'code_user'   => $viewadmin->code_data ?? null,
                    'activity'    => 'Konfirmasi pendaftaran [' .  $getdata['atlet']->nama . ' - ' . $request->get('code_data') . ']',
                    'code_company'=> $viewadmin->code_company ?? null,
                ]);
                
                DB::commit();
                return response()->json(['status_message' => 'success','note' => 'Data berhasil disimpan','results' => $object]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['status_message' => 'error','note' => 'Data gagal disimpan ' . $e->getMessage(),'results' => $object]);
            }
        } 
    }

    public function rejectedregister(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','menuregister')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','editregister')->first();                
            if($level_menu->access_rights == 'No' OR $level_action->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            } 
            
            $getdata['register'] = Registrasi::where('code_data', $request->code_data)->first();
            if($getdata['register']){   
                $getdata['atlet'] = Atlet::where('code_data',$getdata['register']->code_athlete)->first();  
                $getdata['club'] = Club::where('code_data',$getdata['atlet']->code_club)->first();
                $getdata['champion'] = Championship::where('code_data',$getdata['register']->code_champion)->first();
                $getdata['event'] = Event::where('code_data',$getdata['register']->code_event)->first();
            }
  
            try {DB::beginTransaction();
                Registrasi::where('code_data', $request->code_data)
                    ->update([
                        'verified_at'   => now(),
                        'status'        => 'rejected',
                    ]);

                $otpAct = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1);
                $newCodeData_activity = ltrim(Carbon::now()->format('Ymdhis') .$otpAct, '0');
                Activity::create([
                    'id'          => Str::uuid(),
                    'code_data'   => $newCodeData_activity,
                    'code_user'   => $viewadmin->code_data ?? null,
                    'activity'    => 'Rejected pendaftaran [' .  $getdata['atlet']->nama . ' - ' . $request->get('code_data') . ']',
                    'code_company'=> $viewadmin->code_company ?? null,
                ]);
                
                DB::commit();
                return response()->json(['status_message' => 'success','note' => 'Data berhasil disimpan','results' => $object]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['status_message' => 'error','note' => 'Data gagal disimpan ' . $e->getMessage(),'results' => $object]);
            }
        } 
    }
    
}
