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

            $get_data = curlget($url_api."v1/viewpengeluarankas?token=".$key_token."&u=".$admin_login."&code_data=".$print_code);
            $get_data = json_decode($get_data, TRUE);
            $getdata = $get_data['results'];
        
            if($get_data['status_message'] == 'failed'){
                echo "<meta http-equiv='refresh' content='0;/'>";
            }else{

                class PDF extends MultiCellTable
                {
                    function Header()
                    {
                        $url_api = $_REQUEST['api'].'/';
                        $admin_login = $_REQUEST['u'];
                        $key_token = $_REQUEST['token'];
                        $print_code = $_REQUEST['print_code'];

                        $get_data = curlget($url_api."v1/viewpengeluarankas?token=".$key_token."&u=".$admin_login."&code_data=".$print_code);
                        $get_data = json_decode($get_data, TRUE);
                        $getdata = $get_data['results'];

                        if ($getdata['detail_perusahaan']['foto'] == NULL) {
                            $this->Image(__DIR__ . '/../themes/admin/AdminOne/image/public/icon.png', 10, 5.5, 26);
                        } else {
                            $imagePath = __DIR__ . '/../themes/admin/AdminOne/image/public/' . $getdata['detail_perusahaan']['foto'];
                            if (file_exists($imagePath)) {
                                $this->Image($imagePath, 10, 5.5, 24);
                            } else {
                                $this->Image(__DIR__ . '/../themes/admin/AdminOne/image/public/icon.png', 10, 5.5, 26);
                            }
                        } 
                        
                        $jenis = strtolower($getdata['detail_perusahaan']['jenis']);
                        $jenis = ucwords($jenis);

                        $alamat = strtolower($getdata['detail_perusahaan']['alamat']);
                        $alamat = ucwords($alamat);       

                        $mtx = $this->GetX();
                        $gety = $this->GetY();
                        $getx = $this->GetX();
                        
                        $setw0 = 0;
                        $setw5 = 5;
                        $setw30 = 30;
                        $setw40 = 40;
                        $setw45 = 45;
                        $setw60 = 60;
                        $setw70 = 70;
                        $setw80 = 80;
                        $setw90 = 90;
                        $sethfull = 148;
                        $seth = 4;
                        $seth5 = 5;
                        $setmulti_h = 0;

                        $this->SetFont('Arial','B',12);
                        $this->Ln(2);
                        $this->Cell(30);
                        $this->Cell($setw0+10,$seth5,strtoupper($getdata['detail_perusahaan']['kantor']),0,0,'L');
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
            
                        $this->SetFont('Arial','B',12);
                        $this->Ln(2);
                        $this->Cell(135);
                        $this->Cell($setw0,$seth5,'DETAIL TRANSAKSI KAS',0,0,'L');
                        $getx+=$setw30;
                        $this->SetXY($getx, $gety);
                        $this->MultiCell($setw5,$seth,'',0);
                        $getx+=$setw5;
                        $this->SetXY($getx, $gety);
                        $this->MultiCell($setw45,$seth, '',0);
                        $getx+=$setw45;
                        $this->SetXY($getx, $gety);

                        $this->Ln(6);
                        $getx=$mtx; 
                        $gety+=$seth;
                        $this->SetXY($getx, $gety);
                        
                        $this->Ln($setmulti_h-4);
            
                        $this->setFont('Arial','',9);
                        $this->Ln(3);
                        $this->Cell(30);
                        $this->MultiCell($setw90,$seth,$jenis,0);
                        $getx+=$setw40;
                        $this->SetXY($getx, $gety);
                        $this->MultiCell($setw5,$seth,'',0);
                        $getx+=$setw5;
                        $this->SetXY($getx, $gety);
                        $this->MultiCell($setw40,$seth,'',0);
                        // $setmulti_h = $this->GetY();
                        // $setmulti_h= $setmulti_h-$gety;
                        // $getx+=$setw70 - 7.5;
                        $this->SetXY($getx, $gety);
            
                        $this->setFont('Arial','',9);
                        $this->Ln(3);
                        $this->Cell(135);
                        $this->MultiCell($setw40,$seth,'Tanggal Transaksi',0);
                        $getx+=$setw30;
                        $this->SetXY($getx, $gety);
                        $this->Ln(3);
                        $this->Cell(175);
                        $this->MultiCell($setw5,$seth,':',0);
                        $getx+=$setw5;
                        $this->SetXY($getx, $gety);
                        $this->Ln(3);
                        $this->Cell(179);
                        $this->MultiCell($setw45,$seth, isset($getdata['detail']['tanggal']) ? format_tgl_only(strtotime($getdata['detail']['tanggal'])) : 'Belum ditentukan',0);
                        $getx+=$setw45;
                        $this->SetXY($getx, $gety);
                        
                        // $this->Ln(7);
                        // $getx=$mtx; 
                        // $gety+=$seth;
                        // $this->SetXY($getx, $gety);
                        
                        $this->Ln($setmulti_h-4);
            
                        $this->setFont('Arial','',9);
                        $this->Ln(6.5);
                        $this->Cell(30);
                        $this->Cell(0,5,$alamat,0,0,'L');
                        $getx+=$setw40;
                        $this->SetXY($getx, $gety);

                        $this->Ln(7);
                        $this->Cell(135);
                        $this->MultiCell($setw30,$seth,'Transaksi',0);
                        $getx+=$setw30;
                        $this->SetXY($getx, $gety);
                        $this->Ln(7);
                        $this->Cell(175);
                        $this->MultiCell($setw5,$seth,':',0);
                        $getx+=$setw5;
                        $this->SetXY($getx, $gety);
                        $this->Ln(7);
                        $this->Cell(179);
                        $this->MultiCell($setw45 + 10,$seth, 'Kas Keluar',0);
                        $getx+=$setw45;
                        $this->SetXY($getx, $gety);
                        
                        $this->Ln(7);
                        $getx=$mtx; 
                        $gety+=$seth;
                        $this->SetXY($getx, $gety);
                        
                        $this->Ln($setmulti_h-4);
            
                        $this->setFont('Arial','',9);
                        $this->Ln(6.5);
                        $this->Cell(30);
                        $this->Cell(0,5,'Email : ' .$getdata['detail_perusahaan']['email'],0,0,'L');
                        $getx+=$setw40;
                        $this->SetXY($getx, $gety);

                        $this->Ln(7);
                        $this->Cell(135);
                        $this->MultiCell($setw30,$seth,'No. Voucher',0);
                        $getx+=$setw30;
                        $this->SetXY($getx, $gety);
                        $this->Ln(7);
                        $this->Cell(175);
                        $this->MultiCell($setw5,$seth,':',0);
                        $getx+=$setw5;
                        $this->SetXY($getx, $gety);
                        $this->Ln(7);
                        $this->Cell(179);
                        $this->MultiCell($setw45 + 10,$seth,($getdata['detail']['nomor'] ?? 'Belum ditentukan'),0);
                        $getx+=$setw45;
                        $this->SetXY($getx, $gety);$this->Ln(7);
                        $getx=$mtx; 
                        $gety+=$seth;
                        $this->SetXY($getx, $gety);
                        
                        $this->Ln($setmulti_h-4);

                        $this->SetFont('Arial','B',14);
                        $this->Ln(2);
                        $this->Cell(0,5,'',0,0,'R');
                        $this->Ln();
                        $this->SetFont('Arial','',9);
                        $this->Cell(39);
                        $this->MultiCell(216,5,'',0,'R');
                        $this->Cell(0,5,'',0,0,'R');
                        $this->Ln(4);
                        $this->Cell(0,5,'',0,0,'R');
                        $this->Ln(-1);

                        $this->Ln($setmulti_h); 
                        $this->Ln(2); 

                        $this->SetLineWidth(0.3);
                        $this->SetWidths(array(7,120,90,30));
                        $this->SetHeights(array(7));
                        $this->setDrawColor(0,0,0);
                        $this->setTextColor(0,0,0);
                        $this->setFillColor(255,255,255);
                        $this->setFont('Arial','B',9);
                        $this->Ln(1);
                        $this->Cell(6);
                        $this->Row(array(
                            array('No','C'),
                            array('Keterangan','C'),
                            array('Nama Akun','C'),
                            array('Nilai Transaksi','C'),
                        ));
                    }
                    function Footer()
                    {
                        $this->SetY(-15);
                        $this->setFont('Arial','B',8);
                        $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
                    }
                }

                $pdf = new PDF('L', 'mm', array(210, 270));
                $pdf->SetMargins(5,5,10);
                $title = 'Print Detail Transaksi Kas | Perdana Motor';
                $pdf->SetTitle($title);
                $pdf->SetAuthor('Perdana Motor');	
                $pdf->AliasNbPages();
                $pdf->AddPage();
                $pdf->SetAutoPageBreak(false);

                $mtx = $pdf->GetX();
                $gety = $pdf->GetY();
                $getx = $pdf->GetX();

                $height_of_cell = 60;
                $page_height = 270;
                $bottom_margin = 7;
                
                $pdf->ln(0);
                $no=1;
                $pdf->setFont('Arial','',9);
                // foreach ($getdata['list_akun_biaya'] as $view_data) {
                    $pdf->SetHeights(array(8));
                    $pdf->setDrawColor(0,0,0);
                    $pdf->setTextColor(0,0,0);
                    $pdf->setFillColor(255,255,255);
                    $pdf->Cell(6.05);
                    $pdf->Row(array(
                        array($no,'C'),
                        array(($getdata['detail']['keterangan'] ?? 'Belum ditentukan'),'L'),
                        array(($getdata['detail']['jenis'] ?? 'Belum ditentukan'),'L'),
                        array(number_format($getdata['detail']['nilai'] ?? 0,2, ",", "."),'R'),
                    ));

                    $pdf->CheckPageBreak(50);

                    $space_left=$page_height-($pdf->GetY()+$bottom_margin);
                    if ($height_of_cell > $space_left) {
                        $pdf->AddPage();
                        $pdf->ln(1);
                    }

                    $no++;
                // }

                $pdf->SetFont('Arial','B',11);
                $pdf->Cell(6);
                $pdf->MultiCell(127,7,'Total Transkasi :',1,'R');
                $pdf->ln(-7);
                $pdf->Cell(133);
                $pdf->MultiCell(120,7,number_format($getdata['detail']['nilai'] ?? 0,2,",","."),1,'R');
                
                $pdf->ln(1);
                $no=1;
                $pdf->setFont('Arial','',8);

                $pdf->Output('I','print-detail-transaksi-kas.pdf');
            }
        }
    }else{
        echo "<meta http-equiv='refresh' content='0;/'>";
    }

?>