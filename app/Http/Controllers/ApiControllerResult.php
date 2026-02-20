<?php

namespace App\Http\Controllers;

require '../vendor/autoload.php';

use App\Http\Controllers\Controller;
use App\Models\{Setting, Company, User, LevelAdmin, ListAkses, Activity, Result, Championship, HeatLine, Event};
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
                ->where('event.code_kejuaraan', $request->code_championship)
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
    public function listresult(Request $request)
    {
        date_default_timezone_set('Asia/Jakarta');
        $object = [];
        $viewadmin = User::where('id', $request->u)->where('key_token', $request->token)->first();
        if(!$viewadmin){ 
            return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan saat proses data']);
        }else{
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','menuhasilpertandingan')->first();
            $level_sub_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','listresult')->first();
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

            $results['listdata'] = Result::with(['heatLine', 'atlet', 'event'])
                ->when($keysearch, function ($query) use ($keysearch) {
                    $query->where(function ($q) use ($keysearch) {
                        $q->where('code_data', 'ILIKE', "%{$keysearch}%")
                        ->orWhere('best_time', 'ILIKE', "%{$keysearch}%")
                        ->orWhere('hasil', 'ILIKE', "%{$keysearch}%")
                        ->orWhere('ranking', 'ILIKE', "%{$keysearch}%")
                        ->orWhere('catatan', 'ILIKE', "%{$keysearch}%")
                        ->orWhereHas('atlet', function ($qa) use ($keysearch) {
                            $qa->where('nama', 'ILIKE', "%{$keysearch}%");
                        })
                        ->orWhereHas('event', function ($qb) use ($keysearch) {
                            $qb->where('code_event', 'ILIKE', "%{$keysearch}%");
                        });
                    });
                })
                ->orderBy('ranking', 'ASC')
                ->paginate($vd ?? 20);
                
            return response()->json(['status_message' => 'success','note' => 'Proses data berhasil','count_all_data' => $results['listdata']->total(),'count_view_data' => $vd,'keysearch' => $request->keysearch,'results' => $results]);
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
            $level_sub_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','listresult')->first();

            if($level_menu->access_rights == 'No' OR $level_sub_menu->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }   

            // $results['listdata'] = HeatLine::with(['heat.event', 'atlet'])
            //     ->whereHas('heat.event', function ($q) use ($request) {
            //         $q->where('code_kejuaraan', $request->code_championship)
            //         ->where('code_data', $request->code_event);
            //     })
            //     ->orderBy('heat.nomor_seri', 'heatLine.line_number', 'ASC')
            //     ->get();

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
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','listresult')->first();
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
                return response()->json(['status_message' => 'success','note' => 'Data berhasil disimpan','results' => $object,'code_data' => $newCodeData,], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['status_message' => 'error','note' => 'Terjadi kesalahan: ' . $e->getMessage(),'results' => $object,'code_data' => $object,], 500);
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
            $level_menu = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','listresult')->first();
            $level_action = LevelAdmin::where('code_data', $viewadmin->level)->where('data_menu','=','inputresult')->first();
            
            if($level_menu->access_rights == 'No' OR $level_action->access_rights == 'No'){
                return response()->json(['status_message' => 'error','note' => 'Tidak ada akses','results' => $object]);
            }

            $getdata['detail_championship'] = Championship::where('code_data', $request->code_championship)->first();
            $getdata['detail_event'] = Event::where('code_data', $request->code_event)->first();

            // $getdata['result'] = Result::with(['heatLine.heat.event','atlet'])
            //     ->where('code_data', $request->code_data)
            //     ->where('code_event', $request->code_event)
            //     ->get()
            //     ->sortBy([
            //         // fn($r) => $r->heatLine->heat->nomor_seri ?? 999,
            //         // fn($r) => $r->heatLine->line_number ?? 999,
            //         fn($r) => (int) ($r->heatLine?->heat?->nomor_seri ?? 999),
            //         fn($r) => (int) ($r->heatLine?->line_number ?? 999),
            //     ])
            //     ->values();

            $getdata['result'] = Result::query()
                ->with(['heatLine.heat.event','atlet'])
                ->join('db_heat_lines as hl', 'hl.code_data', '=', 'db_results.code_heatline')
                ->join('db_heats as h', 'h.code_data', '=', 'hl.code_heat')
                ->where('db_results.code_data', $request->code_data)
                ->where('db_results.code_event', $request->code_event)
                ->orderByRaw('CAST(h.nomor_seri AS INTEGER) ASC')
                ->orderByRaw('CAST(hl.line_number AS INTEGER) ASC')
                ->select('db_results.*')
                ->get();

            // $getdata['result'] = Result::where('code_data', $request->code_data)->where('code_event', $request->code_event)->get();

            if(!$getdata['result']){
                return response()->json(['status_message' => 'error','note' => 'Data tidak ditemukan','results' => $object]);
            }else{
                return response()->json(['status_message' => 'success','note' => 'Proses data berhasil','results' => $getdata]);
            }
        }

    }
}
