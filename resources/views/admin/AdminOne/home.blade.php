@extends('admin.AdminOne.layout.assets')
@section('title', 'Dashboard Administrasi')

@section('content')

            <div class="page_main">
                <div class="container-fluid text-left">
                    <div class="row">
                        <div class="col-md-12 bg_con_dash">
                            <div class="col-md-12 hd_page_main">Dashboard</div>

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

            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const btnSinkron = document.getElementById('menuSinkron');
                    if (btnSinkron) {
                        btnSinkron.addEventListener('click', function (e) {
                            e.preventDefault(); // Cegah langsung pindah halaman

                            Swal.fire({
                                title: 'Mohon Menunggu...',
                                text: 'Sedang proses sinkronisasi database',
                                allowOutsideClick: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });

                            // Redirect setelah alert muncul (biar kelihatan loading)
                            setTimeout(() => {
                                window.location.href = btnSinkron.href;
                            }, 300);
                        });
                    }
                });
            </script>

            @if(session('success'))
                <script>
                    Swal.fire({icon: 'success',title: 'Berhasil',text: '{{ session("success") }}',timer: 3000,showConfirmButton: false});
                </script>
            @endif

            @if(session('failed'))
                <script>
                    Swal.fire({icon: 'error',title: 'Gagal',text: '{{ session("failed") }}',timer: 3000,showConfirmButton: false});
                </script>
            @endif

@endsection