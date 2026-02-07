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

        $get_user = curlget($url_api."v1/viewadminlogin?token=".$key_token."&u=".$admin_login);
        $get_user = json_decode($get_user, TRUE);
        
        if($get_user['status_message'] == 'failed'){
            echo "<meta http-equiv='refresh' content='0;/'>";
        }else{
            $get_data = curlget($url_api."v1/viewpembelian?token=".$key_token."&u=".$admin_login."&code_data=".$print_code);
            $get_data = json_decode($get_data, TRUE);
            $getdata = $get_data['results'];
        
            if($get_data['status_message'] == 'failed'){
                echo "<meta http-equiv='refresh' content='0;/'>";
            }else{
                class PDF extends MultiCellTable
                {  
                    protected $getdata;
                    protected $mata_uang = 'Rp';
                    protected $kurs_harga = 'Rupiah';

                    function __construct()
                    {
                        parent::__construct();

                        $url_api = $_REQUEST['api'] . '/';
                        $admin_login = $_REQUEST['u'];
                        $key_token = $_REQUEST['token'];
                        $print_code = $_REQUEST['print_code'];

                        $get_data = curlget($url_api."v1/viewpembelian?token={$key_token}&u={$admin_login}&code_data={$print_code}&tipe_data=group");
                        $get_data = json_decode($get_data, TRUE);
                        $this->getdata = $get_data['results'];
                    }

                    function Header()
                    {
                        if ($this->getdata['detail_perusahaan']['foto'] == NULL) {
                            $this->Image(__DIR__ . '/../themes/admin/AdminOne/image/public/icon.png', 10, 5.5, 26);
                        } else {
                            $imagePath = __DIR__ . '/../themes/admin/AdminOne/image/public/' . $this->getdata['detail_perusahaan']['foto'];
                            if (file_exists($imagePath)) {
                                $this->Image($imagePath, 10, 5.5, 30);
                            } else {
                                $this->Image(__DIR__ . '/../themes/admin/AdminOne/image/public/icon.png', 10, 5.5, 26);
                            }
                        }                        

                        $jenis = strtolower($this->getdata['detail_perusahaan']['jenis']);
                        $jenis = ucwords($jenis);

                        $alamat = strtolower($this->getdata['detail_perusahaan']['alamat']);
                        $alamat = ucwords($alamat);                        
                        
                        // $this->Image(__DIR__ . '/../themes/admin/AdminOne/image/public/footer-new.png',-10,113,220);

                        $this->SetFont('Arial','B',14);
                        $this->Ln(3);
                        $this->Cell(45);
                        $this->Cell(0,5,strtoupper($this->getdata['detail_perusahaan']['kantor']),0,0,'L');
                        $this->Ln(6);
                        $this->SetFont('Arial','',9);
                        $this->Cell(45);
                        $this->MultiCell(210,5,$jenis,0,'L');
                        $this->Cell(45);
                        $this->Cell(0,5,$alamat,0,0,'L');
                        $this->Ln();
                        $this->Cell(45.5);
                        $this->Cell(0,5,'Email : ' .$this->getdata['detail_perusahaan']['email'],0,0,'L');

                        $gety = $this->GetY();
                        $this->SetLineWidth(0.6);
                        $this->setDrawColor(0,0,0);
                        $this->setTextColor(0,0,0);
                        $this->setFillColor(255,255,255);
                        $this->Line(10,$gety+7,197,$gety+7);
                        $this->SetLineWidth(0.3);
                        $this->Line(10,$gety+8,197,$gety+8);

                        $this->Ln(6.5);
                        
                        $this->SetFont('Arial','B',14);
                        $this->Ln();
                        $this->Cell(0,5,'PURCHASE ORDER',0,0,'C');
                        $this->Ln(7);

                        $mtx = $this->GetX();
                        $gety = $this->GetY();
                        $getx = $this->GetX();
            
                        $setw5 = 5;
                        $setw30 = 30;
                        $setw40 = 40;
                        $setw45 = 45;
                        $setw70 = 70;
                        $sethfull = 148;
                        $seth = 4;
                        $setmulti_h = 0;
            
                        $this->setFont('Arial','B',10);
                        $this->Cell(5);
                        $this->MultiCell($setw40,$seth,'Supplier :',0);
                        $getx+=$setw40;
                        $this->SetXY($getx, $gety);
                        $this->MultiCell($setw5,$seth,'',0);
                        $getx+=$setw5;
                        $this->SetXY($getx, $gety);
                        $this->MultiCell($setw40,$seth,'',0);
                        $setmulti_h = $this->GetY();
                        $setmulti_h= $setmulti_h-$gety;
                        $getx+=$setw70 + 7.5;
                        $this->SetXY($getx, $gety);
            
                        $this->setFont('Arial','',9);
                        $this->Cell(1);
                        $this->MultiCell($setw30,$seth,'Date',0);
                        $getx+=$setw30;
                        $this->SetXY($getx, $gety);
                        $this->MultiCell($setw5,$seth,':',0);
                        $getx+=$setw5;
                        $this->SetXY($getx, $gety);
                        $this->MultiCell($setw45,$seth, isset($this->getdata['detail']['tanggal']) ? date('j F Y',strtotime($this->getdata['detail']['tanggal'])) : 'Belum ditentukan',0);
                        $getx+=$setw45;
                        $this->SetXY($getx, $gety);
                        
                        $this->Ln();
                        $getx=$mtx; 
                        $gety+=$seth;
                        $this->SetXY($getx, $gety);
                        
                        $this->Ln($setmulti_h-4);
            
                        $this->setFont('Arial','B',9);
                        $this->Cell(5);
                        $this->MultiCell($setw70 + $setw40,$seth,($this->getdata['detail_customer']['nama'] ?? 'Belum ditentukan'),0);
                        $this->setFont('Arial','',9);
                        $this->Cell(5);
                        $this->MultiCell($setw70 + 8,$seth,($this->getdata['detail_customer']['alamat'] ?? 'Belum ditentukan'),0,'L');
                        $this->Cell(5);
                        $setmulti_h = $this->GetY();
                        $setmulti_h= $setmulti_h-$gety;
                        $getx+=$setw40;
                        $this->SetXY($getx, $gety);
                        $getx+=$setw5;
                        $this->SetXY($getx, $gety);
                        $getx+=$setw70 + 7.5;
                        $this->SetXY($getx, $gety);
            
                        $this->Cell(1);
                        $this->MultiCell($setw30,$seth,'Purchase No',0);
                        $getx+=$setw30;
                        $this->SetXY($getx, $gety);
                        $this->MultiCell($setw5,$seth,':',0);
                        $getx+=$setw5;
                        $this->SetXY($getx, $gety);
                        $this->MultiCell($setw45 + 10,$seth,($this->getdata['detail']['nomor'] ?? 'Belum ditentukan'),0);
                        $getx+=$setw45;
                        $this->SetXY($getx, $gety);
                        
                        $this->Ln(7);
                        $getx=$mtx; 
                        $gety+=$seth;
                        $this->SetXY($getx, $gety);
                        
                        $this->Ln($setmulti_h-4);
            
                        $this->setFont('Arial','',9);
                        $this->Cell(5);
                        $this->MultiCell($setw40,$seth,'Phone No',0);
                        $getx+=$setw40;
                        $this->SetXY($getx, $gety);
            
                        $this->Ln($setmulti_h-4);
                        $this->Cell($setw40);
                        $this->MultiCell($setw5,$seth,':',0);
                        $getx+=$setw5;
                        $this->SetXY($getx, $gety);
            
                        $this->Ln($setmulti_h-4);
                        $this->Cell($setw40 + $setw5);
                        $this->MultiCell($setw70,$seth,($this->getdata['detail_customer']['no_telp'] ?? 'Belum ditentukan'),0);
                        $getx+=$setw70 + 7.5;
                        $this->SetXY($getx, $gety);
                        
                        $this->Ln(7);
                        $getx=$mtx; 
                        $gety+=$seth;
                        $this->SetXY($getx, $gety);
                        
                        $this->Ln($setmulti_h-4);
            
                        $this->Cell(1);
                        $this->MultiCell($setw30,$seth,'',0);
                        $getx+=$setw30;
                        $this->SetXY($getx, $gety);
                        $this->MultiCell($setw5,$seth,'',0);
                        $getx+=$setw5;
                        $this->SetXY($getx, $gety);
                        $this->MultiCell($setw45 + 10,$seth,'',0);
                        $getx+=$setw45;
                        $this->SetXY($getx, $gety);

                        $this->Ln($setmulti_h); 
                        $this->Ln(1); 

                        $this->SetLineWidth(0.3);
                        $this->SetWidths(array(7,47.5,28,17,28,28,30));
                        $this->setDrawColor(0,0,0);
                        $this->setTextColor(0,0,0);
                        $this->setFillColor(255,255,255);
                        $this->setFont('Arial','B',9);
                        $this->Ln(1);
                        $this->Cell(6.5);
                        $this->RowHeader(array(
                            array('No','C'),
                            array('Product Name','C'),
                            array('Price','C'),
                            array('Qty','C'),
                            array('Discount','C'),
                            array('Netto','C'),
                            array('Total','C'),
                        ), 7);
                    }

                    function Content()
                    {  
                        $mtx = $this->GetX();
                        $gety = $this->GetY();
                        $getx = $this->GetX();

                        $height_of_cell = 60;
                        $page_height = 327.5;
                        $bottom_margin = 80;

                        $getx_ln = $page_height - $gety;
                        $getx_ln = $getx_ln - 48.5;

                        $getx_isf = $page_height - $getx;
                        $getx_isf = $getx_isf - 42; 
                         
                        $this->ln(1);
                        $no=1;
                        $this->setFont('Arial','',8);

                        foreach ($this->getdata['list_produk'] as $view_data) {
                            $no_data = $view_data['kode_barang'];
                            $harga_beli = ($view_data['harga'] ?? 0);
                            $nm_prod = ($this->getdata['detail_produk'][$no_data]['nama'] ?? 'Belum ditentukan');

                            // Loop untuk membuat 30 data test
                            for ($i = 1; $i <= 1; $i++) {                                
                                $tinggi = $this->getDynamicHeight($nm_prod, 47.5); // 47.5 = lebar kolom product
                                $this->SetHeights(array($tinggi));

                                $this->setDrawColor(255,255,255);
                                $this->setTextColor(0,0,0);
                                $this->setFillColor(255,255,255);
                                $this->Cell(6.5);
                                $this->Row([
                                    array($no,'C'),
                                    array($nm_prod,'L'),
                                    array(number_format($harga_beli,2,",","."),'R'),
                                    array(number_format($view_data['jumlah_beli'] ?? 0,0,"",".").' '.($this->getdata['satuan_produk'][$no_data]['nama'] ?? 'Belum ditentukan'),'C'),
                                    array(number_format($view_data['diskon_persen'] ?? 0,2,",",".").'   +   '.number_format($view_data['diskon_persen2'] ?? 0,2,",","."),'C'),
                                    array(number_format($view_data['harga_netto'] ?? 0,2,",","."),'R'),
                                    array(number_format($view_data['total_harga'] ?? 0,2,",","."),'R'),
                                ]);

                                $this->SetLineWidth(0.3);
                                $this->setDrawColor(0,0,0);
                                $this->setTextColor(0,0,0);
                                $this->setFillColor(255,255,255);
                                // Garis Vertical isi tabel/content
                                $this->Line(11.5,$gety,11.5,$getx_ln);
                                $this->Line(18.5,$gety,18.5,$getx_ln);
                                $this->Line(66,$gety,66,$getx_ln);
                                $this->Line(94,$gety,94,$getx_ln);
                                $this->Line(111,$gety,111,$getx_ln);
                                $this->Line(139,$gety,139,$getx_ln);
                                $this->Line(167,$gety,167,$getx_ln);
                                $this->Line(197,$gety,197,$getx_ln);

                                $space_left=$page_height-($this->GetY()+$bottom_margin);
                                if ($height_of_cell > $space_left) {
                                    $this->Line(11.5,$getx_isf,11.5,$gety);
                                    $this->Line(18.5,$getx_isf,18.5,$gety-5);
                                    $this->Line(66,$getx_isf,66,$gety-5);
                                    $this->Line(94,$getx_isf,94,$gety-5);
                                    $this->Line(111,$getx_isf,111,$gety-5);
                                    $this->Line(139,$getx_isf,139,$gety-5);
                                    $this->Line(167,$getx_isf,167,$gety-5);
                                    $this->Line(197,$getx_isf,197,$gety-5);
                                    
                                    $this->Line(11.5,$getx_isf,197,$getx_isf);

                                    // $this->AddPage();
                                    $this->ln(1);
                                }
                                $no++;
                                $this->CheckPageBreak(6.5);
                            }                            
                        }

                        // GrandTotal
                        $diskon = number_format($this->getdata['detail']['diskon_harga'] ?? 0,2,",",".");

                        $this->SetY(-86.5);
                        
                        $this->Ln(-1.9);
                        $this->setFont('Arial','',8);

                        $mtx = $this->GetX();
                        $gety = $this->GetY();
                        $getx = $this->GetX();

                        $setw5 = 5;
                        $setw30 = 30;
                        $setw40 = 40;
                        $setw45 = 45;
                        $setw70 = 70;
                        $sethfull = 148;
                        $seth = 4;
                        $setmulti_h = 0;

                        $this->setFont('Arial','B',9);
                        $this->Ln(1);
                        $this->Cell(7);
                        $this->MultiCell($setw40,$seth,'Note :',0);
                        $getx+=$setw40;
                        $this->SetXY($getx, $gety);
                        $this->MultiCell($setw5,$seth,'',0);
                        $getx+=$setw5;
                        $this->SetXY($getx, $gety);
                        $this->MultiCell($setw40,$seth,'',0);
                        $setmulti_h = $this->GetY();
                        $setmulti_h= $setmulti_h-$gety;
                        $getx+=$setw70 - 7.5;
                        $this->SetXY($getx, $gety);
                        
                        $this->Ln();
                        $getx=$mtx; 
                        $gety+=$seth;
                        $this->SetXY($getx, $gety);
                        
                        $this->Ln($setmulti_h-4);
                        
                        $this->setFont('Arial','',9);
                        $this->Ln(1);
                        $this->Cell(12);
                        $this->MultiCell($setw70 + 30,$seth,($this->getdata['detail']['ket'] ?? 'Belum ditentukan'),0);
                        $setmulti_h = $this->GetY();
                        $setmulti_h= $setmulti_h-$gety;
                        $getx+=$setw40;
                        $this->SetXY($getx, $gety);
                        $getx+=$setw5;
                        $this->SetXY($getx, $gety);
                        $getx+=$setw70 - 7.5;
                        $this->SetXY($getx, $gety);

                        $this->Ln(-4); 
                        $this->setX(122);
                        $this->cell(40,5,'Total',0,0,'R');
                        $this->cell(34,5,number_format($this->getdata['detail']['total'] ?? 0,2,",","."),0,0,'R');
                        $this->Ln();
                        $this->setX(122);
                        $this->cell(40,5,'Discount (-)',0,0,'R');
                        $this->cell(34,5,$diskon,0,0,'R');
                        $this->Ln();
                        $this->setX(122);
                        $this->setFont('Arial','B',9);
                        $this->cell(40,5,'Grand Total',0,0,'R');
                        $this->setFont('Arial','B',9);
                        $this->cell(34,5,number_format($this->getdata['detail']['grand_total'] ?? 0,2,",","."),0,0,'R');
                        $this->Ln();
                        // $this->setX(122);
                        // $this->cell(40,5,'Shipping Cost (+)',0,0,'R');
                        // $this->cell(34,5,number_format($this->getdata['detail']['biaya_kirim'],2,",","."),0,0,'R');

                        $this->setX(122);
                        $this->setFont('Arial','',9);
                        $this->cell(40,5,'DPP',0,0,'R');
                        $this->cell(34,5,number_format($this->getdata['detail']['sub_total'] ?? 0,2,",","."),0,0,'R');
                        $this->Ln();
                        $this->setX(122);
                        $this->cell(40,5,'PPN',0,0,'R');
                        $this->cell(34,5,number_format($this->getdata['detail']['ppn'] ?? 0,2,",","."),0,0,'R');
                        $this->Ln();
                        
                        $this->Ln();

                        $this->SetLineWidth(0.3);
                        $this->setDrawColor(0,0,0);
                        $this->setTextColor(0,0,0);
                        $this->setFillColor(255,255,255);
                        // Garis horizontal note
                        $this->Line(11.5,$gety-5,197,$gety-5);
                        $this->Line(11.5,$gety+22,197,$gety+22);
                        $this->Line(11.5,$gety+34,197,$gety+34);
                        $this->Line(11.5,$gety+46.5,197,$gety+46.5);
                        // Garis vertical note
                        $this->Line(11.5,$gety-5,11.5,259.2);
                        $this->Line(139,$gety-5,139,234.5);
                        $this->Line(197,$gety-5,197,259.2);
                        
                        // $this->Ln(0.85);
                        $this->Ln(-1.5);
                        $this->setX(11.5);
                        $this->setFont('Arial','IB',9);
                        $this->MultiCell(185.5,7,terbilang(number_format($this->getdata['detail']['grand_total'] ?? 0,2,",","")),0,'L');

                        $hmt_add = 3.5;
                        $this->Ln(3.35);
                        $this->setX(11.5);
                        $this->setFont('Arial','B',9);
                        $this->MultiCell(185.5,$hmt_add,'Shipping Address :',0);
                        $this->setX(14.5);
                        $this->setFont('Arial','',9);
                        $this->MultiCell(178.5,$hmt_add,($this->getdata['detail_gudang']['alamat'] ?? 'Belum ditentukan'),0,'L');
                        $this->Ln(5.5);

                        $getyln = $this->GetY();
                        $this->SetLineWidth(0.3);
                        $this->setDrawColor(0,0,0);
                        $this->setTextColor(0,0,0);
                        $this->setFillColor(255,255,255);
                        // Garis vertical tandatangan
                        $this->Line(11.5,$getyln-5,11.5,287);
                        $this->Line(69,$getyln-1,69,287);
                        $this->Line(134,$getyln-1,134,287);
                        $this->Line(197,$getyln-5,197,287);
                        // Garis horizontal tandatangan
                        $this->Line(11.5,$getyln+27,197,$getyln+27);
                                    
                        $this->setFont('Arial','',10);
                        $this->Cell(25);
                        $this->Cell(20,5,'',0,0,'C');
                        $this->Ln(20);
                        $this->Cell(25);
                        $this->Cell(20,5,'',0,0,'C');
                                    
                        $this->Ln(-20);
                        $this->setFont('Arial','',10);
                        $this->Cell(85);
                        $this->Cell(20,5,'Approved By',0,0,'C');
                        $this->Ln(20);
                        $this->Cell(85);
                        $this->Cell(20,5,'( _______________ )',0,0,'C');
                                    
                        $this->Ln(-20);
                        $this->setFont('Arial','',10);
                        $this->Cell(150);
                        $this->Cell(20,5,'Purchase By',0,0,'C');
                        $this->Ln(20);
                        $this->Cell(150);
                        $this->Cell(20,5,'( '.($this->getdata['user_transaksi']['full_name'] ?? 'Belum ditentukan').' )',0,0,'C');
                    }

                    function Footer()
                    {
                        $this->SetY(-10);
                        $this->setFont('Arial','B',7);
                        $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
                    }
                }

                try {
                    $pdf = new PDF('P', 'mm', 'A4');
                    $pdf->SetMargins(5,5,10); // kiri, atas, kanan
                    $pdf->SetAutoPageBreak(true, 10); // Margin bawah=10
                    // $pdf->SetAutoPageBreak(false);
                    $title = 'Print SO | PERDANA MOTOR';
                    $pdf->SetTitle($title);
                    $pdf->SetAuthor('PERDANA MOTOR');	
                    $pdf->AliasNbPages();
                    $pdf->AddPage();
                    $pdf->Content();
                    $pdf->Output('I','Print-'.$getdata['detail']['nomor'].'.pdf');
                } catch (Exception $e) {
                    echo "Error generating PDF: " . $e->getMessage();
                }
            }
        }
    }else{
        echo "<meta http-equiv='refresh' content='0;/'>";
    }


?>