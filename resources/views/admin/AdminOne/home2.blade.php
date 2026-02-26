@extends('admin.AdminOne.layout.assets')
@section('title', 'Dashboard Administrasi')


@section('content')

            <div class="page_main">
                <div class="container-fluid text-left">
                    <div class="row">
                        <div class="col-md-12 bg_con_dash">
                            <div class="col-md-12 hd_page_main">Dashboard</div>
                                <div class="col-md-12 bg_con_dash">
                                    <div class="col-md-12 _con_dash">
                                        <div class="row bg_con_dash">
                                            <div class="col-md-3 _con_dash">
                                                <div class="col-md-12 bg_con_dash">
                                                    <div class="col-md-12 con_dash">
                                                        <div class="hd_con_dash">Atlet</div>
                                                        <div class="val_con_dash _data">{{ $listdata['totalAtlet'] }}</div>
                                                        <div class="next_con_dash"><a href="/admin/listatlet">Lihat Semua <i class="fa fa-caret-right"></i></a></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3 _con_dash">
                                                <div class="col-md-12 bg_con_dash">
                                                    <div class="col-md-12 con_dash">
                                                        <div class="hd_con_dash">Club</div>
                                                        <div class="val_con_dash _data">{{ $listdata['totalClub'] }}</div>
                                                        <div class="next_con_dash"><a href="/admin/listclub">Lihat Semua <i class="fa fa-caret-right"></i></a></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3 _con_dash">
                                                <div class="col-md-12 bg_con_dash">
                                                    <div class="col-md-12 con_dash">
                                                        <div class="hd_con_dash">Event</div>
                                                        <div class="val_con_dash _data">{{ $listdata['totalEvent'] }}</div>
                                                        <div class="next_con_dash"><a href="/admin/listevent">Lihat Semua <i class="fa fa-caret-right"></i></a></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3 _con_dash">
                                                <div class="col-md-12 bg_con_dash">
                                                    <div class="col-md-12 con_dash">
                                                        <div class="hd_con_dash">Pendaftaran</div>
                                                        <div class="val_con_dash _data">{{ $listdata['totalRegistrasi'] }}</div>
                                                        <div class="next_con_dash"><a href="/admin/histroryregister">Lihat Semua <i class="fa fa-caret-right"></i></a></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-8 bg_con_dash">
                                    <div class="col-md-12 _con_dash">
                                        <div class="row bg_con_dash">
                                            <div class="col-md-12 _con_dash">
                                                <div class="col-md-12 bg_con_dash">
                                                    <div class="col-md-12 con_dash">
                                                        <div class="chart_con_dash" id="chart3"></div>                                             
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4 bg_con_dash">
                                    <div class="col-md-12 _con_dash">
                                        <div class="row bg_con_dash">
                                            <div class="col-md-12 _con_dash">
                                                <div class="col-md-12 bg_con_dash">
                                                    <div class="col-md-12 con_dash">
                                                        <div class="chart_con_dash" id="chart4"></div>                                             
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>






                                <div class="col-md-12 bg_con_dash">
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-2">
                                            <div class="card_stat">
                                                <div class="stat_value">{{ $listdata['totalAtlet'] }}</div>
                                                <div class="stat_label">Atlet</div>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="card_stat">
                                                <div class="stat_value">{{ $listdata['totalClub'] }}</div>
                                                <div class="stat_label">Club</div>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="card_stat">
                                                <div class="stat_value">{{ $listdata['totalEvent'] }}</div>
                                                <div class="stat_label">Event</div>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="card_stat">
                                                <div class="stat_value">{{ $listdata['totalRegistrasi'] }}</div>
                                                <div class="stat_label">Pendaftaran</div>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="card_stat">
                                                <div class="stat_value">{{ $listdata['totalHeat'] }}</div>
                                                <div class="stat_label">Heat</div>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="card_stat">
                                                <div class="stat_value">{{ $listdata['totalHasil'] }}</div>
                                                <div class="stat_label">Hasil</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <div class="card_box">
                                                <div class="card_title">Atlet Putra vs Putri</div>
                                                <canvas id="genderChart"></canvas>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="card_box">
                                                <div class="card_title">Atlet per Kelompok Umur</div>
                                                <canvas id="ageChart"></canvas>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-4">
                                        <div class="col-md-12">
                                            <div class="card_box">
                                                <div class="card_title">Ranking Club</div>
                                                <canvas id="clubChart"></canvas>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row g-3 mb-4">
                                        @php
                                        $menus = [
                                            ['Master Atlet','athlete','fa-users'],
                                            ['Club','club','fa-flag'],
                                            ['Event','event','fa-list'],
                                            ['Pendaftaran','registrasi','fa-file'],
                                            ['Verifikasi','verifikasi','fa-check'],
                                            ['Generate Heat','heat','fa-th'],
                                            ['Input Hasil','hasil','fa-edit'],
                                            ['Ranking','ranking','fa-trophy'],
                                            ['Buku Heat','bukuheat','fa-book'],
                                            ['Buku Hasil','bukuhasil','fa-book-open'],
                                        ];
                                        @endphp

                                        @foreach($menus as $m)
                                            <div class="col-md-2">
                                                <a href="{{ url($m[1]) }}" class="menu_card">
                                                    <i class="fa {{ $m[2] }}"></i>
                                                    <div>{{ $m[0] }}</div>
                                                </a>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                
                            </div>
                        </div>
					</div>
				</div>
			</div>

            @section('script')
                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

                <script>
                    document.addEventListener("DOMContentLoaded", function(){

                        // ===== DATA DARI CONTROLLER (ARRAY) =====
                        const genderStats = @json($listdata['genderStats'] ?? []);
                        const ageStats = @json($listdata['ageStats'] ?? []);
                        const clubRanking = @json($listdata['clubRanking'] ?? []);

                        // ===== GENDER =====
                        const genderLabels = Object.keys(genderStats);
                        const genderValues = Object.values(genderStats);

                        if(document.getElementById('genderChart')){
                            new Chart(document.getElementById('genderChart'),{
                                type:'pie',
                                data:{
                                    labels: genderLabels,
                                    datasets:[{
                                        data: genderValues
                                    }]
                                }
                            });
                        }

                        // ===== AGE GROUP =====
                        const ageLabels = ageStats.map(a => a.nama_kelompok);
                        const ageValues = ageStats.map(a => a.registrasi_count);

                        if(document.getElementById('ageChart')){
                            new Chart(document.getElementById('ageChart'),{
                                type:'bar',
                                data:{
                                    labels: ageLabels,
                                    datasets:[{
                                        data: ageValues
                                    }]
                                }
                            });
                        }

                        // ===== CLUB RANKING =====
                        const clubLabels = clubRanking.map(c => c.nama_club);
                        const clubValues = clubRanking.map(c => c.total_poin);

                        if(document.getElementById('clubChart')){
                            new Chart(document.getElementById('clubChart'),{
                                type:'bar',
                                data:{
                                    labels: clubLabels,
                                    datasets:[{
                                        data: clubValues
                                    }]
                                }
                            });
                        }

                    });
                </script>
            @endsection
            

			@section('script')
				<script type="text/javascript">
                    $(document).ready(function(){

                        // ===== DATA DARI CONTROLLER (ARRAY) =====
                        const genderStats = @json($listdata['genderStats'] ?? []);
                        const ageStats = @json($listdata['ageStats'] ?? []);
                        const clubRanking = @json($listdata['clubRanking'] ?? []);

                        // ===== GENDER =====
                        const genderLabels = Object.keys(genderStats);
                        const genderValues = Object.values(genderStats);

                        // ===== CLUB RANKING =====
                        const clubLabels = clubRanking.map(c => c.nama_club);
                        const clubValues = clubRanking.map(c => c.total_poin);

                        if(document.getElementById('clubChart')){
                            new Chart(document.getElementById('clubChart'),{
                                type:'bar',
                                data:{
                                    labels: clubLabels,
                                    datasets:[{
                                        data: clubValues
                                    }]
                                }
                            });
                        }

                        
                        var chart3;

                        if (document.getElementById('chart3')) {
                            const clubLabels = clubRanking.map(c => c.nama_club);
                            const clubValues = clubRanking.map(c => c.total_poin);

                            chart3 = Highcharts.chart('chart3', {
                                chart: { type: 'column' },
                                title: { text: 'Rangking Club' },
                                xAxis: {
                                    categories: clubLabels,
                                    crosshair: true
                                },
                                yAxis: {
                                    title: { text: 'Total Poin' }
                                },
                                tooltip: { shared: true },
                                credits: { enabled: false },
                                series: [{
                                    name: 'Total Poin',
                                    data: clubValues,
                                    color: '#932C25'
                                }]
                            });
                        }

                        var chart4;
                        chart4 = Highcharts.chart('chart4',{
                            chart: {pointPadding: 0,borderWidth: 0,groupPadding: 0,type: 'pie'},
                            title: {text: 'Atlet Putra vs Putri'},
                            subtitle: {text: ''},
                            tooltip: {shared: true},
                            legend: {enabled: true},
                            credits: {enabled: false},
                            plotOptions: {
                                pie: {
                                    allowPointSelect: true,
                                    cursor: 'pointer',
                                    dataLabels: {
                                        enabled: true
                                    },
                                    showInLegend: true
                                }
                            },
                            series: [
                                {
                                    name: 'Jumlah ',
                                    colorByPoint: true,
                                    data: [
                                        {
                                            name: 'genderLabels',
                                            y : genderLabels,
                                            color: '#932C25',
                                        },
                                        {
                                            name: 'genderValues',
                                            y : genderValues,
                                            color: '#3789C1',
                                        }
                                    ],
                                }
                            ]
                        });
                    });
                </script>
            @endsection

@endsection