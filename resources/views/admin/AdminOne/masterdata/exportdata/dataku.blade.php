<html>
    <head>
        <title>Export to Excel</title>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
        <style> .strtable{ mso-number-format:\@; } table tr th,table tr td{border: 1px solid #000;} </style>
    </head>
    <body>
        <table class="table_view table-striped table-hover">
            <thead>
                <tr>
                    <th style="width:40px; text-align: center;">No</th>
                    <th style="width:150px; text-align: center;">Kode Kelompok</th>
                    <th style="width:400px; text-align: center;">Nama Kelompok</th>
                    <th style="width:150px; text-align: center;">Umur</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 0;?> @forelse($results['data'] as $view_data) <?php $no++ ;?>
                    <tr>
                        <td class="strtable" style="text-align:center;">{{$no}}</td>
                        <td class="strtable" style="text-align:center;">{{$view_data['code_kelompok']}}</td>
                        <td class="strtable" style="text-align:center;">{{$view_data['nama_kelompok']}}</td>
						<td class="strtable" style="text-align:center;">                            
                            @php
                                $min = $view_data['min_usia'] ?? null;
                                $max = $view_data['max_usia'] ?? null;
                            @endphp

                            @if(!empty($min) && !empty($max) && $min != 0 && $max != 0)
                                {{ number_format($min, 0, ',', '.') }} - {{ number_format($max, 0, ',', '.') }}
                            @elseif(!empty($min) && $min != 0)
                                {{ number_format($min, 0, ',', '.') }} +
                            @elseif(!empty($max) && $max != 0)
                                - {{ number_format($max, 0, ',', '.') }}
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td style="text-align:center; padding: 20px; background-color: #FFFFFF; cursor: default; font-weight: 600; font-size: 14px;" colspan="20">Tidak ada data yang tersedia</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </body>
</html>