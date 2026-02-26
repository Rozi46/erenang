@extends('admin.AdminOne.layout.assets')
@section('title', 'Dashboard Administrasi')

@section('content')

    <div class="page_main">
        <div class="container-fluid text-left">
            <div class="row">
                <div class="col-md-12 bg_con_dash">
                    <div class="col-md-12 hd_page_main">Dashboard</div>
                </div>

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
                    <div class="col-md-12 _con_dash">
                        <div class="row bg_con_dash">
                            <div class="col-md-12 _con_dash">
                                <div class="col-md-12 bg_con_dash">
                                    <div class="col-md-12 con_dash">
                                        <div class="chart_con_dash" id="chart5"></div>                                             
                                    </div>
                                </div>
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
                const genderStats = @json($listdata['genderStats'] ?? []);
                const ageStats = @json($listdata['ageStats'] ?? []);
                const clubRanking = @json($listdata['clubRanking'] ?? []);
                
                var chart3;
                if (document.getElementById('chart3')) {
                    const clubLabels = clubRanking.map(c => c.nama_club);
                    const clubValues = clubRanking.map(c => c.total_poin);
                
                    const colors = clubValues.map((v,i)=>{
                        if(i===0) return '#FFD700'; // emas
                        if(i===1) return '#C0C0C0'; // perak
                        if(i===2) return '#CD7F32'; // perunggu
                        return '#932C25';
                    });

                    chart3 = Highcharts.chart('chart3', {
                        chart: {
                            type: 'column',
                            backgroundColor: 'transparent'
                        },

                        title: {
                            text: 'Rangking Club',
                            style: {
                                fontWeight: '600',
                                fontSize: '16px'
                            }
                        },

                        subtitle: {
                            text: 'Total Poin Kejuaraan'
                        },

                        xAxis: {
                            categories: clubLabels,
                            crosshair: true,
                            labels: {
                                style: {
                                    fontSize: '11px'
                                }
                            }
                        },

                        yAxis: {
                            min: 0,
                            title: {
                                text: 'Poin'
                            },
                            gridLineDashStyle: 'Dash',
                            gridLineColor: '#e6e6e6'
                        },

                        tooltip: {
                            headerFormat: '<b>{point.key}</b><br>',
                            pointFormat: 'Total Poin: <b>{point.y}</b>'
                        },

                        credits: { enabled: false },

                        plotOptions: {
                            column: {
                                borderRadius: 6,
                                pointPadding: 0.15,
                                groupPadding: 0.08,
                                dataLabels: {
                                    enabled: true,
                                    format: '{point.y}',
                                    style: {
                                        fontWeight: '600'
                                    }
                                }
                            },
                            series: {
                                states: {
                                    hover: {
                                        brightness: 0.15
                                    }
                                }
                            }
                        },

                        colors: [{
                            linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
                            stops: [
                                [0, '#ff6b6b'],
                                [1, '#932C25']
                            ]
                        }],

                        series: [{
                            name: 'Total Poin',
                            data: clubValues,
                            colorByPoint: true,
                            colors: colors
                        }]
                    });
                }
                
                var chart4;
                if (document.getElementById('chart4')) {
                    const genderData = Object.entries(genderStats).map(([name, value]) => ({
                        name: name,
                        y: value
                    }));

                    chart4 = Highcharts.chart('chart4', {
                        chart: {
                            type: 'pie'
                        },
                        title: {
                            text: 'Atlet Putra vs Putri'
                        },
                        subtitle: {
                            text: 'Komposisi Peserta'
                        },
                        tooltip: {
                            pointFormat: '<b>{point.y} atlet</b> ({point.percentage:.1f}%)'
                        },
                        credits: {
                            enabled: false
                        },
                        plotOptions: {
                            pie: {
                                innerSize: '55%',
                                allowPointSelect: true,
                                cursor: 'pointer',
                                borderRadius: 8,
                                dataLabels: {
                                    enabled: true,
                                    format: '<b>{point.name}</b><br>{point.y} ({point.percentage:.1f}%)'
                                },
                                colors: ['#3789C1', '#E74C3C'],
                                
                            }
                        },
                        series: [{
                            name: 'Jumlah Atlet',
                            data: genderData
                        }]
                    });
                }
                
                var chart5;
                if (document.getElementById('chart5')) {
                    const ageLabels = ageStats.map(a => a.nama_kelompok);
                    const ageValues = ageStats.map(a => a.registrasi_count);
                    
                    const max = Math.max(...ageValues);

                    const ageDataStyled = ageValues.map(v => ({
                        y: v,
                        color: v === max ? '#F39C12' : undefined
                    }));

                    chart5 = Highcharts.chart('chart5', {
                        chart: {
                            type: 'column',
                            backgroundColor: 'transparent'
                        },

                        title: {
                            text: 'Atlet per Kelompok Umur',
                            style: {
                                fontWeight: '600',
                                fontSize: '16px'
                            }
                        },

                        subtitle: {
                            text: 'Distribusi Peserta Berdasarkan Usia'
                        },

                        xAxis: {
                            categories: ageLabels,
                            crosshair: true,
                            labels: {
                                style: { fontSize: '11px' },
                                autoRotation: [-45]
                            }
                        },

                        yAxis: {
                            min: 0,
                            title: {
                                text: 'Jumlah Atlet'
                            },
                            allowDecimals: false,
                            gridLineDashStyle: 'Dash',
                            gridLineColor: '#e6e6e6'
                        },

                        tooltip: {
                            headerFormat: '<b>{point.key}</b><br>',
                            pointFormat: 'Jumlah Atlet: <b>{point.y}</b>'
                        },

                        credits: { enabled: false },

                        plotOptions: {
                            column: {
                                borderRadius: 6,
                                pointPadding: 0.12,
                                groupPadding: 0.08,
                                dataLabels: {
                                    enabled: true,
                                    format: '{point.y}',
                                    style: {
                                        fontWeight: '600',
                                        fontSize: '11px'
                                    }
                                }
                            },
                            series: {
                                states: {
                                    hover: {
                                        brightness: 0.12
                                    }
                                }
                            }
                        },

                        colors: [{
                            linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
                            stops: [
                                [0, '#6EC6FF'],
                                [1, '#2E86C1']
                            ]
                        }],

                        series: [{
                            name: 'Jumlah Atlet',
                            data: ageDataStyled
                        }]
                    });
                }

            });
        </script>
    @endsection

@endsection