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

            $get_data = curlget($url_api."v1/viewpenerimaan?token=".$key_token."&u=".$admin_login."&nomor_penerimaan=".$print_code);
            $get_data = json_decode($get_data, TRUE);
            $getdata = $get_data['results'];
        
            if($get_data['status_message'] == 'failed'){
                echo "<meta http-equiv='refresh' content='0;/'>";
            }else{

                class PDF extends MultiCellTable
                {
                    public $isFinished;

                    function Header()
                    {
                        $url_api = $_REQUEST['api'].'/';
                        $admin_login = $_REQUEST['u'];
                        $key_token = $_REQUEST['token'];
                        $print_code = $_REQUEST['print_code'];

                        $get_data = curlget($url_api."v1/viewpenerimaan?token=".$key_token."&u=".$admin_login."&nomor_penerimaan=".$print_code."&tipe_data=group");
                        $get_data = json_decode($get_data, TRUE);
                        $getdata = $get_data['results'];

                        if ($getdata['detail_perusahaan']['foto'] == NULL) {
                            $this->Image(__DIR__ . '/../themes/admin/AdminOne/image/public/icon.png', 10, 5.5, 26);
                        } else {
                            $imagePath = __DIR__ . '/../themes/admin/AdminOne/image/public/' . $getdata['detail_perusahaan']['foto'];
                            if (file_exists($imagePath)) {
                                $this->Image($imagePath, 10, 5.5, 30);
                            } else {
                                $this->Image(__DIR__ . '/../themes/admin/AdminOne/image/public/icon.png', 10, 5.5, 26);
                            }
                        } 
                        
                        $jenis = strtolower($getdata['detail_perusahaan']['jenis']);
                        $jenis = ucwords($jenis);

                        $alamat = strtolower($getdata['detail_perusahaan']['alamat']);
                        $alamat = ucwords($alamat);                        
                        
                        // $this->Image($url_api.'image/footer-new.png',-10,113,220);

                        $this->SetFont('Arial','B',14);
                        $this->Ln(3);
                        $this->Cell(45);
                        $this->Cell(0,5,strtoupper($getdata['detail_perusahaan']['kantor']),0,0,'L');
                        $this->Ln(6);
                        $this->SetFont('Arial','',9);
                        $this->Cell(45);
                        $this->MultiCell(210,5,$jenis,0,'L');
                        $this->Cell(45);
                        $this->Cell(0,5,$alamat,0,0,'L');
                        $this->Ln();
                        $this->Cell(45.5);
                        $this->Cell(0,5,'Email : ' .$getdata['detail_perusahaan']['email'],0,0,'L');

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
                        $this->Cell(0,5,'PENERIMAAN BARANG',0,0,'C');
                        $this->Ln(10);

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

                        $this->setFont('Arial','',9);
                        $this->Cell(5);
                        $this->MultiCell($setw40,$seth,'Receipt No',0);
                        $getx+=$setw40;
                        $this->SetXY($getx, $gety);
                        $this->MultiCell($setw5,$seth,':',0);
                        $getx+=$setw5;
                        $this->SetXY($getx, $gety);
                        $this->MultiCell($setw40,$seth,($getdata['detail']['nomor_penerimaan'] ?? 'Belum ditentukan'),0);
                        $setmulti_h = $this->GetY();
                        $setmulti_h= $setmulti_h-$gety;
                        $getx+=$setw70 - 7.5;
                        $this->SetXY($getx, $gety);

                        $this->setFont('Arial','',9);
                        $this->Cell(17.5);
                        $this->MultiCell($setw30,$seth,'Purchase No',0);
                        $getx+=$setw30;
                        $this->SetXY($getx, $gety);
                        $this->Cell(17.5);
                        $this->MultiCell($setw5,$seth,':',0);
                        $getx+=$setw5;
                        $this->SetXY($getx, $gety);
                        $this->Cell(16.5);
                        $this->MultiCell($setw45,$seth, ($getdata['detail_pembelian']['nomor'] ?? 'Belum ditentukan'),0);
                        $getx+=$setw45;
                        $this->SetXY($getx, $gety);
                        
                        $this->Ln();
                        $getx=$mtx; 
                        $gety+=$seth;
                        $this->SetXY($getx, $gety);
                        
                        $this->Ln($setmulti_h-4);

                        $this->setFont('Arial','',9);
                        $this->Cell(5);
                        $this->MultiCell($setw40,$seth,'Date',0);
                        $getx+=$setw40;
                        $this->SetXY($getx, $gety);
                        $this->MultiCell($setw5,$seth,':',0);
                        $getx+=$setw5;
                        $this->SetXY($getx, $gety);
                        $this->MultiCell($setw45 + 8,$seth,isset($getdata['detail']['tanggal']) ? date('j F Y',strtotime($getdata['detail']['tanggal'])) : 'Belum ditentukan',0);
                        $setmulti_h = $this->GetY();
                        $setmulti_h= $setmulti_h-$gety;
                        $getx+=$setw45;
                        $this->SetXY($getx, $gety);
                        $getx+=$setw5 + 12.5;
                        $this->SetXY($getx, $gety);

                        $this->Cell(17.5);
                        $this->MultiCell($setw30,$seth,'Date',0);
                        $getx+=$setw30;
                        $this->SetXY($getx, $gety);
                        $this->Cell(17.5);
                        $this->MultiCell($setw5,$seth,':',0);
                        $getx+=$setw5;
                        $this->SetXY($getx, $gety);
                        $this->Cell(16.5);
                        $this->MultiCell($setw30 + 10,$seth,isset($getdata['detail_pembelian']['tanggal']) ? date('j F Y',strtotime($getdata['detail_pembelian']['tanggal'])) : 'Belum ditentukan',0);
                        $getx+=$setw45;
                        $this->SetXY($getx, $gety);
                        
                        $this->Ln();
                        $getx=$mtx; 
                        $gety+=$seth;
                        $this->SetXY($getx, $gety);
                        
                        $this->Ln($setmulti_h-4);

                        $this->setFont('Arial','',9);
                        $this->Cell(5);
                        $this->MultiCell($setw40,$seth,'Supplier',0);
                        $getx+=$setw40;
                        $this->SetXY($getx, $gety);
                        $this->MultiCell($setw5,$seth,':',0);
                        $getx+=$setw5;
                        $this->SetXY($getx, $gety);
                        $this->MultiCell($setw45 + 8,$seth,($getdata['detail_supplier']['nama'] ?? 'Belum ditentukan'),0);
                        $setmulti_h = $this->GetY();
                        $setmulti_h= $setmulti_h-$gety;
                        $getx+=$setw45;
                        $this->SetXY($getx, $gety);
                        $getx+=$setw5 + 12.5;
                        $this->SetXY($getx, $gety);

                        $this->Cell(17.5);
                        $this->MultiCell($setw30,$seth,'Warehouse',0);
                        $getx+=$setw30;
                        $this->SetXY($getx, $gety);
                        $this->Cell(17.5);
                        $this->MultiCell($setw5,$seth,':',0);
                        $getx+=$setw5;
                        $this->SetXY($getx, $gety);
                        $this->Cell(16.5);
                        $this->MultiCell($setw30 + 10,$seth,($getdata['detail_gudang']['nama'] ?? 'Belum ditentukan'),0);
                        $getx+=$setw45;
                        $this->SetXY($getx, $gety);
            
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
                        $this->Ln(3); 

                        $this->SetLineWidth(0.3);
                        $this->SetWidths(array(7,150.5,28));
                        $this->SetHeights(array(7));
                        $this->setDrawColor(0,0,0);
                        $this->setTextColor(0,0,0);
                        $this->setFillColor(255,255,255);
                        $this->setFont('Arial','B',9);
                        $this->Ln(1);
                        $this->Cell(6.5);
                        $this->Row(array(
                            array('No','C'),
                            array('Product Name','C'),
                            array('Qty','C'),
                        ));
                    }
                    function Footer()
                    {
                        if($this->isFinished){
                            $url_api = $_REQUEST['api'].'/';
                            $admin_login = $_REQUEST['u'];
                            $key_token = $_REQUEST['token'];
                            $print_code = $_REQUEST['print_code'];

                            $get_data = curlget($url_api."v1/viewpenerimaan?token=".$key_token."&u=".$admin_login."&nomor_penerimaan=".$print_code."&tipe_data=group");
                            $get_data = json_decode($get_data, TRUE);
                            $getdata = $get_data['results'];

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
                            $this->MultiCell($sethfull,$seth,($getdata['detail']['ket'] ?? 'Belum ditentukan'),0);
                            $setmulti_h = $this->GetY();
                            $setmulti_h= $setmulti_h-$gety;
                            $getx+=$setw40;
                            $this->SetXY($getx, $gety);
                            $getx+=$setw5;
                            $this->SetXY($getx, $gety);
                            $getx+=$setw70 - 7.5;
                            $this->SetXY($getx, $gety);

                            $this->Ln(4); 

                            $this->SetLineWidth(0.3);
                            $this->setDrawColor(0,0,0);
                            $this->setTextColor(0,0,0);
                            $this->setFillColor(255,255,255);
                            $this->Line(11.5,$gety-5,197,$gety-5);
                            $this->Line(11.5,$gety+19,197,$gety+19);
                            $this->Line(11.5,$gety-5,11.5,259.2);

                            $this->Line(197,$gety-5,197,259.2);
                            $this->Line(11.5,$gety+46.5,197,$gety+46.5);

                            $this->Line(11.5,$getx,11.5,$gety-5);
                            $this->Line(18.5,$getx,18.5,$gety-5);
                            $this->Line(169,$getx,169,$gety-5);
                            $this->Line(197,$getx,197,$gety-5);

                            $hmt_add = 3.5;
                            $this->Ln(3.35);
                            $this->setX(11.5);
                            $this->setFont('Arial','B',9);
                            $this->MultiCell(185.5,$hmt_add,'Reception Address :',0);
                            $this->setX(14.5);
                            $this->setFont('Arial','',9);
                            $this->MultiCell(178.5,$hmt_add,($getdata['detail_gudang']['alamat'] ?? 'Belum ditentukan'),0,'L');
                            $this->Ln(5.5);

                            $getyln = $this->GetY();
                            $this->SetLineWidth(0.3);
                            $this->setDrawColor(0,0,0);
                            $this->setTextColor(0,0,0);
                            $this->setFillColor(255,255,255);
                                        
                            $this->setFont('Arial','',10);
                            $this->Cell(25);
                            $this->Cell(20,5,'',0,0,'C');
                            $this->Ln(20);
                            $this->Cell(25);
                            $this->Cell(20,5,'',0,0,'C');
                                        
                            $this->Ln(-20);
                            $this->setFont('Arial','',10);
                            $this->Cell(85);
                            $this->Cell(20,5,'Received By',0,0,'C');
                            $this->Ln(20);
                            $this->Cell(85);
                            $this->Cell(20,5,'( '.($getdata['user_transaksi']['full_name'] ?? 'Belum ditentukan').' )',0,0,'C');
                                        
                            $this->Ln(-20);
                            $this->setFont('Arial','',10);
                            $this->Cell(150);
                            $this->Cell(20,5,'Delivered By',0,0,'C');
                            $this->Ln(20);
                            $this->Cell(150);
                            $this->Cell(20,5,'( _______________ )',0,0,'C');
                        }

                        $this->SetY(-10);
                        $this->setFont('Arial','B',7);
                        $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
                    }
                }

                $pdf = new PDF('P', 'mm', 'A4');
                $pdf->SetMargins(5,5,10);
                $title = 'Print PO | PERDANA MOTOR';
                $pdf->SetTitle($title);
                $pdf->SetAuthor('PERDANA MOTOR');	
                $pdf->AliasNbPages();
                $pdf->AddPage();
                $pdf->SetAutoPageBreak(false);
                $pdf->isFinished = false;

                $mtx = $pdf->GetX();
                $gety = $pdf->GetY();
                $getx = $pdf->GetX();

                $height_of_cell = 60;
                $page_height = 327.5;
                $bottom_margin = 80;

                $getx_ln = $page_height - $gety;
                $getx_ln = $getx_ln - 55.5;

                $getx_isf = $page_height - $getx;
                $getx_isf = $getx_isf - 45.5;
                
                $pdf->ln(1);
                $no=1;
                $pdf->setFont('Arial','',9);              
                
                foreach ($getdata['list_produk_group'] as $view_data) {
                    $id = $view_data['id'];
                    $id = str_replace('-','',$id);
                    $nm_prod = ($get_data['results']['detail_produk'][$id]['nama'] ?? 'Belum ditentukan');
                    $qty_terima = ($view_data['jumlah_terima'] ?? 0);

                    $pdf->SetHeights(array(5));
                    $pdf->setDrawColor(255,255,255);
                    $pdf->setTextColor(0,0,0);
                    $pdf->setFillColor(255,255,255);
                    $pdf->Cell(6.5);
                    $pdf->Row(array(
                        array($no,'C'),
                        array($nm_prod,'L'),
                        array(number_format($qty_terima,0,"",".").' '.($get_data['results']['satuan_produk'][$id]['nama'] ?? 'Belum ditentukan'),'C'),
                    ));

                    $pdf->CheckPageBreak(6.5);

                    $pdf->SetLineWidth(0.3);
                    $pdf->setDrawColor(0,0,0);
                    $pdf->setTextColor(0,0,0);
                    $pdf->setFillColor(255,255,255);
                    $pdf->Line(11.5,$gety,11.5,$getx_ln);
                    $pdf->Line(18.5,$gety,18.5,$getx_ln);
                    $pdf->Line(169,$gety,169,$getx_ln);
                    $pdf->Line(197,$gety,197,$getx_ln);

                    $space_left=$page_height-($pdf->GetY()+$bottom_margin);
                    if ($height_of_cell > $space_left) {
                        $pdf->Line(11.5,$getx_isf,11.5,$gety);
                        $pdf->Line(18.5,$getx_isf,18.5,$gety-5);
                        $pdf->Line(169,$getx_isf,169,$gety-5);
                        $pdf->Line(197,$getx_isf,197,$gety-5);
                        
                        $pdf->Line(11.5,$getx_isf,197,$getx_isf);

                        $pdf->AddPage();
                        $pdf->ln(1);
                        $pdf->isFinished = false;
                    }

                    $no++;
                }
                $pdf->isFinished = true;
                
                $pdf->Output('I','Print-'.$getdata['detail']['nomor_penerimaan'].'.pdf');
            }
        }
    }else{
        echo "<meta http-equiv='refresh' content='0;/'>";
    }


?>