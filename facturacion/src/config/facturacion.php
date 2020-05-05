<?php

return [

    /*
    |------------------------------------------------------------------------
    |   Datos cliente
    |------------------------------------------------------------------------
    */

    'cuit'          =>  '20371763644',

    'punto_venta'   =>  '0001',

    'concepto'      =>  '1',#1:productos,2:servicios,#3:productos y servicios

    /*
    |------------------------------------------------------------------------
    |   Credenciales cliente
    |------------------------------------------------------------------------
    */

    'cert'          =>  'certificado.crt',

    'key'           =>  'keystore.key',

    'PASSPHRASE'    =>  "xxxxx",

    /*
    |------------------------------------------------------------------------
    |   URLS AFIP
    |------------------------------------------------------------------------
    */
    
    'homo' => true,


    #########################################################################
    #URLS HOMOLOGACION
    #########################################################################

    'URL_WSAA_HOMO'     =>  "https://wsaahomo.afip.gov.ar/ws/services/LoginCms?wsdl",

    'WSDL_WSAA_HOMO'    =>"https://wsaahomo.afip.gov.ar/ws/services/LoginCms?wsdl",

    #COMP_ULTIMO
    'URL_ULT_HOMO'      =>  "https://wswhomo.afip.gov.ar/wsfev1/service.asmx?op=FECompUltimoAutorizado",
    'WSDL_ULT_HOMO'     =>  "https://wswhomo.afip.gov.ar/wsfev1/service.asmx?WSDL",


    #CAE SOLICITAR
    'URL_CAE_HOMO'      =>  "https://wswhomo.afip.gov.ar/wsfev1/service.asmx?op=FECAESolicitar",
    'WSDL_CAE_HOMO'     =>  "https://wswhomo.afip.gov.ar/wsfev1/service.asmx?WSDL",


    ############################################################################
    #URLS PRODUCCION
    ############################################################################

    #WSAA
    'URL_WSAA'      =>  "https://wsaa.afip.gov.ar/ws/services/LoginCms?wsdl",
    'WSDL_WSAA'     =>  "https://wsaa.afip.gov.ar/ws/services/LoginCms?wsdl",

    #COMP_ULTIMO
    'URL_ULT'       =>  "https://servicios1.afip.gov.ar/wsfev1/service.asmx?op=FECompUltimoAutorizado",
    'WSDL_ULT'      =>  "https://servicios1.afip.gov.ar/wsfev1/service.asmx?WSDL",
    #CAE SOLICITAR
    'URL_CAE'       =>  "https://servicios1.afip.gov.ar/wsfev1/service.asmx?op=FECAESolicitar",
    'WSDL_CAE'      =>  "https://servicios1.afip.gov.ar/wsfev1/service.asmx?WSDL",
];
