<?php
require __DIR__.'/api.php';

use Illuminate\Support\Facades\Route;

Route::get('/admin', function () {
    // return view('welcome']);
    return redirect()->route('administration');
});

Route::get('/admin/administration', function () {
    if (Session::get('admin_login_renang')) {
        return redirect()->route('dash');
	}else{
		return view('admin.AdminOne.login', ['url' => 'login']);
		// return view('maintenance']);
    }
})->name('administration');
Route::get('/admin/login', function () { 
    return redirect()->route('administration');
})->name('login');

Route::post('/admin/login',[\App\Http\Controllers\SistemController::class, 'login']);
Route::get('/admin/logout',[\App\Http\Controllers\SistemController::class, 'logout']);
Route::get('/admin/dash',[\App\Http\Controllers\SistemController::class, 'dash'])->name('dash');

// Route::group(['middleware' => 'auth.jwt'], function(){
	// Data Kejuaraan
	Route::get('/admin/listchampionship',[\App\Http\Controllers\SistemController::class, 'listchampionship']);
	Route::get('/admin/exportchampionship',[\App\Http\Controllers\SistemController::class, 'exportchampionship']);
	Route::get('/admin/newchampionship',[\App\Http\Controllers\SistemController::class, 'newchampionship']);
	Route::post('/admin/newchampionship',[\App\Http\Controllers\ActionController::class, 'newchampionship']);
	Route::get('/admin/editchampionship',[\App\Http\Controllers\SistemController::class, 'editchampionship']);
	Route::post('/admin/editchampionship',[\App\Http\Controllers\ActionController::class, 'editchampionship']);
	Route::get('/admin/deletechampionship',[\App\Http\Controllers\ActionController::class, 'deletechampionship']);
	
	// Data Hasil Pertandingan
	Route::get('/admin/menudatahasilpertandingan',[\App\Http\Controllers\SistemController::class, 'menudatahasilpertandingan']);
	Route::get('/admin/historyresult',[\App\Http\Controllers\SistemController::class, 'menudatahasilpertandingan']);
	Route::get('/admin/inputresult',[\App\Http\Controllers\SistemController::class, 'inputresult']);
	Route::get('/admin/geteventresult',[\App\Http\Controllers\SistemController::class, 'getopeventresult']);
	Route::get('/admin/listdataevent',[\App\Http\Controllers\SistemController::class, 'listdataevent']);
	Route::post('/admin/saveresult',[\App\Http\Controllers\ActionController::class, 'saveresult']);
	Route::get('/admin/viewresult',[\App\Http\Controllers\SistemController::class, 'viewresult']);
	Route::get('/admin/listdataresult',[\App\Http\Controllers\SistemController::class, 'listdataresult']);
	Route::post('/admin/uploadfotoresult',[\App\Http\Controllers\ActionController::class, 'uploadfotoresult']);
	Route::post('/admin/savecatatan',[\App\Http\Controllers\ActionController::class, 'savecatatan']);
	Route::post('/admin/saveresultlist',[\App\Http\Controllers\ActionController::class, 'saveresultlist']);

	// Data Pendafataran
	Route::get('/admin/menuregister',[\App\Http\Controllers\SistemController::class, 'menuregister']);
	Route::get('/admin/getatlete',[\App\Http\Controllers\SistemController::class, 'getopatlete']);
	Route::get('/admin/getevent',[\App\Http\Controllers\SistemController::class, 'getopevent']);
	Route::post('/admin/saveregister',[\App\Http\Controllers\ActionController::class, 'saveregister']);
	Route::get('/admin/histroryregister',[\App\Http\Controllers\SistemController::class, 'histroryregister']);
	Route::get('/admin/viewregister',[\App\Http\Controllers\SistemController::class, 'viewregister']);
	Route::post('/admin/editregister',[\App\Http\Controllers\ActionController::class, 'editregister']);
	Route::post('/admin/verifiedregister',[\App\Http\Controllers\ActionController::class, 'verifiedregister']);
	Route::post('/admin/rejectedregister',[\App\Http\Controllers\ActionController::class, 'rejectedregister']);

	// Event
	Route::get('/admin/listevent',[\App\Http\Controllers\SistemController::class, 'listevent']);
	Route::get('/admin/exportevent',[\App\Http\Controllers\SistemController::class, 'exportevent']);
	Route::get('/admin/newevent',[\App\Http\Controllers\SistemController::class, 'newevent']);
	Route::post('/admin/newevent',[\App\Http\Controllers\ActionController::class, 'newevent']);
	Route::get('/admin/editevent',[\App\Http\Controllers\SistemController::class, 'editevent']);
	Route::post('/admin/editevent',[\App\Http\Controllers\ActionController::class, 'editevent']);
	Route::get('/admin/deleteevent',[\App\Http\Controllers\ActionController::class, 'deleteevent']);
	Route::post('/admin/generateheat',[\App\Http\Controllers\ActionController::class, 'generateheat']);

	// Heat
	Route::get('/admin/listheat',[\App\Http\Controllers\SistemController::class, 'listheat']);
	Route::get('/admin/exportheat',[\App\Http\Controllers\SistemController::class, 'exportheat']);
	Route::get('/admin/newheat',[\App\Http\Controllers\SistemController::class, 'newheat']);
	Route::post('/admin/newheat',[\App\Http\Controllers\ActionController::class, 'newheat']);
	Route::get('/admin/editheat',[\App\Http\Controllers\SistemController::class, 'editheat']);
	Route::post('/admin/editheat',[\App\Http\Controllers\ActionController::class, 'editheat']);
	Route::get('/admin/deleteheat',[\App\Http\Controllers\ActionController::class, 'deleteheat']);

	// Heat Line
	Route::get('/admin/listheatline',[\App\Http\Controllers\SistemController::class, 'listheatline']);
	Route::get('/admin/exportheatline',[\App\Http\Controllers\SistemController::class, 'exportheatline']);
	Route::get('/admin/newheatline',[\App\Http\Controllers\SistemController::class, 'newheatline']);
	Route::post('/admin/newheatline',[\App\Http\Controllers\ActionController::class, 'newheatline']);
	Route::get('/admin/editheatline',[\App\Http\Controllers\SistemController::class, 'editheatline']);
	Route::post('/admin/editheatline',[\App\Http\Controllers\ActionController::class, 'editheatline']);
	Route::get('/admin/deleteheatline',[\App\Http\Controllers\ActionController::class, 'deleteheatline']);

	// Data Atlete
	Route::get('/admin/listatlet',[\App\Http\Controllers\SistemController::class, 'listatlet']);
	Route::get('/admin/exportlistatlet',[\App\Http\Controllers\SistemController::class, 'exportlistatlet']);
	Route::get('/admin/newatlet',[\App\Http\Controllers\SistemController::class, 'newatlet']);
	Route::post('/admin/newatlet',[\App\Http\Controllers\ActionController::class, 'newatlet']);
	Route::get('/admin/editatlet',[\App\Http\Controllers\SistemController::class, 'editatlet']);
	Route::post('/admin/editatlet',[\App\Http\Controllers\ActionController::class, 'editatlet']);
	Route::get('/admin/deleteatlet',[\App\Http\Controllers\ActionController::class, 'deleteatlet']);

	// Data Club
	Route::get('/admin/listclub',[\App\Http\Controllers\SistemController::class, 'listclub']);
	Route::get('/admin/exportlistclub',[\App\Http\Controllers\SistemController::class, 'exportlistclub']);
	Route::get('/admin/newclub',[\App\Http\Controllers\SistemController::class, 'newclub']);
	Route::post('/admin/newclub',[\App\Http\Controllers\ActionController::class, 'newclub']);
	Route::get('/admin/editclub',[\App\Http\Controllers\SistemController::class, 'editclub']);
	Route::post('/admin/editclub',[\App\Http\Controllers\ActionController::class, 'editclub']);
	Route::get('/admin/deleteclub',[\App\Http\Controllers\ActionController::class, 'deleteclub']);

	// Data Kategori / Gaya Renang
	Route::get('/admin/listkategori',[\App\Http\Controllers\SistemController::class, 'listkategori']);
	Route::get('/admin/exportlistkategori',[\App\Http\Controllers\SistemController::class, 'exportlistkategori']);
	Route::get('/admin/newkategori',[\App\Http\Controllers\SistemController::class, 'newkategori']);
	Route::post('/admin/newkategori',[\App\Http\Controllers\ActionController::class, 'newkategori']);
	Route::get('/admin/editkategori',[\App\Http\Controllers\SistemController::class, 'editkategori']);
	Route::post('/admin/editkategori',[\App\Http\Controllers\ActionController::class, 'editkategori']);
	Route::get('/admin/deletekategori',[\App\Http\Controllers\ActionController::class, 'deletekategori']);

	// Data Kelompok Umur
	Route::get('/admin/listku',[\App\Http\Controllers\SistemController::class, 'listku']);
	Route::get('/admin/exportlistku',[\App\Http\Controllers\SistemController::class, 'exportlistku']);
	Route::get('/admin/newku',[\App\Http\Controllers\SistemController::class, 'newku']);
	Route::post('/admin/newku',[\App\Http\Controllers\ActionController::class, 'newku']);
	Route::get('/admin/editku',[\App\Http\Controllers\SistemController::class, 'editku']);
	Route::post('/admin/editku',[\App\Http\Controllers\ActionController::class, 'editku']);
	Route::get('/admin/deleteku',[\App\Http\Controllers\ActionController::class, 'deleteku']);

	// Pengguna
	Route::get('/admin/listusers',[\App\Http\Controllers\SistemController::class, 'listusers']);
	Route::get('/admin/exportlistusers',[\App\Http\Controllers\SistemController::class, 'exportlistusers']);
	Route::get('/admin/newusers',[\App\Http\Controllers\SistemController::class, 'newusers']);
	Route::post('/admin/newusers',[\App\Http\Controllers\ActionController::class, 'newusers']);
	Route::get('/admin/editusers',[\App\Http\Controllers\SistemController::class, 'editusers']);
	Route::post('/admin/editusers',[\App\Http\Controllers\ActionController::class, 'editusers']);
	Route::get('/admin/deleteusers',[\App\Http\Controllers\ActionController::class, 'deleteusers']);
	
	// Level Pengguna
	Route::get('/admin/levelusers',[\App\Http\Controllers\SistemController::class, 'levelusers']);
	Route::get('/admin/newlevelusers',[\App\Http\Controllers\SistemController::class, 'newlevelusers']);
	Route::post('/admin/actionlevel',[\App\Http\Controllers\ActionController::class, 'actionlevel']);
	Route::get('/admin/editlevel',[\App\Http\Controllers\SistemController::class, 'editlevel']);
	Route::get('/admin/deletelevel',[\App\Http\Controllers\ActionController::class, 'deletelevel']);
	
	// Admin
	Route::get('/admin/editaccount',[\App\Http\Controllers\SistemController::class, 'editaccount']);
	Route::post('/admin/editaccount',[\App\Http\Controllers\ActionController::class, 'editaccount']);
	Route::post('/admin/editpassaccount',[\App\Http\Controllers\ActionController::class, 'editpassaccount']);

	// Aktivitas Pengguna
	Route::get('/admin/activityusers',[\App\Http\Controllers\SistemController::class, 'activityusers']);
	Route::get('/admin/exportactivityusers',[\App\Http\Controllers\SistemController::class, 'exportactivityusers']);

	// Setting
	Route::get('/admin/settingmenu',[\App\Http\Controllers\SistemController::class, 'settingmenu']);
	Route::get('/admin/delmenu',[\App\Http\Controllers\ActionController::class, 'delmenu']);
	Route::post('/admin/actionsettingmenu',[\App\Http\Controllers\ActionController::class, 'actionsettingmenu']);
	Route::get('/admin/manualbook',[\App\Http\Controllers\SistemController::class, 'manualbook']);
	Route::post('/admin/uploadmanualbook',[\App\Http\Controllers\ActionController::class, 'uploadmanualbook']);
	Route::get('/admin/viewmanualbook',[\App\Http\Controllers\SistemController::class, 'viewmanualbook']);
	Route::get('/admin/downloadmanualbook',[\App\Http\Controllers\ApiControllerPengaturan::class, 'downloadmanualbook']);
	Route::get('/admin/listcompany',[\App\Http\Controllers\SistemController::class, 'listcompany']);
	Route::get('/admin/newcompany',[\App\Http\Controllers\SistemController::class, 'newcompany']);
	Route::post('/admin/newcompany',[\App\Http\Controllers\ActionController::class, 'newcompany']);
	Route::get('/admin/editcompany',[\App\Http\Controllers\SistemController::class, 'editcompany']);
	Route::post('/admin/editcompany',[\App\Http\Controllers\ActionController::class, 'editcompany']);
	Route::get('/admin/deletecompany',[\App\Http\Controllers\ActionController::class, 'deletecompany']);
	Route::get('/admin/sinkron',[\App\Http\Controllers\ActionController::class, 'sinkron']);
// });