@extends('admin.AdminOne.layout.assets')
@section('title', 'Line Dalam Seri')

@section('content')

            <div class="page_main">
                <div class="container-fluid text-left">
                    <div class="row">
                        <div class="col-md-12 bg_page_main hd" line="hd_action">
                            <div class="col-md-12 hd_page_main">Line Dalam Seri</div>
							<div class="col-md-12 bg_act_page_main">
								<div class="row">
									<div class="col-xl-12 col_act_page_main text-left">
										<button type="button" class="btn btn-default back" onclick="BackPage()"><i class="fa fa-chevron-left"></i> Kembali</button>
										<!-- @if($level_user['newheatline'] == 'Yes')<a load="true" href="/admin/newheatline"><button type="button" class="btn btn-primary">Tambah Data</button></a>@endif -->
										
										@if($level_user['exportheatline'] == 'Yes')<button type="button" class="btn btn-info back" onclick="exportdata('heatline')"><i class="fa fa-download"></i> Export Data</button>@endif

										@if($level_user['exportheatline'] == 'Yes')<button type="button" class="btn btn-success"><i class="fa fa-trophy"></i> Hitung Ranking & Poin</button>@endif
									</div>
								</div>
							</div>
                        </div>
						<div class="col-md-12 bg_page_main dt" line="form_action">
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
													<th style="min-width:150px; text-align: center;">Kode Data</th>
													<th style="min-width:150px; text-align: center;">Seri Lomba</th>
													<th style="min-width:150px; text-align: center;">Nama Atlet</th>
													<th style="min-width:150px; text-align: center;">Line Number</th>
													<th style="min-width:150px; text-align: center;">Best Time</th>
													<th style="min-width:150px; text-align: center;">Hasil</th>
													<th style="min-width:150px; text-align: center;">Foto Hasil</th>
													<th style="min-width:150px; text-align: center;">Ranking</th>
                                                    
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
																	$('div[data-model="confirmasi"] .modal-body').html('<div class="alert alert-danger">Anda yakin untuk menghapus data {{$view_data['code_data']}}.</div>');
																	$('button[btn-action="action-confirmasi"]').remove();
																	$('button[btn-action="close-confirmasi"]').before('<button type="button" class="btn btn-primary btn-sm" btn-action="action-confirmasi">Yakin</button>');
																	$('button[btn-action="action-confirmasi"]').click(function(){
																		if($('button[btn-action="action-confirmasi"]').click){
																			$('button[btn-action="action-confirmasi"]').remove();
																			$('button[btn-action="close-confirmasi"]').remove();
																			loadingpage(20000);
																			window.location.href = "/admin/deleteheatline?d={{$view_data['code_data']}}";
																		}
																	});
																}
															});
														});
													</script>
													<tr>
														<td style="text-align:center;">{{ $no }}</td>
														<td style="text-align:center;">{{ $view_data['code_data'] ?? 'Belum ditentukan' }}</td>
														<td style="text-align:center;">{{ number_format($listdata['detail_heat'][$view_data['code_data']]['nomor_seri'] ?? 0, 0,"",".") }}</td>														
														<td style="text-align:left;">{{ $listdata['detail_atlet'][$view_data['code_data']]['nama'] ?? 'Belum ditentukan' }}</td>
														<td style="text-align:center;">{{ number_format($view_data['line_number'] ?? 0, 0,"",".") }}</td>
														<td style="text-align:center;">{{ $view_data['best_time'] ?? 'Belum ditentukan' }}</td>
														<td style="text-align:center;">
															<input type="text" name="new_hasil_{{$view_data['code_data']}}" value="{{ $view_data['hasil'] ?? '00:00.00' }}" placeholder="MM:SS.xx" style="width: 90px; text-align:center;" onKeyPress="return goodchars(event,'0123456789',this)" />
														</td>
														<!-- src="{{ $view_data['code_data'] ? asset('/themes/admin/AdminOne/image/public/'.$view_data['code_data']) : asset('/themes/admin/AdminOne/image/public/icon.png') }}"  -->
                                                        <td style="text-align:center;">
                                                            <img                                                                 
																src="{{ asset('/themes/admin/AdminOne/image/no_image.png') }}"
                                                                alt="foto" 
                                                                style="width: 150px; height: 100px;">
                                                        </td>
														<td style="text-align:center;">{{ number_format($view_data['ranking'] ?? 0, 0,"",".") }}</td>

														<td class="colright" style="text-align:center;">
															<div class="dropdown dropleft">
																<button type="button" class="btn dropdown-toggle" data-toggle="dropdown">Atur</button>
																<div class="dropdown-menu">
																	<h5 class="dropdown-header">Pengaturan Data</h5>
																	<a load="true" class="dropdown-item" href="/admin/editheatline?d={{$view_data['code_data']}}">Lihat/Ubah Data</a>
																	<a class="dropdown-item @if($listdata['count_used'][$view_data['code_data']] > 0) disabled @endif @if($level_user['deleteheatline'] == 'No') disabled @endif" <?php if($listdata['count_used'][$view_data['code_data']] == 0){ if($level_user['deleteheatline'] == 'Yes'){ ?> btn="del_data_{{$view_data['code_data']}}"<?php } }?>>Hapus Data</a>
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