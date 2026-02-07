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

            $get_data = curlget($url_api."v1/viewsalespayment?token=".$key_token."&u=".$admin_login."&code_data=".$print_code."&tipe_data=group");
            $get_data = json_decode($get_data, TRUE);
            $getdata = $get_data['results'];
        
            if($get_data['status_message'] == 'failed'){
                echo "<meta http-equiv='refresh' content='0;/'>";
            }else{
                    $mata_uang = 'Rp';

                class PDF extends MultiCellTable
                {
                    function Header()
                    {
                        $url_api = $_REQUEST['api'].'/';
                        $admin_login = $_REQUEST['u'];
                        $key_token = $_REQUEST['token'];
                        $print_code = $_REQUEST['print_code'];

                        $get_data = curlget($url_api."v1/viewsalespayment?token=".$key_token."&u=".$admin_login."&code_data=".$print_code."&tipe_data=group");
                        $get_data = json_decode($get_data, TRUE);
                        $getdata = $get_data['results'];
                        $mata_uang = 'Rp';

                        if ($getdata['detail_perusahaan']['foto'] == NULL) {
                            $this->Image(__DIR__ . '/../themes/admin/AdminOne/image/public/icon.png', 10, 5.5, 22);
                        } else {
                            $imagePath = __DIR__ . '/../themes/admin/AdminOne/image/public/' . $getdata['detail_perusahaan']['foto'];
                            if (file_exists($imagePath)) {
                                $this->Image($imagePath, 10, 5.5, 22);
                            } else {
                                $this->Image(__DIR__ . '/../themes/admin/AdminOne/image/public/icon.png', 10, 5.5, 22);
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
                        $sethfull = 148;
                        $seth = 4;
                        $seth5 = 5;
                        $setmulti_h = 0;

                        $this->SetFont('Arial','B',12);
                        $this->Ln(2);
                        $this->Cell(27);
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
                        $this->Cell(115);
                        $this->Cell($setw0,$seth5,'PAYMENT RECEIPT - BILL',0,0,'L');
                        $getx+=$setw30;
                        $this->SetXY($getx, $gety);
                        $this->MultiCell($setw5,$seth,'',0);
                        $getx+=$setw5;
                        $this->SetXY($getx, $gety);
                        $this->MultiCell($setw45,$seth, '',0);
                        $getx+=$setw45;
                        $this->SetXY($getx, $gety);

                        $this->Ln();
                        $getx=$mtx; 
                        $gety+=$seth;
                        $this->SetXY($getx, $gety);
                        
                        $this->Ln($setmulti_h-4);
            
                        $this->setFont('Arial','',8);
                        $this->Ln(3);
                        $this->Cell(27);
                        $this->MultiCell($setw80,$seth,$jenis,0);
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
                        $this->Cell(115);
                        $this->MultiCell($setw30,$seth,'Date',0);
                        $getx+=$setw30;
                        $this->SetXY($getx, $gety);
                        $this->Ln(3);
                        $this->Cell(145);
                        $this->MultiCell($setw5,$seth,':',0);
                        $getx+=$setw5;
                        $this->SetXY($getx, $gety);
                        $this->Ln(3);
                        $this->Cell(149);
                        $this->MultiCell($sethfull,$seth,isset($getdata['detail']['tanggal']) ? format_tgl_tree(strtotime($getdata['detail']['tanggal'])) : 'Belum ditentukan',0);
                        $getx+=$setw45;
                        $this->SetXY($getx, $gety);
                        
                        $this->Ln($setmulti_h-4);
            
                        $this->setFont('Arial','',9);
                        $this->Ln(6.5);
                        $this->Cell(27);
                        $this->Cell(0,5,$alamat,0,0,'L');
                        $getx+=$setw40;
                        $this->SetXY($getx, $gety);

                        $this->Ln(7);
                        $this->Cell(115);
                        $this->MultiCell($setw30,$seth,'Voucher No',0);
                        $getx+=$setw30;
                        $this->SetXY($getx, $gety);
                        $this->Ln(7);
                        $this->Cell(145);
                        $this->MultiCell($setw5,$seth,':',0);
                        $getx+=$setw5;
                        $this->SetXY($getx, $gety);
                        $this->Ln(7);
                        $this->Cell(149);
                        $this->MultiCell($sethfull,$seth,($getdata['detail']['nomor'] ?? 'Belum ditentukan'),0);
                        $getx+=$setw45;
                        $this->SetXY($getx, $gety);
                        
                        $this->Ln(7);
                        $getx=$mtx; 
                        $gety+=$seth;
                        $this->SetXY($getx, $gety);
                        
                        $this->Ln($setmulti_h-4);
            
                        $this->setFont('Arial','',9);
                        $this->Ln(6.5);
                        $this->Cell(27);
                        $this->Cell(0,5,'Email : ' .$getdata['detail_perusahaan']['email'],0,0,'L');
                        $getx+=$setw40;
                        $this->SetXY($getx, $gety);

                        $this->Ln(7);
                        $this->Cell(115);
                        $this->MultiCell($setw30,$seth,'Sales Order',0);
                        $getx+=$setw30;
                        $this->SetXY($getx, $gety);
                        $this->Ln(7);
                        $this->Cell(145);
                        $this->MultiCell($setw5,$seth,':',0);
                        $getx+=$setw5;
                        $this->SetXY($getx, $gety);
                        $this->Ln(7);
                        $this->Cell(149);
                        $this->MultiCell($sethfull,$seth,($getdata['detail']['nomor_piutang'] ?? 'Belum ditentukan'),0);
                        $getx+=$setw45;
                        $this->SetXY($getx, $gety);
                        
                        $this->Ln(7);
                        $getx=$mtx; 
                        $gety+=$seth;
                        $this->SetXY($getx, $gety);
                        
                        $this->Ln($setmulti_h-4);

                        $this->Ln(2);

                        $this->SetFont('Arial','B',14);
                        $this->Ln(2);
                        $this->Cell(0,5,'',0,0,'R');
                        $this->Ln();
                        $this->SetFont('Arial','',9);
                        $this->Cell(39);
                        $this->MultiCell(156,5,'',0,'R');
                        $this->Cell(0,5,'',0,0,'R');
                        $this->Ln(4);
                        $this->Cell(0,5,'',0,0,'R');
                        $this->Ln(2);

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
                        $this->MultiCell($setw40,$seth,'Payment From :',0);
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

                        $this->setFont('Arial','B',9);
                        $this->Cell(12);
                        $this->MultiCell($sethfull,$seth,($getdata['detail_customer']['nama'] ?? 'Belum ditentukan'),0);
                        $this->setFont('Arial','',9);
                        $this->Cell(12);
                        $this->MultiCell($sethfull,$seth,($getdata['detail_customer']['alamat'] ?? 'Belum ditentukan'),0,'L');
                        $this->Cell(12);

                        $setmulti_h = $this->GetY();
                        $setmulti_h= $setmulti_h-$gety;
                        $getx+=$setw40;
                        $this->SetXY($getx, $gety);
                        $getx+=$setw5;
                        $this->SetXY($getx, $gety);
                        $getx+=$setw70 - 7.5;
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
                        $this->MultiCell($setw70,$seth,($getdata['detail_customer']['no_telp'] ?? 'Belum ditentukan'),0);
                        $getx+=$setw70 - 7.5;
                        $this->SetXY($getx, $gety);
                        
                        $this->Ln(7);
                        $getx=$mtx; 
                        $gety+=$seth;
                        $this->SetXY($getx, $gety);
                        
                        $this->Ln(7);
                        $getx=$mtx; 
                        $gety+=$seth;
                        $this->SetXY($getx, $gety);
                        
                        $this->Ln($setmulti_h-4);

                        $this->setFont('Arial','',9);
                        $getx+=$setw40;
                        $getx+=$setw5;
                        $getx+=$setw70 - 7.5;
                        $this->SetXY($getx, $gety);
                        
                        $this->Ln(7);
                        $getx=$mtx; 
                        $gety+=$seth;
                        $this->SetXY($getx, $gety);
                        
                        $this->Ln($setmulti_h-4);

                        $this->setFont('Arial','',9);
                        $getx+=$setw40;
                        $getx+=$setw5;
                        $getx+=$setw70 - 7.5;
                        $this->SetXY($getx, $gety);
                        
                        $this->Ln(7);
                        $getx=$mtx; 
                        $gety+=$seth;
                        $this->SetXY($getx, $gety);
                        
                        $this->Ln($setmulti_h-4);

                        $this->Ln($setmulti_h); 
                        $this->Ln(-15); 
                    }
                }

                $pdf = new PDF('P', 'mm', array(210, 270));
                $pdf->SetMargins(5,5,10);
                $title = 'Print Pembayaran | Perdana Motor';
                $pdf->SetTitle($title);
                $pdf->SetAuthor('Perdana Motor');	
                $pdf->AliasNbPages();
                $pdf->AddPage();
                $pdf->SetAutoPageBreak(false);

                $gety = $pdf->GetY();
                
                $pdf->Ln(2);
                $pdf->SetLineWidth(0.3);
                $pdf->setDrawColor(0,0,0);
                $pdf->setTextColor(0,0,0);
                $pdf->setFillColor(255,255,255);
                $pdf->Line(11.5,$gety,197,$gety);
                
                $pdf->Ln(2);
                $pdf->setFont('Arial','B',10);
                $pdf->Cell(10);
                $pdf->Cell(45,5,'Amount',0,0,'L');
                $pdf->Cell(4,5,':', 0, 0, 'L');
                $pdf->MultiCell(125,5,number_format($getdata['detail']['jumlah'] ?? 0,2,",","."),0,'L');

                $getyangka = $pdf->GetY();
                $pdf->SetLineWidth(0.3);
                $pdf->setDrawColor(0,0,0);
                $pdf->setTextColor(0,0,0);
                $pdf->setFillColor(255,255,255);
                $pdf->Line(65,$getyangka,190,$getyangka);

                $pdf->Ln();
                $pdf->setFont('Arial','IB',10);
                $pdf->Cell(10);
                $pdf->Cell(45,5,'',0,0,'L');
                $pdf->Cell(4,5,':', 0, 0, 'L');
                $pdf->MultiCell(125,5,terbilang($getdata['detail']['jumlah'] ?? 0),0,'L');

                $getyblng = $pdf->GetY();
                $pdf->SetLineWidth(0.3);
                $pdf->setDrawColor(0,0,0);
                $pdf->setTextColor(0,0,0);
                $pdf->setFillColor(255,255,255);
                $pdf->Line(65,$getyblng,190,$getyblng);

                $pdf->Ln();
                $pdf->setFont('Arial','B',10);
                $pdf->Cell(10);
                // $pdf->Cell(45,5,'Note',0,'L');
                // $pdf->Cell(4,5,':');
                $pdf->setFont('Arial','',10);

                $getybtn = $pdf->GetY();
                $pdf->SetLineWidth(0.3);
                $pdf->setDrawColor(0,0,0);
                $pdf->setTextColor(0,0,0);
                $pdf->setFillColor(255,255,255);
                $pdf->Line(11.5,$getybtn+5,197,$getybtn+5);
                $pdf->Line(11.5,$gety,11.5,$getybtn+5);
                $pdf->Line(197,$gety,197,$getybtn+5);
                
                $pdf->Ln(10);             

                // Pengaturan font untuk teks
                $pdf->setFont('Arial', '', 10);

                // Daftar label dan posisi
                $labels = [
                    'Payment By' => 30,
                    'Receiver By' => 90,
                    'Approved By' => 150
                ];
                
                foreach ($labels as $label => $position) {
                    $pdf->Cell($position);
                    $pdf->Cell(20, 5, $label, 0, 0, 'C');
                    $pdf->Ln(25);
                    $pdf->Cell($position);
                    $pdf->Cell(20, 5, '( _______________ )', 0, 0, 'C');
                    $pdf->Ln(-25); // Kembali ke baris sebelumnya
                }
                
                $pdf->Output();
            }
        }
    }else{
        echo "<meta http-equiv='refresh' content='0;/'>";
    }

?>