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
 *		\file       htdocs/stocktransfers/admin/config.php
 *      \defgroup   stocktransfers Module Stock transfers
 *      \brief      Settings page
 *      \version    v 1.0 2017/11/20
 */

// == ACTIVATE the ERROR reporting
ini_set('display_errors',1);ini_set('display_startup_errors',1);error_reporting(-1);

define('NOCSRFCHECK',1);

$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res && file_exists("../../../../main.inc.php")) $res=@include("../../../../main.inc.php");
if (! $res && preg_match('/\/imasdeweb([^\/]*)\//',$_SERVER["PHP_SELF"],$reg)) $res=@include("../../../../dolibarr".$reg[1]."/htdocs/main.inc.php"); // Used on dev env only
if (! $res) die("Include of main fails");

// == STOCKTRANSFERS_MODULE DOCUMENT_ROOT & URL_ROOT
    if (file_exists(DOL_DOCUMENT_ROOT.'/custom/stocktransfers/core/modules/modStocktransfers.class.php')){
        define('STOCKTRANSFERS_MODULE_DOCUMENT_ROOT',DOL_DOCUMENT_ROOT.'/custom/stocktransfers');
        define('STOCKTRANSFERS_MODULE_URL_ROOT',DOL_URL_ROOT.'/custom/stocktransfers');
    }else{
        define('STOCKTRANSFERS_MODULE_DOCUMENT_ROOT',DOL_DOCUMENT_ROOT.'/stocktransfers');
        define('STOCKTRANSFERS_MODULE_URL_ROOT',DOL_URL_ROOT.'/stocktransfers');
    }

dol_include_once("core/lib/admin.lib.php");
dol_include_once("core/class/html.formadmin.class.php");

if (!$user->admin) accessforbidden();

$langs->load("admin");
$langs->load("other");
$langs->load("stocktransfers");

/***************************************************
 *
 *	Actions / prepare data
 *
****************************************************/

    // == request action by GET/POST

        if (!empty($_POST['config'])){

            //echo var_export($_POST,true);die();

            /* save incoming data */
                $db->begin();
                $error = 0;
                foreach ($_POST['config'] as $K => $v){
                    //$result = dolibarr_set_const($db, "STRIPE_TEST_PUBLISHABLE_KEY", GETPOST('STRIPE_TEST_PUBLISHABLE_KEY', 'alpha'), 'chaine', 0, '', $conf->entity);
                    $result = dolibarr_set_const($db, $K, $v, 'chaine', 0, '', $conf->entity);
            		if (! $result > 0) $error ++;
                }

            /* message to user */
            	if (! $error) {
            		$db->commit();
            		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
            	} else {
            		$db->rollback();
            		dol_print_error($db);
            	}
        }

/***************************************************
 *
 *	View
 *
****************************************************/

$help_url='';
llxHeader('',$langs->trans('stocktransfersMenuTitle2').' :: '.$langs->trans('STtabConfig'),$help_url);

// = first header row (section title & go back link)

    $linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
    print_fiche_titre($langs->trans("stocktransfersSetup"),$linkback,'setup');

// = tabs of the section

    $h=0;

    $head[$h][0] = 'config.php';
    $head[$h][1] = $langs->trans("STtabConfig");
    $head[$h][2] = 'tabconfig';
    $h++;

    $head[$h][0] = 'about.php';
    $head[$h][1] = $langs->trans("STtabAbout");
    $head[$h][2] = 'tababout';
    $h++;

// = init current tab

    $html = new Form($db);

    dol_fiche_head($head, 'tabconfig', $langs->trans('stocktransfersMenuTitle2'),-1,'stock');

    print "\n<div style='padding:0 1.5em;'>";
?>

<form id="stocktransfersForm" name="stocktransfersForm" action="<?= $_SERVER["PHP_SELF"] ?>" method="post">

    <!-- ** PDF GENERAL ** SETTINGS -->

    <?= load_fiche_titre($langs->trans("STsettTit00"),'','') ?>

    <table class="noborder" style="width:auto;min-width:60%;">
        <tr class="liste_titre">
            <td width="35%"><?= $langs->trans("Name") ?></td>
            <td width="65%"><?= $langs->trans("Value") ?></td>
        </tr>
        <!-- base font-size -->
        <tr>
            <td><?= $langs->trans("STsettLab10") ?></td>
            <td>
                <?php $value = !empty($conf->global->STOCKTRANSFERS_MODULE_SETT_10) ? $conf->global->STOCKTRANSFERS_MODULE_SETT_10 : '10' ?>
                <select name="config[STOCKTRANSFERS_MODULE_SETT_10]">
                    <option value="7" <?= $value=='7' ? "selected='selected'":"" ?>>7 px</option>
                    <option value="8" <?= $value=='8' ? "selected='selected'":"" ?>>8 px</option>
                    <option value="9" <?= $value=='9' ? "selected='selected'":"" ?>>9 px</option>
                    <option value="10" <?= $value=='10' ? "selected='selected'":"" ?>>10 px</option>
                    <option value="11" <?= $value=='11' ? "selected='selected'":"" ?>>11 px</option>
                    <option value="12" <?= $value=='12' ? "selected='selected'":"" ?>>12 px</option>
                    <option value="13" <?= $value=='13' ? "selected='selected'":"" ?>>13 px</option>
                    <option value="14" <?= $value=='14' ? "selected='selected'":"" ?>>14 px</option>
                </select>
            </td>
        </tr>
        <!-- font-family -->
        <tr>
            <td><?= $langs->trans("STsettLab11") ?></td>
            <td>
                <?php $value = !empty($conf->global->STOCKTRANSFERS_MODULE_SETT_11) ? $conf->global->STOCKTRANSFERS_MODULE_SETT_11 : 'sans-serif' ?>
                <select name="config[STOCKTRANSFERS_MODULE_SETT_11]">
                    <option value="sans-serif" <?= $value=='sans-serif' ? "selected='selected'":"" ?>>sans-serif</option>
                    <option value="serif" <?= $value=='serif' ? "selected='selected'":"" ?>>serif</option>
                </select>
            </td>
        </tr>
    </table>

    <!-- ** PDF HEADER ** SETTINGS -->

    <?= load_fiche_titre($langs->trans("STsettTit01"),'','') ?>

    <table class="noborder" style="width:auto;min-width:60%;">
        <tr class="liste_titre">
            <td width="35%"><?= $langs->trans("Name") ?></td>
            <td width="65%"><?= $langs->trans("Value") ?></td>
        </tr>
        <!-- document title -->
        <tr>
            <td><?= $langs->trans("STsettLab01") ?></td>
            <td>
                <input name="config[STOCKTRANSFERS_MODULE_SETT_01]" type="text" class=""
                    value="<?= $conf->global->STOCKTRANSFERS_MODULE_SETT_01 ?>"
                    placeholder="<?= htmlentities($langs->trans("STsettExampleAbbrv").' '.$langs->trans("STsettLab01def")) ?>" />
                &nbsp; <em><?= $langs->trans("STsettEmpty") ?></em>
            </td>
        </tr>
        <!-- label for Reference -->
        <tr>
            <td><b><?= $langs->trans("STsettLab02") ?></b></td>
            <td>
                <input name="config[STOCKTRANSFERS_MODULE_SETT_02]" type="text" class="fieldrequired"
                    value="<?= !empty($conf->global->STOCKTRANSFERS_MODULE_SETT_02) ? $conf->global->STOCKTRANSFERS_MODULE_SETT_02 : $langs->trans('stocktransfersPDF1') ?>"
                    placeholder="<?= htmlentities($langs->trans("STsettExampleAbbrv").' '.$langs->trans("STsettLab02def")) ?>" />
            </td>
        </tr>
        <!-- show the shipper name -->
        <tr>
            <td><?= $langs->trans("STsettLab03") ?></td>
            <td>
                <?php $value = !empty($conf->global->STOCKTRANSFERS_MODULE_SETT_03) ? $conf->global->STOCKTRANSFERS_MODULE_SETT_03 : 'M' ?>
                <select name="config[STOCKTRANSFERS_MODULE_SETT_03]">
                    <option value="N" <?= $value=='N' ? "selected='selected'":"" ?>><?= $langs->trans('STno') ?></option>
                    <option value="Y" <?= $value=='Y' ? "selected='selected'":"" ?>><?= $langs->trans('STyes') ?></option>
                    <option value="M" <?= $value=='M' ? "selected='selected'":"" ?>><?= strip_tags($langs->trans("STsettLab03opt3")) ?></option>
                </select>
            </td>
        </tr>
        <!-- show the number of packages -->
        <tr>
            <td><?= $langs->trans("STsettLab04") ?></td>
            <td>
                <?php $value = !empty($conf->global->STOCKTRANSFERS_MODULE_SETT_04) ? $conf->global->STOCKTRANSFERS_MODULE_SETT_04 : 'M' ?>
                <select name="config[STOCKTRANSFERS_MODULE_SETT_04]">
                    <option value="N" <?= $value=='N' ? "selected='selected'":"" ?>><?= $langs->trans('STno') ?></option>
                    <option value="Y" <?= $value=='Y' ? "selected='selected'":"" ?>><?= $langs->trans('STyes') ?></option>
                    <option value="M" <?= $value=='M' ? "selected='selected'":"" ?>><?= strip_tags($langs->trans("STsettLab03opt3")) ?></option>
                </select>
            </td>
        </tr>
        <!-- field to name the warehouse -->
        <tr>
            <td><?= $langs->trans("STsettLab07") ?></td>
            <td>
                <?php $value = !empty($conf->global->STOCKTRANSFERS_MODULE_SETT_07) ? $conf->global->STOCKTRANSFERS_MODULE_SETT_07 : 'L' ?>
                <select name="config[STOCKTRANSFERS_MODULE_SETT_07]">
                    <option value="L" <?= $value=='L' ? "selected='selected'":"" ?>><?= strip_tags($langs->trans("STsettLab07opt1")) ?></option>
                    <option value="R" <?= $value=='R' ? "selected='selected'":"" ?>><?= strip_tags($langs->trans("STsettLab07opt2")) ?></option>
                </select>
            </td>
        </tr>
        <!-- position of warehouse's boxes -->
        <tr>
            <td><?= $langs->trans("STsettLab13") ?></td>
            <td>
                <?php $value = !empty($conf->global->STOCKTRANSFERS_MODULE_SETT_13) ? $conf->global->STOCKTRANSFERS_MODULE_SETT_13 : 'A-B' ?>
                <select name="config[STOCKTRANSFERS_MODULE_SETT_13]">
                    <option value="A-B" <?= $value=='A-B' ? "selected='selected'":"" ?>><?= strip_tags($langs->trans("STsettLab13opt1")) ?></option>
                    <option value="B-A" <?= $value=='B-A' ? "selected='selected'":"" ?>><?= strip_tags($langs->trans("STsettLab13opt2")) ?></option>
                </select>
            </td>
        </tr>
    </table>

    <!-- ** PDF PODUCT LIST ** SETTINGS -->

    <br /><?= load_fiche_titre($langs->trans("STsettTit02"),'','') ?>

    <table class="noborder" style="width:auto;min-width:60%;">
        <tr class="liste_titre">
            <td width="35%"><?= $langs->trans("Name") ?></td>
            <td width="65%"><?= $langs->trans("Value") ?></td>
        </tr>
        <!-- show price -->
        <tr>
            <td><?= $langs->trans("STsettLab05") ?></td>
            <td>
                <?php $value = !empty($conf->global->STOCKTRANSFERS_MODULE_SETT_05) ? $conf->global->STOCKTRANSFERS_MODULE_SETT_05 : 'N' ?>
                <select name="config[STOCKTRANSFERS_MODULE_SETT_05]">
                    <option value="N" <?= $value=='N' ? "selected='selected'":"" ?>><?= $langs->trans('STno') ?></option>
                    <option value="Y" <?= $value=='Y' ? "selected='selected'":"" ?>><?= strip_tags($langs->trans("STsettLab05opt2")) ?></option>
                    <option value="T" <?= $value=='T' ? "selected='selected'":"" ?>><?= strip_tags($langs->trans("STsettLab05opt3")) ?></option>
                </select>
            </td>
        </tr>
        <!-- show num. part / serial code -->
        <tr>
            <td><?= $langs->trans("STsettLab08") ?></td>
            <td>
                <?php $value = !empty($conf->global->STOCKTRANSFERS_MODULE_SETT_08) ? $conf->global->STOCKTRANSFERS_MODULE_SETT_08 : 'M' ?>
                <select name="config[STOCKTRANSFERS_MODULE_SETT_08]">
                    <option value="N" <?= $value=='N' ? "selected='selected'":"" ?>><?= $langs->trans('STno') ?></option>
                    <option value="Y" <?= $value=='Y' ? "selected='selected'":"" ?>><?= $langs->trans('STyes') ?></option>
                    <option value="M" <?= $value=='M' ? "selected='selected'":"" ?>><?= strip_tags($langs->trans("STsettLab03opt3")) ?></option>
                </select>
            </td>
        </tr>
        <!-- show barcode -->
        <tr>
            <td><?= $langs->trans("STsettLab09") ?></td>
            <td>
                <?php $value = !empty($conf->global->STOCKTRANSFERS_MODULE_SETT_09) ? $conf->global->STOCKTRANSFERS_MODULE_SETT_09 : 'M' ?>
                <select name="config[STOCKTRANSFERS_MODULE_SETT_09]">
                    <option value="N" <?= $value=='N' ? "selected='selected'":"" ?>><?= $langs->trans('STno') ?></option>
                    <option value="Y" <?= $value=='Y' ? "selected='selected'":"" ?>><?= $langs->trans('STyes') ?></option>
                    <option value="M" <?= $value=='M' ? "selected='selected'":"" ?>><?= strip_tags($langs->trans("STsettLab03opt3")) ?></option>
                </select>
            </td>
        </tr>
    </table>

    <!-- ** PDF FOOTER ** SETTINGS -->

    <br /><?= load_fiche_titre($langs->trans("STsettTit03"),'','') ?>

    <table class="noborder" style="width:auto;min-width:60%;">
        <tr class="liste_titre">
            <td width="35%"><?= $langs->trans("Name") ?></td>
            <td width="65%"><?= $langs->trans("Value") ?></td>
        </tr>
        <!-- show signatures -->
        <?php for ($ii=1;$ii<4;$ii++){ ?>
        <tr>
            <td><?= $langs->trans("STsettLab06".$ii) ?></td>
            <td>
                <input name="config[STOCKTRANSFERS_MODULE_SETT_06<?= $ii ?>]" type="text" class=""
                    value="<?= $conf->global->{'STOCKTRANSFERS_MODULE_SETT_06'.$ii} ?>"
                    placeholder="<?= htmlentities($langs->trans("STsettExampleAbbrv").' '.$langs->trans("stocktransfersPDF".($ii+7))) ?>" />
                &nbsp; <em><?= $langs->trans("STsettEmpty") ?></em>
            </td>
        </tr>
        <?php } ?>
        <!-- 3 line page footer -->
        <tr>
            <td><?= $langs->trans("STsettLab12") ?></td>
            <td>
                <textarea name="config[STOCKTRANSFERS_MODULE_SETT_12]" class="" style="width:95%;height:4em;"
                    ><?= isset($conf->global->STOCKTRANSFERS_MODULE_SETT_12) ? $conf->global->STOCKTRANSFERS_MODULE_SETT_12 : '' ?></textarea>
                <br />&nbsp; <em><?= $langs->trans("STsettLab12desc") ?></em>
            </td>
        </tr>

    </table>

    <!-- SUBMIT button -->

    <p style="text-align:left;margin:3rem 0;">
        <a href="#" onclick="$('#stocktransfersForm').submit();return false;" class="button"><?= dol_escape_htmltag($langs->trans("Save")) ?></a>
    </p>

    <!-- NOTES & ALERTS -->

    <p><br /><br /></p>
    <table class="noborder">
        <tr>
            <td>
                <?= $langs->trans('stocktransfersConfig01') ?>
            </td>
        </tr>
    </table>

    <!-- MODULE VERSION & USER GUIDE LINK -->
    <?php
        require_once STOCKTRANSFERS_MODULE_DOCUMENT_ROOT.'/core/modules/modStocktransfers.class.php';
        $module = new modStockTransfers($db);
        $user_lang = substr($langs->defaultlang,0,2);
    ?>
    <div style="margin: 2rem 0;color: #ccc;display: inline-block;border-top: 1px #ccc solid;border-bottom: 1px #ccc solid;background-color: rgba(0,0,0,0.05);padding: 0.5rem;">
        <span class="help">Stock transfers <?= $module->version ?>
           &nbsp; | &nbsp; <a href="https://imasdeweb.com/index.php?pag=m_blog&gad=detalle_entrada&entry=<?= $user_lang == 'es' ? '38':'39'?>" target="_blank"><?= $langs->trans('stocktransfersUserGuide') ?></a>
        </span>
    </div>

    <script>
        $(document).ready(function(){
            $('#stocktransfersForm').bind('submit',function(){
                var msg = js_validate_form('stocktransfersForm');
                if (msg!=""){
                    alert(msg);
                    return false;
                }
            });
        });

        function js_validate_form(form_id){

            /* prepare */
                var all_fine = true, fine = true, control, c_val, c_name, c_id;
                $(control).removeClass('alertedfield');
                $('#'+form_id+' tr').removeClass('alertedcontainer');

            /* check required fields */
                $('#'+form_id+' .fieldrequired').each(function(){
                    /* = input fields = */
                        control = $(this).closest('tr').find('input');
                        c_val = $(control).val();
                        c_name = $(control).attr('name');
                        c_id = $(control).attr('id');
                        if (c_name!=undefined){
                            if (c_val=='') fine = false;
                            if (!fine){
                                all_fine = false;
                                $(control).addClass('alertedfield');
                                $(control).closest('tr').addClass('alertedcontainer');
                            }
                        }
                    /* = select fields = */
                        control = $(this).closest('tr').find('select');
                        c_val = $(control).val();
                        c_name = $(control).attr('name');
                        c_id = $(control).attr('id');
                        if (c_name!=undefined){
                            if (c_val=='' || c_val=='-1') fine = false;
                            if (!fine){
                                all_fine = false;
                                $(control).addClass('alertedfield');
                                $(control).closest('tr').addClass('alertedcontainer');
                            }
                        }
                });

            /* submit form */
                if (!all_fine){
                    return "You must populate some mandatory fields before submit changes.";
                }else{
                    return "";
                }
        }

        $(document).ready(function(){
                $('form').on('click','.alertedfield',function(){
                    $(this).removeClass('alertedfield');
                });
                $('form').on('click','.alertedcontainer',function(){
                    $(this).removeClass('alertedcontainer');
                });
        });
    </script>

    <style>
        input.alertedfield, select.alertedfield, textarea.alertedfield{background-color:yellow!important;}
        .alertedcontainer td, .alertedcontainer td.fieldrequired{color:red!important;}
        .block{padding:0.5rem;background-color:rgba(100,100,100,0.05);border-radius:3px;border:1px rgba(100,100,100,0.2) solid;}
    </style>

<?php

dol_fiche_end();

print "</form>\n";
print "</div>\n";

clearstatcache();

dol_htmloutput_mesg($mesg);


llxFooter();

$db->close();
