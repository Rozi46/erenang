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
										@if($level_user['exportheatline'] == 'Yes')<button type="button" class="btn btn-info back" onclick="exportdata('heatline')"><i class="fa fa-download"></i> Export Data</button>@endif
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
													<th style="min-width:150px; text-align: center;">Nama Kejuaraan</th>
													<th style="min-width:150px; text-align: center;">Nomor Lomba</th>
													<th style="min-width:100px; text-align: center;">Seri Lomba</th>
													<th style="min-width:150px; text-align: center;">Nama Atlet</th>
													<th style="min-width:100px; text-align: center;">Line Number</th>
													<th style="min-width:150px; text-align: center;">Best Time</th>
													<th style="min-width:150px; text-align: center;">Hasil</th>
													<th style="min-width:150px; text-align: center;">Foto Hasil</th>
													<th style="min-width:50px; text-align: center;">Ranking</th>
												</tr>
											</thead>
											<tbody>
												<?php $no = 0;?> @forelse($results['data'] as $view_data) 
                                                    <?php 
                                                        $no++ ;
                                                    ?>
													<tr>
														<td style="text-align:center;">{{ $no }}</td>
														<td style="text-align:center;">{{ $view_data['code_data'] ?? 'Belum ditentukan' }}</td>
														<td style="text-align:center;">{{ $view_data['heat']['event']['championship']['nama_kejuaraan'] ?? 'Belum ditentukan' }}</td>
														<td style="text-align:center;">{{ $view_data['heat']['event']['code_event'] ?? 'Belum ditentukan' }}</td>
														<td style="text-align:center;">{{ number_format($view_data['heat']['nomor_seri'] ?? 0, 0,"",".") }}</td>														
														<td style="text-align:left;">{{ $view_data['atlet']['nama'] ?? 'Belum ditentukan' }}</td>
														<td style="text-align:center;">{{ number_format($view_data['line_number'] ?? 0, 0,"",".") }}</td>
														<td style="text-align:center;">{{ $view_data['best_time'] ?? 'Belum ditentukan' }}</td>
														<td style="text-align:center;">{{ $view_data['result'][0]['hasil'] ?? '00:00.00' }}</td>
														<td style="text-align:center;"><img src="{{ !empty($view_data['result'][0]['foto']) ? asset('/themes/admin/AdminOne/image/upload/'.$view_data['result'][0]['foto']) : asset('/themes/admin/AdminOne/image/no_image.png') }}" class="preview-foto" data-id="{{ $view_data['id'] }}" style="width:150px;height:100px;object-fit:cover;cursor:pointer;border-radius:6px;border:1px solid #ddd;"></td>														
														<td class="@if(($view_data['result'][0]['ranking'] ?? 0) == 1) rank-1 @elseif(($view_data['result'][0]['ranking'] ?? 0) == 2) rank-2 @elseif(($view_data['result'][0]['ranking'] ?? 0) == 3) rank-3 @endif" style="text-align:center;">{{ number_format($view_data['result'][0]['ranking'] ?? 0, 0,"",".") }}</td>
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