@extends('admin.AdminOne.layout.assets')
@section('title', 'Ubah Data Atlet')

@section('content')

			<div class="page_main">
                <div class="container-fluid text-left">
					<div class="row">
						<div class="col-md-12 bg_page_main hd" line="hd_action">
							<div class="col-md-12 hd_page_main">Ubah Data Atlet</div>
							<div class="col-md-12 bg_act_page_main">
								<div class="row">
									<div class="col-xl-12 col_act_page_main text-left">
										<button type="button" class="btn btn-default back" onclick="BackPage()"><i class="fa fa-chevron-left"></i> Kembali</button>
										@if($level_user['editatlet'] == 'Yes')<button type="button" class="btn btn-primary" name="btn_save">Simpan Data</button>@endif
                                        
                                        @if($results['results']['atlet']['registrasi_count'] == '0') 
											@if($level_user['deleteatlet'] == 'Yes')<button type="button" class="btn btn-danger" name="btn_del" onclick="DeleteData()">Hapus Data</button>@endif
										@endif
                                        
									</div>
								</div>
							</div>
						</div>
						<div class="col-md-12 bg_page_main dt" line="form_action">
							<div class="col-md-12 data_page">
								<form method="post" name="form_data" enctype="multipart/form-data" action="/admin/editatlet">
									{{csrf_field()}}
									<div class="row bg_data_page form_page content">
                                        <input type="text" name="code_data" value="{{ $results['results']['atlet']['code_data'] }}" readonly="true" style="display: none;" />  
                                        <div class="col-md-8 bg_form_page">
                                            <div class="col-md-12 bg_form_page">
                                                <div class="form-group row form_input text-left">
                                                    <label for="nis" class="col-sm-2 col-form-label">NIS <span>*</span></label>
                                                    <div class="col-sm-10 input">
                                                        <input type="text" name="nis" placeholder="NIS" value="{{ $results['results']['atlet']['nis'] }}" autofocus/>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-12 bg_form_page">
                                                <div class="form-group row form_input text-left">
                                                    <label for="nama" class="col-sm-2 col-form-label">Nama <span>*</span></label>
                                                    <div class="col-sm-10 input">
                                                        <input type="text" name="nama" placeholder="Nama" value="{{ $results['results']['atlet']['nama'] }}" >
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-12 bg_form_page">
                                                <div class="form-group row form_input text-left">
                                                    <label for="gender" class="col-sm-2 col-form-label">Gender <span>*</span></label>
                                                    <div class="col-sm-10 input">
                                                        <select name="gender" placeholder="Gender">
                                                            <option value="" style="display:none;">Pilih Gender</option>
                                                            <option value="Laki-Laki">Laki-Laki</option>
                                                            <option value="Perempuan">Perempuan</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-12 bg_form_page">
                                                <div class="form-group row form_input text-left">
                                                    <label for="tempat_lahir" class="col-sm-2 col-form-label">Tempat Lahir <span>*</span></label>
                                                    <div class="col-sm-10 input">
                                                        <input type="text" name="tempat_lahir" placeholder="Tempat Lahir" value="{{ $results['results']['atlet']['tempat_lahir'] }}" >
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-12 bg_form_page">
                                                <div class="form-group row form_input text-left">
                                                    <label for="tanggal_lahir" class="col-sm-2 col-form-label">Tanggal Lahir <span>*</span></label>
                                                    <div class="col-sm-10 input">
                                                        <div class="input-group-append" btn="tgl_view" line="tanggal_lahir">
                                                            <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                                        </div>
                                                        <input class="pointer" type="text" name="tanggal_lahir" placeholder="Tanggal Lahir" value="{{ \Carbon\Carbon::parse($results['results']['atlet']['tanggal_lahir'])->locale('id')->translatedFormat('d F Y') }}" readonly="true"/>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-12 bg_form_page">
                                                <div class="form-group row form_input text-left">
                                                    <label for="club" class="col-sm-2 col-form-label">Club <span>*</span></label>
                                                    <div class="col-sm-10 input">
                                                        <select id="code_club" name="code_club" placeholder="Pilih Club">	
                                                            <option value="" selected="true">Pilih Club</option>
                                                            @foreach ($list_club as $view_data)
                                                                <option value="{{$view_data['code_data']}}">{{$view_data['nama_club']}}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
										<div class="col-md-4 bg_form_page">
											<div class="form_input text-left">
												<div class="tag_title">Foto Atlet</div>
                                                    <img 
                                                        src="{{ $results['results']['atlet']['foto'] ? asset('/themes/admin/AdminOne/image/public/'.$results['results']['atlet']['foto']) : asset('/themes/admin/AdminOne/image/no_image.png') }}" 
                                                        alt="Foto" srcimg="logo_atlet" onclick="OpenFile('form_data','logo_atlet')">

												<input type="file" accept="image/*" name="logo_atlet" placeholder="Logo Atlet"/>
												<div class="btn_200">
													<button type="button" class="btn btn-default" onclick="OpenFile('form_data','logo_atlet')">Upload Foto</button>
												</div>
											</div>
										</div>  
									</div> 
								</form>
							</div>
						</div>
					</div>
				</div>
			</div>

            @section('script')
                <script type="text/javascript">
                    $(document).ready(function(){
                        $('select[name="gender"] option[value="{{ $results['results']['atlet']['gender'] }}"]').prop("selected", true);
                        $('select[name="code_club"] option[value="{{ $results['results']['atlet']['code_club']}}"]').prop("selected", true);  
                        
                        $('button[name="btn_save"], button[name="btn_del"]').prop('disabled', true);
                        $('form :input').not('button, [type=button], [type=submit]').prop('disabled', true);

                        @if($level_user['editatlet'] == 'Yes')
                            $('form :input').not('button, [type=button], [type=submit]').prop('disabled', false);

                            $('form :input').on('input change', function () {
                                checkFormInputs();
                            });
                            checkFormInputs();
                        @endif 

                        $('input[name="tanggal_lahir"]').datepicker({
                            format: 'dd MM yyyy',
                            startDate: '-65y',
                            endDate: '-18y',
                            autoclose : true,
                            language: "id",
                            orientation: "bottom"
                        });

                        $('#code_club').select2({
                            placeholder: 'Pilih Club',
                            allowClear: true,
                            width: '100%'
                        });
										
						$('button[name="btn_save"]').click(function(){
							var nama = $('input[name="nama"]').val();
							if($(('button[name="btn_save"]')).click){
								$('div[data-model="confirmasi"]').modal({backdrop: false});
								$('div[data-model="confirmasi"] .modal-body').html('<div class="alert alert-warning">Anda yakin untuk simpan data ' + nama + '.</div>');
								$('button[btn-action="action-confirmasi"]').remove();
								$('button[btn-action="close-confirmasi"]').before('<button type="button" class="btn btn-primary btn-sm" btn-action="action-confirmasi">Yakin</button>');
								$('button[btn-action="action-confirmasi"]').click(function(){
									if($('button[btn-action="action-confirmasi"]').click){
										$('button[btn-action="action-confirmasi"]').remove();
										$('button[btn-action="close-confirmasi"]').remove();
                                        loadingpage(20000);
										$('form[name="form_data"]').submit();  
									}
								});
							}
						});
                    });

                    function DeleteData() {
                        $('div[data-model="confirmasi"]').modal({backdrop: false});
                        $('div[data-model="confirmasi"] .modal-body').html('<div class="alert alert-danger">Anda yakin untuk menghapus data {{ $results['results']['atlet']['nama'] }}.</div>');
                        $('button[btn-action="action-confirmasi"]').remove();
                        $('button[btn-action="close-confirmasi"]').before('<button type="button" class="btn btn-primary btn-sm" btn-action="action-confirmasi">Yakin</button>');
                        $('button[btn-action="action-confirmasi"]').click(function(){
                            if($('button[btn-action="action-confirmasi"]').click){
                                $('button[btn-action="action-confirmasi"]').remove();
                                $('button[btn-action="close-confirmasi"]').remove();
                                loadingpage(20000);
                                window.location.href = "/admin/deleteatlet?d={{ $results['results']['atlet']['code_data'] }}";
                            }
                        });
                    }
					
					function checkFormInputs() {
						let isComplete = true;
						$('form :input').each(function () {
							if (
								$(this).is(':visible') &&
								!$(this).is(':disabled') &&
								$(this).attr('type') !== 'hidden' &&
								$(this).attr('type') !== 'button' &&
								$(this).attr('type') !== 'submit'
							) {
								if (!$(this).val().trim()) {
									isComplete = false;
									return false;
								}
							}
						});

                        $('button[name="btn_save"], button[name="btn_del"]').prop('disabled', !isComplete);
					}
                </script>
            @endsection
@endsection