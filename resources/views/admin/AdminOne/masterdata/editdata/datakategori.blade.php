@extends('admin.AdminOne.layout.assets')
@section('title', 'Ubah Data Kategori')

@section('content')

			<div class="page_main">
                <div class="container-fluid text-left">
					<div class="row">
						<div class="col-md-12 bg_page_main hd" line="hd_action">
							<div class="col-md-12 hd_page_main">Ubah Data Kategori</div>
							<div class="col-md-12 bg_act_page_main">
								<div class="row">
									<div class="col-xl-12 col_act_page_main text-left">
										<button type="button" class="btn btn-default back" onclick="BackPage()"><i class="fa fa-chevron-left"></i> Kembali</button>
										@if($level_user['editkategori'] == 'Yes')<button type="button" class="btn btn-primary" name="btn_save">Simpan Data</button>@endif
										
										@if($results['count_used'] == '0') 
											@if($level_user['deletekategori'] == 'Yes')<button type="button" class="btn btn-danger" name="btn_del" onclick="DeleteData()">Hapus Data</button>@endif
										@endif
                                        
									</div>
								</div>
							</div>
						</div>
						<div class="col-md-12 bg_page_main dt" line="form_action">
							<div class="col-md-12 data_page">
								<form method="post" name="form_data" enctype="multipart/form-data" action="/admin/editkategori">
									{{csrf_field()}}
									<div class="row bg_data_page form_page content">
                                        <input type="text" name="code_data" value="{{ $results['results']['kategori']['code_data'] }}" readonly="true" style="display: none;" />
                                        <div class="col-md-12 bg_form_page">
                                            <div class="form-group row form_input text-left">
                                                <label for="nama_gaya" class="col-sm-2 col-form-label">Nama Gaya <span>*</span></label>
                                                <div class="col-sm-10 input">
                                                    <input type="text" name="nama_gaya" placeholder="Nama Gaya"  value="{{$results['results']['kategori']['nama_gaya']}}" autofocus/>
                                                </div>
                                            </div>
                                        </div> 
                                        <div class="col-md-12 bg_form_page">
                                            <div class="form-group row form_input text-left">
                                                <label for="istilah" class="col-sm-2 col-form-label">Istilah <span>*</span></label>
                                                <div class="col-sm-10 input">
                                                    <input type="text" name="istilah" placeholder="Istilah"  value="{{$results['results']['kategori']['istilah']}}" autofocus/>
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
							var nama_gaya = $('input[name="nama_gaya"]').val();
							if($(('button[name="btn_save"]')).click){
								$('div[data-model="confirmasi"]').modal({backdrop: false});
								$('div[data-model="confirmasi"] .modal-body').html('<div class="alert alert-warning">Anda yakin untuk simpan data ' + nama_gaya + '.</div>');
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
                        $('div[data-model="confirmasi"] .modal-body').html('<div class="alert alert-danger">Anda yakin untuk menghapus data {{ $results['results']['kategori']['nama_gaya'] }}.</div>');
                        $('button[btn-action="action-confirmasi"]').remove();
                        $('button[btn-action="close-confirmasi"]').before('<button type="button" class="btn btn-primary btn-sm" btn-action="action-confirmasi">Yakin</button>');
                        $('button[btn-action="action-confirmasi"]').click(function(){
                            if($('button[btn-action="action-confirmasi"]').click){
                                $('button[btn-action="action-confirmasi"]').remove();
                                $('button[btn-action="close-confirmasi"]').remove();
                                loadingpage(20000);
                                window.location.href = "/admin/deletekategori?d={{ $results['results']['kategori']['code_data'] }}";
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