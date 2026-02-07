<?php

    namespace App\Http\Controllers;

    require '../../vendor/autoload.php';
    require '../fpdf/MultiCellTable.php';
    require __DIR__ . '/../../resources/views/admin/AdminOne/layout/function.blade.php';

    use Illuminate\Http\Request;
    use Illuminate\Http\Response;
    use Illuminate\Support\Facades\Http;
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Facades\Session;
    use Illuminate\Support\Facades\Hash;
    use Illuminate\Support\Carbon;
    use Jenssegers\Date\Date;
    use Artisan;
    use Cookie;
    use JWTAuth;
    use FPDF;
    use MultiCellTable;


    if(isset($_REQUEST['token'])){        
    	$url_api = $_REQUEST['api'].'/';
    	$admin_login = $_REQUEST['u'];
        $key_token = $_REQUEST['token'];
        $print_code = $_REQUEST['print_code'];

        function curlget($url){
            $ch = curl_init(); 
            curl_setopt($ch, CURLOPT_URL, $url); 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
            $output = curl_exec($ch); 
            curl_close($ch);      
            return $output;
        }

        $get_user = curlget($url_api."v1/cash/viewadminlogin?token=".$key_token."&u=".$admin_login);
        $get_user = json_decode($get_user, TRUE);
        
        if($get_user['status_message'] == 'failed'){
            echo "<meta http-equiv='refresh' content='0;/'>";
        }else{

            $get_data = curlget($url_api."v1/cash/viewpenjualan?token=".$key_token."&u=".$admin_login."&code_data=".$print_code);
            $get_data = json_decode($get_data, TRUE);
            $getdata = $get_data['results'];
        
            if($get_data['status_message'] == 'failed'){
                echo "<meta http-equiv='refresh' content='0;/'>";
            }else{
                class PDF extends MultiCellTable
                {
                    private $mata_uang = 'Rp';
                    private $kurs_harga = 'Rupiah';
                    private $getdata = [];
                
                    public function __construct($orientation = 'P', $unit = 'mm', $size = [80, 100])
                    {
                        parent::__construct($orientation, $unit, $size);
                        $this->SetMargins(5, 5, 5);
                    }
                
                    // Helper function for API calls
                    private function fetchData()
                    {
                        $url_api = $_REQUEST['api'] . '/';
                        $admin_login = $_REQUEST['u'];
                        $key_token = $_REQUEST['token'];
                        $print_code = $_REQUEST['print_code'];
                
                        $response = curlget("{$url_api}v1/cash/viewpenjualan?token={$key_token}&u={$admin_login}&code_data={$print_code}&tipe_data=group");
                        $data = json_decode($response, true);
                
                        return $data['results'] ?? [];
                    }
                
                    function Header()
                    {
                        $this->getdata = $this->fetchData();
                        $detailPerusahaan = $this->getdata['detail_perusahaan'] ?? [];
                        $logo = !empty($detailPerusahaan['foto'])
                            ? $_REQUEST['api'] . '/themes/admin/AdminOne/image/public/' . $detailPerusahaan['foto']
                            : '../themes/admin/AdminOne/image/public/icon.png';
                
                        // Logo
                        $this->Image($logo, 32.5, 5, 15);
                
                        // Company Info
                        $this->Ln(15);
                        $this->SetFont('Arial', 'B', 10);
                        $this->MultiCell(0, 5, strtoupper($detailPerusahaan['kantor'] ?? 'PERUSAHAAN'), 0, 'C');
                        $this->SetFont('Arial', 'B', 8);
                        $this->MultiCell(70, 4, ucwords(strtolower($detailPerusahaan['alamat'] ?? '')), 0, 'C');
                        $this->Ln(2);
                
                        // Title
                        $this->SetFont('Arial', 'B', 10);
                        $this->Cell(0, 5, 'SALES ORDER', 0, 1, 'C');
                        $this->Ln(2);
                    }

                    function AddContent()
                    {
                        $this->SetFont('Arial', 'B', 8);
                        // Tabel Header
                        $viewdata = $this->getdata['detail'] ?? [];                       
                        $detail_customer = $this->getdata['detail_customer'] ?? [];

                        $this->Cell(35, 4, $detail_customer['nama'] ?? '', 0, 0, 'L');
                        $this->Cell(35, 4, $viewdata['jenis_penjualan'] ?? '', 0, 1, 'R');
                        $this->Cell(35, 4, $viewdata['nomor'] ?? '', 0, 0, 'L');
                        $this->Cell(35, 4, Carbon::parse($viewdata['tanggal'])->format('d F Y') ?? '', 0, 1, 'R');   
                        $this->Cell(50, 4, 'Kasir : '.(isset($this->getdata['user_transaksi']['full_name']) ? $this->getdata['user_transaksi']['full_name'] : ''), 0, 0, 'L'); 
                        $this->Cell(20, 4, Carbon::parse($viewdata['created_at'])->setTimezone('Asia/Jakarta')->format('H:i:s') ?? '', 0, 1, 'R');  
                        $this->Cell(10, 4, 'Note  : ' , 0, 0, 'L'); 
                        $this->MultiCell(60, 4, $viewdata['ket'] ?? '' , 0, 'L'); 
                        $this->Cell(15, 4, 'Mekanik  : ' , 0, 0, 'L'); 
                        $this->MultiCell(55, 4, (!empty($this->getdata['detail_mekanik']) ? implode(', ', array_column($this->getdata['detail_mekanik'], 'nama')) : '') , 0, 'L'); 
                        $this->Ln(4);                    
                
                        // Tabel Isi                        
                        $no=1;
                        foreach ($this->getdata['list_produk'] as $item) {
                            $no_data = $item['kode_barang'];
                            $nm_prod = $this->getdata['detail_produk'][$no_data]['nama'] ?? 'Belum ditentukan';
                            $satuan_prod = $this->getdata['satuan_barang_produk'][$no_data]['nama'] ?? 'Belum ditentukan';
                
                            $this->Cell(5, 4, $no, 0, 0, 'C');
                            $this->MultiCell(65, 4, $nm_prod, 0, 'L');
                            $this->Cell(5, 4, '', 0, 0, 'C');
                            $this->Cell(10, 4, number_format($item['jumlah_jual'], 2), 0, 0, 'L');
                            $this->Cell(10, 4, $satuan_prod, 0, 0, 'L');
                            $this->Cell(14, 4, number_format($item['harga'], 0), 0, 0, 'L');
                            $this->Cell(8, 4, number_format($item['diskon_persen'],2), 0, 0, 'L');
                            $this->Cell(8, 4, number_format($item['diskon_persen2'],2), 0, 0, 'L');
                            $this->Cell(15, 4, number_format($item['total_harga'],2), 0, 1, 'R');

                            $no++;
                        }
                
                        // Total dan Diskon
                        $detail = $this->getdata['detail'] ?? [];
                        $this->Ln(2);
                
                        $this->SetFont('Arial', 'B', 9);
                
                        $this->Cell(40, 4, 'Total', 0, 0, 'L');
                        $this->Cell(30, 4, number_format($detail['total'] ?? 0, 2, ',', '.'), 0, 1, 'R');
                
                        $this->Cell(40, 4, 'Discount', 0, 0, 'L');
                        $this->Cell(30, 4, number_format($detail['diskon_harga'] ?? 0, 2, ',', '.'), 0, 1, 'R');

                        $this->Cell(40, 4, 'Grand Total', 0, 0, 'L');
                        $this->Cell(30, 4, number_format($detail['grand_total'] ?? 0, 2, ',', '.'), 0, 1, 'R');
                    }
                
                    function Footer()
                    {
                        $this->SetY(-15);
                        $this->SetFont('Arial', 'B', 8);
                        $this->Cell(0, 5, 'Terima kasih telah berbelanja!', 0, 1, 'C');
                        $this->SetFont('Arial', '', 7);
                        // $this->Cell(0, 5, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
                    }
                }
                
                // Hitung tinggi kertas secara dinamis
                $data = curlget("{$url_api}v1/cash/viewpenjualan?token={$key_token}&u={$admin_login}&code_data={$print_code}&tipe_data=group");        
                $productCount = count(json_decode($data, true)['results']['list_produk'] ?? []);
                $lineHeight = 10; // Tinggi setiap baris produk
                $headerFooterHeight = 90; // Tinggi header + footer
                $contentHeight = $productCount * $lineHeight + 20; // Tinggi konten produk
                $paperHeight = max(80, $headerFooterHeight + $contentHeight);
                
                // Buat PDF
                $pdf = new PDF('P', 'mm', [80, $paperHeight]);
                $pdf->SetTitle('Struk Penjualan');
                $pdf->AliasNbPages();
                $pdf->AddPage();
                $pdf->AddContent();
                $pdf->Output();                                  
            }
        }
    }else{
        echo "<meta http-equiv='refresh' content='0;/'>";
    }


?>