<?php

namespace App\Http\Controllers;

require '../vendor/autoload.php';

use App\Http\Controllers\Controller;
use App\Models\{Setting, Company, User, LevelAdmin, ListAkses, Activity, Result, Championship, HeatLine, Heat, Event};
use Illuminate\Http\{Request, UploadedFile, Response};
use Illuminate\Support\Facades\{Hash, Validator, File, Http, Route, Session, Auth, DB, Lang};
use Illuminate\Support\{Carbon, Str};
use Illuminate\Database\Query\Builder;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Ramsey\Uuid\Uuid;

class ApiControllerResult extends Controller
{
    // Select 2
    public function listopchampionship(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $results = Championship::select('code_data','nama_kejuaraan')->whereDate('tanggal_mulai', '<', Carbon::today())->orderBy('nama_kejuaraan', 'ASC')->get();            
            return response()->json(['status_message' => 'success','results' => $results]);
        }
    }

    public function listopeventresult(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{ 
            $results = HeatLine::join('db_heats as heat', 'heat.code_data', '=', 'db_heat_lines.code_heat')
                ->join('db_events as event', 'event.code_data', '=', 'heat.code_event')
                ->leftJoin('db_results as r', 'r.code_event', '=', 'event.code_data')
                ->where('event.code_kejuaraan', $request->code_championship)
                ->whereNull('r.code_event')
                ->groupBy('event.code_data', 'event.code_event')
                ->orderBy('event.code_event', 'ASC')
                ->select(
                    'event.code_data',
                    'event.code_event'
                )
                ->get();

            return response()->json($results);
        }
    }

    // Hasil Pertandingan
    public function historyresult(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','menuhasilpertandingan')->first();
            $level_sub_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','menudatahasilpertandingan')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','exportresult')->first();
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
            $keysearch = $request->keysearch;      

            // $results['listdata'] = Result::with(['heatLine.heat', 'atlet.club', 'event.championship'])
            //     ->when($keysearch, function ($query) use ($keysearch) {
            //         $query->where(function ($q) use ($keysearch) {
            //             $q->where('code_data', 'ILIKE', "%{$keysearch}%")
            //             ->orWhere('hasil', 'ILIKE', "%{$keysearch}%")
            //             ->orWhere('ranking', 'ILIKE', "%{$keysearch}%")
            //             ->orWhere('catatan', 'ILIKE', "%{$keysearch}%")                        
            //             ->orWhereHas('atlet', function ($qa) use ($keysearch) {
            //                 $qa->where('nama', 'ILIKE', "%{$keysearch}%");
            //             })
            //             ->orWhereHas('event', function ($qb) use ($keysearch) {
            //                 $qb->where('code_event', 'ILIKE', "%{$keysearch}%");
            //             })
            //             ->orWhereHas('heatline', function ($qc) use ($keysearch) {
            //                 $qc->where('best_time', 'ILIKE', "%{$keysearch}%");
            //             });
            //         });
            //     })
            //     ->orderBy('ranking', 'ASC')
            //     ->paginate($vd ?? 20);

            $results['listdata'] = \App\Models\Result::with(['heatLine.heat','event.championship'])
                ->when($keysearch, function ($query) use ($keysearch) {
                    $query->where(function ($q) use ($keysearch) {
                        $q->whereHas('event', function ($qe) use ($keysearch) {
                            $qe->where('code_event', 'ILIKE', "%{$keysearch}%")
                            ->orWhereHas('championship', function ($qc) use ($keysearch) {
                                $qc->where('nama_kejuaraan', 'ILIKE', "%{$keysearch}%");
                            });
                        });
                    });
                })
                ->select('code_event','code_data') // grouping event
                ->groupBy('code_event','code_data')
                ->orderBy('code_event')
                ->paginate($vd ?? 20);

            foreach($results['listdata'] as $key => $data){ 
                $code_event = $data->code_event;

                $results['listdata_result'] = Result::with(['heatLine.heat','atlet.club','event.championship'])
                    ->where('code_event', $code_event)
                    ->orderBy('ranking', 'ASC')
                    ->get();
            }
                
            return response()->json(['status_message' => 'success','note' => 'Proses data berhasil','count_all_data' => $results['listdata']->total(),'count_view_data' => $vd,'keysearch' => $request->keysearch,'results' => $results]);
        }
    }

    public function detailResult(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','menuhasilpertandingan')->first();
            $level_sub_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','menudatahasilpertandingan')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','exportresult')->first();

            if($level_menu->access_rights == 'No' OR $level_sub_menu->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }

            $code_event = $request->code_event;

            $getdata = Result::with(['heatLine.heat','atlet.club','event.championship'])
                ->where('code_event', $code_event)
                ->orderBy('ranking')
                ->get();

            return response()->json(['status_message' => 'success','note' => 'Proses data berhasil','results' => $getdata]);
        }
    }

    public function listdataevent(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','menuhasilpertandingan')->first();
            $level_sub_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','menudatahasilpertandingan')->first();

            if($level_menu->access_rights == 'No' OR $level_sub_menu->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }  

            $results['listdata'] = HeatLine::query()
                ->with(['heat.event', 'atlet'])
                ->join('db_heats as heat', 'heat.code_data', '=', 'db_heat_lines.code_heat')
                ->join('db_events as event', 'event.code_data', '=', 'heat.code_event')
                ->where('event.code_kejuaraan', $request->code_championship)
                ->where('event.code_data', $request->code_event)
                ->orderBy('heat.nomor_seri')
                ->orderBy('db_heat_lines.line_number')
                ->select('db_heat_lines.*')
                ->get();

            return response()->json(['status_message' => 'success','note' => 'Proses data berhasil','results' => $results]);
        }
    }

    public function saveresult(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','menudatahasilpertandingan')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','inputresult')->first();
            
            if($level_menu->access_rights == 'No' OR $level_action->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }

            $validator = Validator::make($request->all(), [
                'code_championship' => 'required|string|max:200',
                'code_event'        => 'required|string|max:200',
                'hasil_up'             => ['required','regex:/^\d{2}:\d{2}\.\d{2}$/']
            ]);

            if($validator->fails()){
                return response()->json(['status_message' => 'error','note' => $validator->errors()]);
            }

            try {
                DB::beginTransaction();

                $counttransaksi = Result::where('code_event', $request->get('code_event'))->count();

                if($counttransaksi == 0){
                    $otpAct = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1);
                    $newCodeData = 'RS' . ltrim(Carbon::now()->format('Ymdhis') . $otpAct, '0');

                    $getdata['heatline'] = HeatLine::query()
                        ->with(['heat.event', 'atlet'])
                        ->join('db_heats as heat', 'heat.code_data', '=', 'db_heat_lines.code_heat')
                        ->join('db_events as event', 'event.code_data', '=', 'heat.code_event')
                        ->where('event.code_kejuaraan', $request->code_championship)
                        ->where('event.code_data', $request->code_event)
                        ->orderBy('heat.nomor_seri')
                        ->orderBy('db_heat_lines.line_number')
                        ->select('db_heat_lines.*')
                        ->get();                        
                        
                    foreach($getdata['heatline'] as $key => $list_heatline){
                        $savedata = Result::create([
                            'id'            => Str::uuid(),
                            'code_data'     => $newCodeData,
                            'code_heatline' => $list_heatline->code_data,
                            'code_athlete'  => $list_heatline->code_athlete,
                            'code_event'    => $list_heatline->heat->code_event,
                            'hasil'         => '00:00.00',
                            'ranking'       => 0,
                            'catatan'       => '',
                        ]);
                    }

                    $update_heatline = Result::Where('code_athlete', $request->code_athlete)
                        ->where('code_event', $request->code_event)
                        ->update(['hasil' => $request->hasil_up]);
                }else{
                    $newCodeData = $request->code_data;
                    $update_heatline = Result::Where('code_athlete', $request->code_athlete)
                        ->where('code_event', $request->code_event)
                        ->update(['hasil' => $request->hasil_up]);
                }

                $otpAct = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1);
                $newCodeData_activity = ltrim(Carbon::now()->format('Ymdhis') . $otpAct, '0');

                Activity::create([
                    'id'          => Str::uuid(),
                    'code_data'   => $newCodeData_activity,
                    'code_user'   => $viewadmin->code_data ?? null,
                    'activity'    => 'Tambah data hasil pertandingan [' . $request->get('code_event') . ' - ' . $newCodeData . ']',
                    'code_company'=> $viewadmin->code_company ?? null,
                ]);

                DB::commit();
                return response()->json(['status_message' => 'success','note' => 'Data berhasil disimpan','results' => $object,'code_data' => $newCodeData], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan: ' . $e->getMessage(),'results' => $object,'code_data' => $object], 500);
            }
        }
    }

    public function viewresult(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','menudatahasilpertandingan')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','inputresult')->first();
            
            if($level_menu->access_rights == 'No' OR $level_action->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }

            $getdata['detail_championship'] = Championship::where('code_data', $request->code_championship)->first();
            $getdata['detail_event'] = Event::where('code_data', $request->code_event)->first();

            $getdata['result'] = Result::query()
                ->with(['heatLine.heat.event','atlet'])
                ->where('code_data', $request->code_data)
                ->where('code_event', $request->code_event)
                ->orderBy(
                    Heat::selectRaw('CAST(nomor_seri AS INTEGER)')
                        ->join('db_heat_lines', 'db_heat_lines.code_heat', '=', 'db_heats.code_data')
                        ->whereColumn('db_heat_lines.code_data', 'db_results.code_heatline')
                )
                ->orderBy(
                    HeatLine::selectRaw('CAST(line_number AS INTEGER)')
                        ->whereColumn('db_heat_lines.code_data', 'db_results.code_heatline')
                )
                ->get();

            if(!$getdata['result']){
                return response()->json(['status_message' => 'error','note' => 'Data tidak ditemukan','results' => $object]);
            }else{
                return response()->json(['status_message' => 'success','note' => 'Proses data berhasil','results' => $getdata]);
            }
        }
    }

    public function uploadfotoresult_old(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','menudatahasilpertandingan')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','inputresult')->first();
            
            if($level_menu->access_rights == 'No' OR $level_action->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }   

            try {
                DB::beginTransaction();

                $newCodeData = $request->code_data; 
                $imageName = null;
                $imageName = $request->file('foto');
                if ($request->hasFile('foto')) {
                    $imageName = 'FR-'.$request->id.'-'.time().'.'.$imageName->extension();
                    $imageName->move(public_path('/themes/admin/AdminOne/image/upload/'), $imageName);

                    Result::Where('code_athlete', $request->code_athlete)->where('code_event', $request->code_event)
                    ->update([
                        'foto' => $imageName,
                    ]);

                    if (!empty($viewadmin->image)) {
                        File::delete(public_path('/themes/admin/AdminOne/image/upload/'.$viewadmin->image));
                    }
                }

                $otpAct = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1);
                $newCodeData_activity = ltrim(Carbon::now()->format('Ymdhis') . $otpAct, '0');

                Activity::create([
                    'id'          => Str::uuid(),
                    'code_data'   => $newCodeData_activity,
                    'code_user'   => $viewadmin->code_data ?? null,
                    'activity'    => 'Upload foto hasil pertandingan [' . $request->get('code_event') . ' - ' . $newCodeData . ']',
                    'code_company'=> $viewadmin->code_company ?? null,
                ]);

                DB::commit();
                return response()->json(['status_message' => 'success','note' => 'Data berhasil disimpan','results' => $object,'code_data' => $newCodeData,], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan: ' . $e->getMessage(),'results' => $object,'code_data' => $object,], 500);
            }
        }
    }

    public function uploadfotoresult(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];

        $viewadmin = User::where('id',$request->u)->where('key_token',$request->token)->first();
        if(!$viewadmin){
            return response()->json(['status_message'=>'error','note'=>'Terjadi kesalahan saat proses data']);
        }

        $level_menu = LevelAdmin::where('code_data',$viewadmin->level)->where('data_menu','menudatahasilpertandingan')->first();
        $level_action = LevelAdmin::where('code_data',$viewadmin->level) ->where('data_menu','inputresult')->first();

        if($level_menu->access_rights=='No' || $level_action->access_rights=='No'){
            return response()->json(['status_message'=>'error','note'=>'Tidak ada akses' ]);
        }

        try{
            DB::beginTransaction();

            if(!$request->hasFile('foto')){
                return response()->json(['status_message'=>'error','note'=>'File tidak ditemukan']);
            }

            $file = $request->file('foto');
            $result = Result::where('id',$request->id)->first();

            if(!$result){
                return response()->json(['status_message'=>'error','note'=>'Data foto result tidak ditemukan']);
            }

            if($result->foto){
                File::delete(public_path('/themes/admin/AdminOne/image/upload/'.$result->foto));
            }

            $imageName = 'FR-'.$request->id.'-'.time().'.'.$file->getClientOriginalExtension();
            $file->move(public_path('/themes/admin/AdminOne/image/upload/'),$imageName );

            $result->update([
                'foto'=>$imageName
            ]);

            $otpAct = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'),1);
            $newCodeData_activity = ltrim(Carbon::now()->format('YmdHis').$otpAct,'0');

            Activity::create([
                'id'=>Str::uuid(),
                'code_data'=>$newCodeData_activity,
                'code_user'=>$viewadmin->code_data ?? null,
                'activity'=>'Upload foto hasil pertandingan ['.$result->code_event.' - '.$result->code_data.']',
                'code_company'=>$viewadmin->code_company ?? null,
            ]);

            DB::commit();
            return response()->json(['status_message'=>'success','note'=>'Foto berhasil diupload','url'=>asset('/themes/admin/AdminOne/image/upload/'.$imageName),'code_data'=>$result->code_data]);

        }catch(\Exception $e){
            DB::rollBack();
            return response()->json(['status_message'=>'error','note'=>$e->getMessage()],500);
        }
    }

    public function savecatatan(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','menudatahasilpertandingan')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','inputresult')->first();
            
            if($level_menu->access_rights == 'No' OR $level_action->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }

            try {
                DB::beginTransaction();   

                $result = Result::where('id',$request->id)->first();
                if(!$result){
                    return response()->json(['status_message'=>'error','note'=>'Data foto result tidak ditemukan','code_data' => $object]);
                }

                $result->update([
                    'catatan'=>$request->catatan
                ]);

                $otpAct = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1);
                $newCodeData_activity = ltrim(Carbon::now()->format('Ymdhis') . $otpAct, '0');

                Activity::create([
                    'id'          => Str::uuid(),
                    'code_data'   => $newCodeData_activity,
                    'code_user'   => $viewadmin->code_data ?? null,
                    'activity'    => 'Tambah catatan hasil pertandingan ['.$result->code_event.' - '.$result->code_data.']',
                    'code_company'=> $viewadmin->code_company ?? null,
                ]);

                DB::commit();
                return response()->json(['status_message' => 'success','note' => 'Data berhasil disimpan','results' => $object,'code_data' => $result->code_data], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan: ' . $e->getMessage(),'results' => $object,'code_data' => $object], 500);
            }
        }
    }

    public function saveresultlist(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','menudatahasilpertandingan')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','inputresult')->first();
            
            if($level_menu->access_rights == 'No' OR $level_action->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }

            $validator = Validator::make($request->all(), [
                'code_championship' => 'required|string|max:200',
                'code_event'        => 'required|string|max:200',
            ]);

            if($validator->fails()){
                return response()->json(['status_message' => 'error','note' => $validator->errors()]);
            }

            try {
                DB::beginTransaction();

                $codeData = $request->get('code_data');
                $codeChampionship = $request->get('code_championship');
                $codeEvent = $request->get('code_event');

                $invalidExists = Result::where('code_data', $codeData)
                    ->where(function ($q) {
                        $q->where(function ($q2) {
                            $q2->where(function ($q3) {
                                    $q3->whereNull('hasil')
                                    ->orWhere('hasil', '00:00.00');
                                })
                            ->whereNotNull('foto');
                        })
                        ->orWhere(function ($q2) {
                            $q2->whereNotNull('hasil')
                            ->where('hasil', '!=', '00:00.00')
                            ->whereNull('foto');
                        });
                    })
                    ->exists();

                if ($invalidExists) { 
                    throw new \Exception('Data hasil dan foto tidak sesuai aturan');
                }

                $affected = Result::where('code_data', $codeData)
                    ->update([
                        'status_data' => 'Finish'
                    ]);

                if ($affected === 0) {
                    return response()->json(['status_message' => 'error','note' => 'Data tidak ditemukan','code_data' => $codeData,'code_championship' => $codeChampionship,'code_event' => $codeEvent]);
                }

                $rows = Result::where('code_event', $codeEvent)
                    ->where(function ($q) {
                        $q->whereNull('catatan')
                        ->orWhere('catatan', '');
                    })
                    ->whereNotNull('hasil')
                    ->where('hasil', '!=', '00:00.00')
                    ->orderByRaw("
                        split_part(hasil, ':', 1)::int * 60 +
                        split_part(split_part(hasil, ':', 2), '.', 1)::int +
                        split_part(hasil, '.', 2)::int / 100.0
                    ASC")
                    ->get();

                $poinMap = [
                    1 => 5,
                    2 => 3,
                    3 => 1
                ];

                $ranking = 0;
                $lastTime = null;
                $posisi = 0;

                foreach ($rows as $row) {
                    $posisi++;

                    [$minSec, $ms] = explode('.', $row->hasil);
                    [$min, $sec] = explode(':', $minSec);
                    $timeValue = ($min * 60) + $sec + ($ms / 100);

                    if ($lastTime === null || $timeValue != $lastTime) {
                        $ranking = $posisi;
                    }

                    $poin = $poinMap[$ranking] ?? 0;

                    $row->update([
                        'ranking' => $ranking,
                        'poin'    => $poin
                    ]);

                    $lastTime = $timeValue;
                }

                Result::where('code_event', $codeEvent)
                    ->where(function ($q) {
                        $q->whereIn('catatan', ['DNF','DSQ','NS'])
                        ->orWhereNull('hasil')
                        ->orWhere('hasil', '00:00.00');
                    })
                    ->update([
                        'ranking' => 0,
                        'poin'    => 0
                    ]);

                Event::where('code_data', $codeEvent)
                    ->update([
                        'status_data' => 'Finish'
                    ]);

                $otpAct = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1);
                $newCodeData_activity = ltrim(Carbon::now()->format('YmdHis') . $otpAct, '0');

                Activity::create([
                    'id'           => Str::uuid(),
                    'code_data'    => $newCodeData_activity,
                    'code_user'    => $viewadmin->code_data ?? null,
                    'activity'     => 'Simpan data dan selesai hasil pertandingan ['.$request->get('code_event').' - '.$codeData.']',
                    'code_company' => $viewadmin->code_company ?? null,
                ]);

                DB::commit();
                return response()->json(['status_message' => 'success','note' => 'Data berhasil disimpan','code_data' => $codeData,'code_championship' => $codeChampionship,'code_event' => $codeEvent], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan: ' . $e->getMessage(),'code_data' => $codeData,'code_championship' => $codeChampionship,'code_event' => $codeEvent], 500);
            }
        }
    }

    public function printresult(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','menudatahasilpertandingan')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','historyresult')->first();
            
            if($level_menu->access_rights == 'No' OR $level_action->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }            
                        
            $getdata['championship'] = Championship::with(['event'])->where('code_data', $request->code_data)->first();
            $code_championship = $request->code_data;
            if(!$getdata['championship']){
                return response()->json(['status_message' => 'failed','note' => 'Data tidak ditemukan','results' => $object]);
            }else{
                $getdata['detail_perusahaan'] = Company::where('code_data', $viewadmin->code_company)->first();

                $getdata['events'] = Event::with([
                        'championship:id,code_data,nama_kejuaraan',
                        'kategori:id,code_data,nama_gaya',
                        'kelompokUmur:id,code_data,nama_kelompok',
                        'result' => fn($q) => $q->orderBy('ranking'),
                        'result.atlet.club',
                        'result.heatLine'
                    ])
                    ->where('code_kejuaraan', $code_championship)
                    ->orderBy('tanggal')
                    ->orderBy('code_event')
                    ->get();

                return response()->json(['status_message' => 'success','note' => 'Proses data berhasil','results' => $getdata]);
            }
        }
    }

    public function updatebesttimeupheatline(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','menudatahasilpertandingan')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','inputresult')->first();
            
            if($level_menu->access_rights == 'No' OR $level_action->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }

            $validator = Validator::make($request->all(), [
                'besttime_up' => ['required','regex:/^\d{2}:\d{2}\.\d{2}$/']
            ]);

            if($validator->fails()){
                return response()->json(['status_message' => 'error','note' => $validator->errors()]);
            }

            try {
                DB::beginTransaction();                

                // Validasi minimal
                if (!$request->code_data) {
                    throw new \Exception('Data tidak ditemukan');
                }

                // Update best time
                $updated = HeatLine::where('code_data', $request->code_data)
                    ->update(['best_time' => $request->besttime_up]);

                if (!$updated) {
                    throw new \Exception('Data heatline tidak ditemukan atau gagal update');
                }

                
                $newCodeData = $request->code_data;

                $otpAct = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 1);
                $newCodeData_activity = ltrim(Carbon::now()->format('Ymdhis') . $otpAct, '0');

                Activity::create([
                    'id'          => Str::uuid(),
                    'code_data'   => $newCodeData_activity,
                    'code_user'   => $viewadmin->code_data ?? null,
                    'activity'    => 'Simpan data best time pada seri lomba [' .  $request->code_data . ']',
                    'code_company'=> $viewadmin->code_company ?? null,
                ]);

                DB::commit();
                return response()->json(['status_message' => 'success','note' => 'Data berhasil disimpan','results' => $object,'code_data' => $newCodeData], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan: ' . $e->getMessage(),'results' => $object,'code_data' => $object], 500);
            }
        }
    }
}
