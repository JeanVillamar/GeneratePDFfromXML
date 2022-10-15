<?php
//$xml = file_get_contents('1409202201179001691900121481100003115050337010614.xml');
//$xml = file_get_contents('./facturasXML/Factura - 2022-09-20T114404.107.xml');
$xml = file_get_contents('facturas/Factura (44).xml');

$xml_content = simplexml_load_string($xml);
$xml_content = (array) $xml_content;

// xml que tiene datos de factura 
$xml_comprobante = simplexml_load_string($xml_content["comprobante"]);

// datos de importancia
$numeroAutorizacion = $xml_content["numeroAutorizacion"];//clave acceso
$fechaAutorizacion = $xml_content["fechaAutorizacion"];
// cambia formato de fecha de: 
date_default_timezone_set('America/Bogota');
$fechaAutorizacion= date("Y-m-d H:i:s", strtotime($fechaAutorizacion));
$razonsocial = (string) utf8_decode($xml_comprobante->infoTributaria->razonSocial);
$ruc = (string) $xml_comprobante->infoTributaria->ruc;
$dirEmpresa = (string) utf8_decode($xml_comprobante->infoTributaria->dirMatriz) ;
$contribuyenteEspecial = (string) $xml_comprobante->infoFactura->contribuyenteEspecial ;
$obligadoContabilidad = (string) $xml_comprobante->infoFactura->obligadoContabilidad ;
$nFactura = (string) $xml_comprobante->infoTributaria->estab;
$nFactura .= "-".(string) $xml_comprobante->infoTributaria->ptoEmi;
$nFactura .= "-".(string) $xml_comprobante->infoTributaria->secuencial;

//datos cliente 
$razonSocialComprador = (string) $xml_comprobante->infoFactura->razonSocialComprador;
$identificacionComprador = (string) $xml_comprobante->infoFactura->identificacionComprador;
$fechaEmision = (string) $xml_comprobante->infoFactura->fechaEmision;

//totales y subtotales 
$arraySubtotales = (array)$xml_comprobante->infoFactura->totalConImpuestos;
$arraySubtotales = $arraySubtotales["totalImpuesto"];
//$baseImponible = subtotal 0%
//$baseImponibleIva =  subtotal 12%

list($baseImponible, $baseImponibleIva, $iva, $valorDevolucionIva) = extraeSubtotales($arraySubtotales);
//SUBTOTAL NO SUJETO DE IVA
$propina = (float)$xml_comprobante->infoFactura->propina;
$totalSinImpuestos = (float)$xml_comprobante->infoFactura->totalSinImpuestos; // Subtotal sin impuesto 
$valorTotal = (float)$xml_comprobante->infoFactura->importeTotal; // con impuesto y todo 
$totalDescuento = (float)$xml_comprobante->infoFactura->totalDescuento;
$formaPago = (string)$xml_comprobante->infoFactura->pagos->pago->formaPago;
validaFormaPago($formaPago);

// informacion adicional 
$infoAdicional = array();

foreach ( $xml_comprobante->infoAdicional->children() as $child ) {
  $infoAdicional[(string)$child["nombre"]] = utf8_decode((string)$child);
}
// detalles de productos
$detallesProducto = array();
//codigoPrincipal, cantidad, descripcion, precioUnitario, descuento, precioTotalSinImpuesto
foreach ( $xml_comprobante->detalles->detalle as $detalle ) {
  $detallesProducto[(string)$detalle->codigoPrincipal] = array((string)$detalle->cantidad, (string)$detalle->descripcion,    (string)$detalle->precioUnitario, (string)$detalle->descuento, (string)$detalle->precioTotalSinImpuesto);
}

/* la funcion recibe un array con las bases imponibles de los xml, en ocaciones trae dos o solo una base imponibles desde el xml. la funcion procesa y debuelve valores correctos segun que valores nos da el xml.
ouputs: [$baseImponible, $baseImponibleIva, $iva, $valorDevolucionIva]
*/
function extraeSubtotales(&$arraySubtotales) {
  $baseImponible = 0;
  $baseImponibleIva = 0;
  $iva = 0;
  $valorDevolucionIva = 0;
  // si trae dos bases imponibles, es un array con dos XMLElement
  if(count($arraySubtotales) == 2) {
    $xml0 = $arraySubtotales[0];
    $xml1 = $arraySubtotales[1];
    if ((float)$xml0->valor != 0){
      $baseImponibleIva = (float)$xml0->baseImponible;
      $iva = (float)$xml0->valor;// si el attr valor != 0 es el iva
      $valorDevolucionIva = (float)$xml0->valorDevolucionIva;
      $baseImponible = (float)$xml1->baseImponible;
    }
    if ((float)$xml0->valor == 0){ // si valor es 0, el otro xmlElement trae el valor(iva)
      $baseImponibleIva = (float)$xml1->baseImponible;
      $iva = (float)$xml1->valor;
      $valorDevolucionIva = (float)$xml1->valorDevolucionIva;
      $baseImponible = (float)$xml0->baseImponible;
    }
  }
  
  // si solo trae una base, es un XMLElement
  else{
    $baseImponible = 0.0;
    $baseImponibleIva = (float)$arraySubtotales->baseImponible;
    $iva =  (float)$arraySubtotales->valor;
    $valorDevolucionIva = (float)$arraySubtotales->valorDevolucionIva;
  }
  
  return [$baseImponible, $baseImponibleIva, $iva, $valorDevolucionIva];
}

/* pasa la forma de pago de int a string*/
function validaFormaPago(&$formaPago) {
  switch ($formaPago) {
  case "01":
    $formaPago = utf8_decode("SIN UTILIZACIÓN DEL SISTEMA FINANCIERO");
    break;
  case "15":
    $formaPago = utf8_decode("COMPENSACIÓN DE DEUDAS");
    break;
  case "16":
    $formaPago = utf8_decode("TARJETA DE DÉBITO");
    break;
    case "17":
    $formaPago = utf8_decode("DINERO ELECTRÓNICO");
    break;
  case "18":
    $formaPago = "TARJETA PREPAGO";
    break;
  case "19":
    $formaPago = utf8_decode("TARJETA DE CRÉDITO");
    break;
  case "20":
    $formaPago = utf8_decode("OTROS CON UTILIZACIÓN DEL SISTEMA FINANCIERO");
    break;
  case "21":
    $formaPago = utf8_decode("ENDOSO DE TÍTULOS");
    break;
  }
}

?>