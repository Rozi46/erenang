@extends('admin.AdminOne.layout.assets')
@section('title', 'Ubah Data Kelompok Umur')

@section('content')

			<div class="page_main">
                <div class="container-fluid text-left">
					<div class="row">
						<div class="col-md-12 bg_page_main hd" line="hd_action">
							<div class="col-md-12 hd_page_main">Ubah Data Kelompok Umur</div>
							<div class="col-md-12 bg_act_page_main">
								<div class="row">
									<div class="col-xl-12 col_act_page_main text-left">
										<button type="button" class="btn btn-default back" onclick="BackPage()"><i class="fa fa-chevron-left"></i> Kembali</button>
										@if($level_user['editku'] == 'Yes')<button type="button" class="btn btn-primary" name="btn_save">Simpan Data</button>@endif
                                        
                                        @if($results['count_used'] == '0') 
											@if($level_user['deleteku'] == 'Yes')<button type="button" class="btn btn-danger" name="btn_del" onclick="DeleteData()">Hapus Data</button>@endif
										@endif
                                        
									</div>
								</div>
							</div>
						</div>
						<div class="col-md-12 bg_page_main dt" line="form_action">
							<div class="col-md-12 data_page">
								<form method="post" name="form_data" enctype="multipart/form-data" action="/admin/editku">
									{{csrf_field()}}
									<div class="row bg_data_page form_page content">
                                        <input type="text" name="code_data" value="{{ $results['results']['ku']['code_data'] }}" readonly="true" style="display: none;" />
                                        <div class="col-md-12 bg_form_page">
                                            <div class="form-group row form_input text-left">
                                                <label for="code_kelompok" class="col-sm-2 col-form-label">Kode Kelompok <span>*</span></label>
                                                <div class="col-sm-10 input">
                                                    <input type="text" name="code_kelompok" placeholder="Kode Kelompok"  value="{{$results['results']['ku']['code_kelompok']}}" autofocus/>
                                                </div>
                                            </div>
                                        </div> 
                                        <div class="col-md-12 bg_form_page">
                                            <div class="form-group row form_input text-left">
                                                <label for="nama_kelompok" class="col-sm-2 col-form-label">Nama Kelompok <span>*</span></label>
                                                <div class="col-sm-10 input">
                                                    <input type="text" name="nama_kelompok" placeholder="Nama Kelompok"  value="{{$results['results']['ku']['nama_kelompok']}}" autofocus/>
                                                </div>
                                            </div>
                                        </div> 
                                        <div class="col-md-12 bg_form_page">
                                            <div class="form-group row form_input text-left">
                                                <label for="min_usia" class="col-sm-2 col-form-label">Usia Minimum <span>*</span></label>
                                                <div class="col-sm-10 input">
                                                    <input type="text" name="min_usia" placeholder="Usia Minimum"  value="{{ number_format($results['results']['ku']['min_usia'], 0, ',', '.') }}" autofocus/>
                                                </div>
                                            </div>
                                        </div> 
                                        <div class="col-md-12 bg_form_page">
                                            <div class="form-group row form_input text-left">
                                                <label for="max_usia" class="col-sm-2 col-form-label">Usia Maximum <span>*</span></label>
                                                <div class="col-sm-10 input">
                                                    <input type="text" name="max_usia" placeholder="Usia Maximum"  value="{{ number_format($results['results']['ku']['max_usia'] ?? 0, 0, ',', '.') }}" autofocus/>
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
                        $('button[name="btn_save"], button[name="btn_del"]').prop('disabled', true);
                        $('form :input').not('button, [type=button], [type=submit]').prop('disabled', true);

                        @if($level_user['editkategori'] == 'Yes')
                            $('form :input').not('button, [type=button], [type=submit]').prop('disabled', false);

                            $('form :input').on('input change', function () {
                                checkFormInputs();
                            });
                            checkFormInputs();
                        @endif 
										
						$('button[name="btn_save"]').click(function(){
							var code_kelompok = $('input[name="code_kelompok"]').val();
							if($(('button[name="btn_save"]')).click){
								$('div[data-model="confirmasi"]').modal({backdrop: false});
								$('div[data-model="confirmasi"] .modal-body').html('<div class="alert alert-warning">Anda yakin untuk simpan data ' + code_kelompok + '.</div>');
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
                        $('div[data-model="confirmasi"] .modal-body').html('<div class="alert alert-danger">Anda yakin untuk menghapus data {{ $results['results']['ku']['nama_kelompok'] }}.</div>');
                        $('button[btn-action="action-confirmasi"]').remove();
                        $('button[btn-action="close-confirmasi"]').before('<button type="button" class="btn btn-primary btn-sm" btn-action="action-confirmasi">Yakin</button>');
                        $('button[btn-action="action-confirmasi"]').click(function(){
                            if($('button[btn-action="action-confirmasi"]').click){
                                $('button[btn-action="action-confirmasi"]').remove();
                                $('button[btn-action="close-confirmasi"]').remove();
                                loadingpage(20000);
                                window.location.href = "/admin/deleteku?d={{ $results['results']['ku']['code_data'] }}";
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