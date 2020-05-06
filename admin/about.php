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
 *	\file       htdocs/stocktransfers/admin/about.php
 *      \defgroup   stocktransfers Module Stock transfers
 *      \brief      About page
 *      \version    v 1.0 2017/11/20
 */

define('NOCSRFCHECK',1);

$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res && file_exists("../../../../main.inc.php")) $res=@include("../../../../main.inc.php");
if (! $res && preg_match('/\/imasdeweb([^\/]*)\//',$_SERVER["PHP_SELF"],$reg)) $res=@include("../../../../dolibarr".$reg[1]."/htdocs/main.inc.php"); // Used on dev env only
if (! $res) die("Include of main fails");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");

// == STOCKTRANSFERS_MODULE DOCUMENT_ROOT & URL_ROOT
    if (file_exists(DOL_DOCUMENT_ROOT.'/custom/stocktransfers/core/modules/modStocktransfers.class.php')){
        define('STOCKTRANSFERS_MODULE_DOCUMENT_ROOT',DOL_DOCUMENT_ROOT.'/custom/stocktransfers');
        define('STOCKTRANSFERS_MODULE_URL_ROOT',DOL_URL_ROOT.'/custom/stocktransfers');
    }else{
        define('STOCKTRANSFERS_MODULE_DOCUMENT_ROOT',DOL_DOCUMENT_ROOT.'/stocktransfers');
        define('STOCKTRANSFERS_MODULE_URL_ROOT',DOL_URL_ROOT.'/stocktransfers');
    }

if (!$user->admin) accessforbidden();


$langs->load("admin");
$langs->load("other");
$langs->load("stocktransfers");


/**
 * View
 */

$help_url='';
llxHeader('',$langs->trans('stocktransfersMenuTitle2').' :: '.$langs->trans('STtabAbout'),$help_url);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("stocktransfersSetup"),$linkback,'setup');
print '<br>';

$h=0;

$head[$h][0] = 'config.php';
$head[$h][1] = $langs->trans("STtabConfig");
$head[$h][2] = 'tabconfig';
$h++;

$head[$h][0] = 'about.php';
$head[$h][1] = $langs->trans("STtabAbout");
$head[$h][2] = 'tababout';
$h++;

$search_query = 'bimex';

dol_fiche_head($head, 'tababout', $langs->trans('stocktransfersMenuTitle2'),-1,'stock');

print "<div style='padding:1em 2em;'>";
print $langs->trans("stocktransfersAboutInfo").'<br>';
print '<br>';

print $langs->trans("stocktransfersMoreModules",$search_query).'<br>';
print '&nbsp; &nbsp; &nbsp; '.$langs->trans("stocktransfersMoreModulesLink",$search_query).'<br>';
print '<a href="http://www.dolistore.com/search.php?search_query='.$search_query.'" target="_blank"><img border="0" width="180" src="'.DOL_URL_ROOT.'/theme/dolistore_logo.png"></a><br><br><br>';

print '<br>';
print "</div>";

dol_fiche_end();


llxFooter();

$db->close();
