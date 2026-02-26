@extends('admin.AdminOne.layout.assets')
@section('title', 'Dashboard Administrasi')


@section('content')

            <div class="page_main">
                <div class="container-fluid text-left">
                    <div class="row">
                        <div class="col-md-12 bg_con_dash">
                            <div class="col-md-12 hd_page_main">Dashboard</div>
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

                                    <!-- <div class="row g-3 mb-4">
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
                                    </div> -->
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

@endsection