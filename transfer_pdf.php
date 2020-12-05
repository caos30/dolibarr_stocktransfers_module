<?php
/* Copyright (C) 2017 Sergi Rodrigues <proyectos@imasdeweb.com>
 *
 * Licensed under the GNU GPL v3 or higher (See file gpl-3.0.html)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/stocktransfers/transfer_pdf.php
 *      \defgroup   stocktransfers Module Stock transfers
 *      \brief      Generation of a PDF delivery note for carrier
 *      \version    v 1.0 2017/11/20
 */

// == ACTIVATE the ERROR reporting
//ini_set('display_errors',1);ini_set('display_startup_errors',1);error_reporting(-1);


$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res && file_exists("../../../../main.inc.php")) $res=@include("../../../../main.inc.php");
if (! $res && preg_match('/\/imasdeweb([^\/]*)\//',$_SERVER["PHP_SELF"],$reg)) $res=@include("../../../dolibarr".$reg[1]."/htdocs/main.inc.php"); // Used on dev env only
if (! $res) die("Include of main fails");

// == STOCKTRANSFERS_MODULE DOCUMENT_ROOT & URL_ROOT
    if (file_exists(DOL_DOCUMENT_ROOT.'/custom/stocktransfers/core/modules/modStocktransfers.class.php')){
        define('STOCKTRANSFERS_MODULE_DOCUMENT_ROOT',DOL_DOCUMENT_ROOT.'/custom/stocktransfers');
        define('STOCKTRANSFERS_MODULE_URL_ROOT',DOL_URL_ROOT.'/custom/stocktransfers');
    }else{
        define('STOCKTRANSFERS_MODULE_DOCUMENT_ROOT',DOL_DOCUMENT_ROOT.'/stocktransfers');
        define('STOCKTRANSFERS_MODULE_URL_ROOT',DOL_URL_ROOT.'/stocktransfers');
    }

require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once STOCKTRANSFERS_MODULE_DOCUMENT_ROOT.'/lib/stocktransfers_transfer.class.php';

if (!empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}

$langs->load("products");
$langs->load("stocks");
$langs->load("orders");
$langs->load("productbatch");
$langs->load("projects");
$langs->load("stocktransfers");

include_once("./lib/stocktransfers.lib.php");
include_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");

$user->getrights('stocktransfers');
$langs->load("stocktransfers@stocktransfers");

// == Security check
    $result=restrictedArea($user,'produit|service');

// == Get parameters
    $transfer_id = GETPOST('id', '0');

// == data object
    global $transfer;
    $transfer = new StockTransfer($db);
    if ($transfer_id > 0) {
        $ret = $transfer->fetch($transfer_id);
    }

// == some style settings
    $fontsize = !empty($conf->global->STOCKTRANSFERS_MODULE_SETT_10) ? intval($conf->global->STOCKTRANSFERS_MODULE_SETT_10) : 10;
    $fontfamily = !empty($conf->global->STOCKTRANSFERS_MODULE_SETT_11) ? $conf->global->STOCKTRANSFERS_MODULE_SETT_11 : 'serif';

/***************************************************
 *
 *	Generate and output PDF
 *
****************************************************/

// == load PDF class
    require_once TCPDF_PATH.'tcpdf.php';

// == Extend the TCPDF class to create custom Footer
    class MYPDF extends TCPDF {

        public function Footer() {
            global $conf;
            $fontsize = !empty($conf->global->STOCKTRANSFERS_MODULE_SETT_10) ? intval($conf->global->STOCKTRANSFERS_MODULE_SETT_10) : 10;
            $fontfamily = !empty($conf->global->STOCKTRANSFERS_MODULE_SETT_11) ? $conf->global->STOCKTRANSFERS_MODULE_SETT_11 : 'serif';
            $text = !empty($conf->global->STOCKTRANSFERS_MODULE_SETT_12) ? $conf->global->STOCKTRANSFERS_MODULE_SETT_12 : '';

            $this->SetY(-25);
            $footer_text = "<span style=\"font-family:$fontfamily;font-size:".($fontsize - 1)."px;\">".nl2br($text)."</div>";
            $this->writeHTMLCell(0, 0, '', '', $footer_text, 0, 0, 0, true, 'C', false);
        }
    }

// == create new PDF document
    $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    $pdf->setJPEGQuality(75);

// == set document information
    $pdf->SetCreator('Dolibarr');
    $pdf->SetAuthor('User name');
    $pdf->SetTitle('Stock Transfer Report');
    $pdf->SetSubject('Stock Transfer');
    $pdf->SetKeywords('Stock Transfer');

// == set default header data
    //$pdf->SetHeaderData('../../../../UserFiles/admin/modulo_'.$modulo.'/fondo_bitllet.jpg', 180, 'Bitllet per a  '.$nombre.' - Colònia: '.$evento['titulo'], '');
    //$pdf->SetHeaderData('', 180, $title, '');
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(true);

// == set header and footer fonts
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// == set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// == set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP / 1.5, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// == set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// == set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// == set some language-dependent strings
    //$pdf->setLanguageArray($l);
    $pdf->setLanguageArray('es');

// == set font
    $pdf->SetFont('courier', '', 10);

// == add a page
    $pdf->AddPage();

// == calculate margins
    $wp=185;
    $hp=250;
    $xm=15;	$ym=25;

// == add Image
    /*
    $img_file = $config_site['path_s'].'/UserFiles/admin/modulo_registrations/pdf_voucher_card_background.jpg';
    if (file_exists($img_file)){
        $dimensions = _get_proportional_dimensions(array('uri_source'=>$img_file, 'max_width'=>$wp, 'max_height'=>240));
        $pdf->Image($img_file, $xm, $ym, $dimensions[0], $dimensions[1] , '', 'http'.$config_site['HTTPS'].'://'.$config_site['dominio_s'], '', true, 150);
    }
    */

// == HTML body of the PDF
    $html = _render_view('pdf',array('transfer'=>$transfer,'db'=>$db, 'langs'=>$langs, 'mysoc'=>$mysoc));
    //echo $html; die();
    $pdf->writeHTML($html, true, false, true, false, '');

// == reset pointer to the last page
    $pdf->lastPage();

// ---------------------------------------------------------

// == Close and output PDF document
    $title = $langs->trans('STFilename').'-'.$transfer->date1.'_'.substr('0000'.$transfer->rowid,-4);
    $file = urlencode($title).'.pdf';
    $pdf->Output($file, 'I');

//============================================================+
// END OF FILE
//============================================================+
