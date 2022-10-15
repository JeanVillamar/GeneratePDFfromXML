<?php
include "extraeDatos.php";
//require "fpdf/fpdf.php";
require'code128.php';

$pdf = new PDF_Code128($orientation='P',$unit='mm');
$pdf->AddPage();
$pdf->Image('./logo.png',150,20,45,35,'PNG');
$pdf->SetFont('Arial','B',17);    
$pdf->setY(12);
$pdf->setX(10);
// Agregamos los datos de la empresa
$pdf->Cell(5,5,$razonsocial,0,0,'L');
$pdf->SetFont('Arial','',10);    
$pdf->setY(60);$pdf->setX(125);
$pdf->Multicell(80,6,$razonsocial."\n"."DIR MATRIZ: ".$dirEmpresa."\n".
                "CONTRIBUYENTE ESPECIAL NRO: ".$contribuyenteEspecial."\n".
                "OBLIGADO A LLEVAR CONTABLIDAD: ".$obligadoContabilidad."\n",1);

$tempPosY = $pdf->GetY();

$pdf->setY(25);$pdf->setX(10);
$pdf->Multicell(110, 6, utf8_decode("RUC: ".$ruc."\n".
                                    "FACTURA Nº: ".$nFactura."\n".
                                    "NO. DE AUTORIZACIÓN: ".$numeroAutorizacion."\n".
                                    "FECHA Y HORA DE AUTORIZACIÓN: ".$fechaAutorizacion."\n".
                                    "CLAVE DE ACCESO:".str_repeat("\n", 5).
                                    "   ".$numeroAutorizacion."\n"),1,"L");
// codigo de barras con Cod128
$pdf->Code128(13,62,$numeroAutorizacion,100,20);

$tempPosY1 = $pdf->GetY();
if($tempPosY1 >$tempPosY){
  $pdf->setY($tempPosY1);
}
else{
  $pdf->setY($tempPosY);
}

$pdf->Ln(6);
//agregamos datos de cliente 
$pdf->Multicell(195, 6, utf8_decode("RAZÓN SOCIAL / NOMBRES Y APELLIDOS: ".$razonSocialComprador."\n".
                                    "RUC/CI: ".$identificacionComprador."\n".
                                    "FECHA DE EMISIÓN: ".$fechaEmision."\n"),1,"L");

$pdf->Ln();
//tabla de productos 
$header = array("Cod Principal", "Cant","Descripción","Precio unitario","Descuento","Precio total");
// Column widths
$w = array(26,12, 94, 23, 20, 20);
$pdf->SetFont('Arial','',9); 
// Header
for($i=0;$i<count($header);$i++)
    $pdf->Cell($w[$i],6,utf8_decode($header[$i]),1,0,'C');
$pdf->Ln();
//datos productos 
$pdf->SetFont('Arial','',8); 
foreach($detallesProducto as $codProducto => $detalles){
  $pdf->Cell($w[0],6,$codProducto,1,0,'C',);//codigoPrincipal
  $pdf->Cell($w[1],6,$detalles[0],1,0,'R',);//cantidad
  $pdf->Cell($w[2],6,utf8_decode($detalles[1]),1,0,'L',);//descripcion
  $pdf->SetFillColor(255,255,255);
  $pdf->Cell($w[3],6,$detalles[2],1,0,'R',1);//precioUnitario
  $pdf->SetFillColor(255,255,255);
  $pdf->Cell($w[4],6,$detalles[3],1,0,'R',1);//descuento
  $pdf->SetFillColor(255,255,255);
  $pdf->Cell($w[5],6,$detalles[4],1,0,"R",1);//precioTotalSinImpuesto
  $pdf->Ln();
}
// tabla con los subtotales y totales
$pdf->SetFont('Arial','',9); 
$pdf->Ln(6);
$tempPosX = $pdf->GetX();
$tempPosY = $pdf->GetY();
/////////////////////////////
$header = array("", "");
$datosPago = array(
	array("SUBTOTAL 0%:",$baseImponible),
	array("SUBTOTAL 12%:", $baseImponibleIva),
	array("SUBTOTAL NO SUJETO DE IVA:", $valorDevolucionIva),
  array("SUBTOTAL SIN IMPUESTOS", $totalSinImpuestos),
  array("DESCUENTO:", $totalDescuento),
  array("IVA 12%:", $iva),
	array("PROPINA:", $propina),
  array("VALOR TOTAL:", $valorTotal),
);
// Column widths
$w2 = array(55, 25);

// datos de subtotales y totales 
foreach($datosPago as $row){
  $pdf->setX(125);
  $pdf->Cell($w2[0],6,$row[0],1,0,'R');
  $pdf->Cell($w2[1],6,"$ ".number_format($row[1], 2, ".",","),'1',0,'R');
  $pdf->Ln();
}

// informacion adicional, 
$InfoAdicionalStr = utf8_decode("INFORMACIÓN ADICIONAL\n");
foreach($infoAdicional as $detalle =>$valor){
  $InfoAdicionalStr.=utf8_decode($detalle).": ".utf8_decode($valor)."\n";
}

$pdf->setX($tempPosX); $pdf->setY($tempPosY);
$pdf->Multicell(110,6,utf8_decode($InfoAdicionalStr),1,'L');

//forma de pago
$pdf->Ln(5);
$header = array("Forma de Pago", "Valor");
$data2 = array(
	array($formaPago,$valorTotal)
);

$w2 = array(90, 20);
for($i=0;$i<count($header);$i++)
    $pdf->Cell($w2[$i],6,utf8_decode($header[$i]),1,0,'C');
$pdf->Ln();
foreach($data2 as $row){
  $pdf->setX(10);
  $pdf->Cell($w2[0],6,utf8_decode($row[0]),1);
  $pdf->Cell($w2[1],6,"$ ".number_format($row[1], 2, ".",","),'1',0,'R');

  $pdf->Ln();
}

echo $pdf->output();//muestra pdf
$pdf->output('temp.pdf','F');//genra un archivo pdf en la carpeta actual
?>