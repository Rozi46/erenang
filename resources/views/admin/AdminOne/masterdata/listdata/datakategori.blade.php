@extends('admin.AdminOne.layout.assets')
@section('title', 'Data Kategori')

@section('content')

            <div class="page_main">
                <div class="container-fluid text-left">
                    <div class="row">
                        <div class="col-md-12 bg_page_main hd">
                            <div class="col-md-12 hd_page_main">Data Kategori / Gaya Renang</div>
							<div class="col-md-12 bg_act_page_main">
								<div class="row">
									<div class="col-xl-12 col_act_page_main text-left">
										<button type="button" class="btn btn-default back" onclick="BackPage()"><i class="fa fa-chevron-left"></i> Kembali</button>
										@if($level_user['newkategori'] == 'Yes')<a load="true" href="/admin/newkategori"><button type="button" class="btn btn-primary">Tambah Data</button></a>@endif
										
										@if($level_user['exportkategori'] == 'Yes')<button type="button" class="btn btn-info back" onclick="exportdata('listkategori')"><i class="fa fa-download"></i> Export Data</button>@endif
									</div>
								</div>
							</div>
                        </div>
						<div class="col-md-12 bg_page_main dt">
							<div class="col-md-12 bg_act_page_main page">
								<div class="row">
									<div class="col-xl-12 col_act_page_main text-right">
										@include('admin.AdminOne.layout.pagination')
									</div>
								</div>
							</div>
							<div class="col-md-12 data_page">
								<div class="row bg_data_page">
									<div class="table_data freezeHead freezeCol">
										<table class="table_view table-striped table-hover">
											<thead>
												<tr>
													<th style="width:30px; text-align: center;">No</th>
													<th style="min-width:50px; text-align: center;">Kode Data</th>
													<th style="min-width:250px; text-align: center;">Nama Kategori</th>
													<th class="colright" style="width:30px; text-align: center;"><i class="head fa fa-cog"></i></th>
												</tr>
											</thead>
											<tbody>
												<?php $no = 0;?> @forelse($results['data'] as $view_data) 
                                                    <?php 
                                                        $no++ ;
                                                    ?>
													<script type="text/javascript">
														$(document).ready(function(){
															$('[btn="del_data_{{$view_data['code_data']}}"]').click(function(){
																if($('[btn="del_data_{{$view_data['code_data']}}"]').click){
																	$('div[data-model="confirmasi"]').modal({backdrop: false});
																	$('div[data-model="confirmasi"] .modal-body').html('<div class="alert alert-danger">Anda yakin untuk menghapus data {{$view_data['nama_gaya']}}.</div>');
																	$('button[btn-action="action-confirmasi"]').remove();
																	$('button[btn-action="close-confirmasi"]').before('<button type="button" class="btn btn-primary btn-sm" btn-action="action-confirmasi">Yakin</button>');
																	$('button[btn-action="action-confirmasi"]').click(function(){
																		if($('button[btn-action="action-confirmasi"]').click){
																			$('button[btn-action="action-confirmasi"]').remove();
																			$('button[btn-action="close-confirmasi"]').remove();
																			loadingpage(20000);
																			window.location.href = "/admin/deletekategori?d={{$view_data['code_data']}}";
																		}
																	});
																}
															});
														});
													</script>
													<tr>
														<td style="text-align:center;">{{$no}}</td>
														<td style="text-align:center;">{{$view_data['code_data'] ?? 'Belum ditentukan'}}</td>
														<td>
															{{$view_data['nama_gaya']}} 
															@if(!empty($view_data['istilah']) && $view_data['istilah'] !== '-')
																( {{$view_data['istilah']}} )
															@endif
														</td>

														<td class="colright" style="text-align:center;">
															<div class="dropdown dropleft">
																<button type="button" class="btn dropdown-toggle" data-toggle="dropdown">Atur</button>
																<div class="dropdown-menu">
																	<h5 class="dropdown-header">Pengaturan Data</h5>
																	<a load="true" class="dropdown-item" href="/admin/editkategori?d={{$view_data['code_data']}}">Lihat/Ubah Data</a>
																	<a class="dropdown-item @if($listdata['count_used'][$view_data['code_data']] > 0) disabled @endif @if($level_user['deletekategori'] == 'No') disabled @endif" <?php if($listdata['count_used'][$view_data['code_data']] == 0){ if($level_user['deletekategori'] == 'Yes'){ ?> btn="del_data_{{$view_data['code_data']}}"<?php } }?>>Hapus Data</a>
																</div>
															</div>
														</td>
													</tr>
												@empty
													<tr>
														<td style="text-align:center; padding: 20px; background-color: #FFFFFF; cursor: default; font-weight: 600; font-size: 14px;" colspan="20">Tidak ada data yang tersedia</td>
													</tr>
												@endforelse
											</tbody>
										</table>
									</div>
								</div>
							</div>
						</div>
                    </div>
                </div>
            </div>

			@section('script')
				<script type="text/javascript">
					$(document).ready(function(){
					});
				</script>
			@endsection

@endsection