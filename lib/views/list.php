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
 *	\file       htdocs/stocktransfers/lib/views/list.php
 *      \defgroup   Stocktransfers Stock Transfers
 *      \brief      View for transfer list
 *      \version    v 1.0 2017/11/20
 */

/***************************************************
 *
 *	Prepare data
 *
****************************************************/

    // == incoming and default data
        $id = GETPOST('id','int');
        $limit = GETPOST('limit') ? GETPOST('limit','int') : intval($conf->liste_limit);
        $page = GETPOST("page",'int');

            if (empty($page)) $page = 0;
            $pageprev = empty($page) ? 0 : $page - 1;
            $pagenext = empty($page) ? 1 : $page + 1;
            $offset = $limit * $page;

        $sortfield = GETPOST("sortfield",'alpha');
        $sortorder = GETPOST("sortorder",'alpha');

            if (! $sortfield) $sortfield = "rowid";
            if (! $sortorder) $sortorder = "DESC";

    // == object fields
        $arrayfields=array(
            //'rowid'=>array('label'=>$langs->trans("STID"), 'checked'=>1, 'style'=>'width:4rem;'),
            'ts_create'=>array('label'=>$langs->trans("STDate"), 'checked'=>1),
            'label'=>array('label'=>$langs->trans("STLabel"), 'checked'=>1),
            'inventorycode'=>array('label'=>$langs->trans("STinventorycode"), 'checked'=>0),
            'fk_depot1'=>array('label'=>$langs->trans("WarehouseSource"), 'checked'=>1),
            'fk_depot2'=>array('label'=>$langs->trans("WarehouseTarget"), 'checked'=>1),
            'date1'=>array('label'=>$langs->trans("stocktransfersDate1"), 'checked'=>1),
            'date2'=>array('label'=>$langs->trans("stocktransfersDate2"), 'checked'=>0),
            'fk_project'=>array('label'=>$langs->trans("stocktransfersProject"), 'checked'=>1),
            'shipper'=>array('label'=>$langs->trans("stocktransfersShipper"), 'checked'=>0),
            'n_package'=>array('label'=>$langs->trans("stocktransfersNPackages"), 'checked'=>0),
            'n_products'=>array('label'=>$langs->trans("STProducts"), 'checked'=>1,  'style'=>'width:4rem;'),
            'status'=>array('label'=>$langs->trans("STStatus"), 'checked'=>1),
        );
        if (empty($conf->projet->enabled)) unset($arrayfields['fk_project']);

	// == clean some date filters "not wanted"
		foreach (array('ts_create','date1','date2') as $aa){
			foreach (array('_start','_end') as $bb){
				if (isset($fsearch[$aa.$bb.'day']))   unset($fsearch[$aa.$bb.'day']); // example: ts_create_startday
				if (isset($fsearch[$aa.$bb.'month'])) unset($fsearch[$aa.$bb.'month']);
				if (isset($fsearch[$aa.$bb.'year']))  unset($fsearch[$aa.$bb.'year']);
			}
		}
	
    // == load transfers

        $sql = "SELECT * FROM ".MAIN_DB_PREFIX."stocktransfers_transfers";
        
        // == WHERE filter
            $where = array();
            foreach ($fsearch as $ff=>$vv){
				 $nsearch = '';
                 if ($ff=='status'){
                    $nsearch = "(status LIKE '%$vv%')";
                 }else if ($ff=='fk_project' || $ff=='fk_depot1' || $ff=='fk_depot2'){
					if (!empty($vv) && $vv!='-1'){
						$nsearch = natural_search($ff, $vv, 0, 1);
					}
				 // --- START DATES filters
				 }else if ($ff=='ts_create_start' || $ff=='date1_start' || $ff=='date2_start'){
					if (!empty($vv) && $vv!='-1'){
						$fieldname = str_replace('_start','',$ff); // ts_create_start -> ts_create
						$ex = explode('-',str_replace('/','-',$vv)); // 31/12/2021
						$ts = mktime(0,0,0,intval($ex[1]),intval($ex[0]),intval($ex[2]));
						$nsearch = $fieldname." > '".date('Y-m-d H:i:s',$ts)."'";
					}	
				 }else if ($ff=='ts_create_end' || $ff=='date1_end' || $ff=='date2_end'){
					if (!empty($vv) && $vv!='-1'){
						$fieldname = str_replace('_end','',$ff); // ts_create_end -> ts_create
						$ex = explode('-',str_replace('/','-',$vv)); // 31/12/2021
						$ts = mktime(23,59,59,intval($ex[1]),intval($ex[0]),intval($ex[2]));
						$nsearch = $fieldname." < '".date('Y-m-d H:i:s',$ts)."'";
					}	
                 }else{
                    $nsearch = natural_search($ff, $vv, 0, 1);
				 }
                 if (!empty($nsearch) && $nsearch != '()') $where[] =  $nsearch;
            }
            if (count($where)>0) $sql .= ' WHERE '.implode(' AND ',$where);
			
        // == ORDER
            $sql.= ' ORDER BY ';
            $listfield = explode(',',$sortfield);
            foreach ($listfield as $key => $value)
                $sql.= $listfield[$key].' '.$sortorder.',';
            $sql.= ' rowid DESC ';

        // == Count total nb of records
            $nbtotalofrecords = '';
            if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)){
                $sql_count = "SELECT count(rowid) as n FROM ".MAIN_DB_PREFIX."stocktransfers_transfers ";
                if (count($where)>0) $sql_count .= ' WHERE '.implode(' AND ',$where);
                $resql = $db->query($sql_count);
                if ($resql)
                    $row = $resql->fetch_assoc();
                else
                    $row = array();
                $nbtotalofrecords = isset($row['n']) ? floatval($row['n']) : 0;
            }

        // == LIMIT
            $sql .= $db->plimit($limit+1, $offset);

        // == run query
            $transfers = array();
            $resql = $db->query($sql);
            if ($resql) {
                //while($obj = $db->fetch_object($resql)) $transfers[$obj->rowid] = $obj;
                while($row = $resql->fetch_assoc()) $transfers[] = $row;
            }
			//echo _var_export($fsearch,'$fsearch')._var_export($sql,'$sql')._var_export($transfers,'$transfers');

    // == load depots
        $depots = array();
        $resql = $db->query("SELECT * FROM ".MAIN_DB_PREFIX."entrepot");
        $depots_select_options = array();
        if ($resql) {
            while($row = $resql->fetch_assoc()){
				$depots[$row['rowid']] = $row;
				$depot_label = !empty($row['ref']) ? $row['ref']
                                    : (!empty($row['lieu']) ? $row['lieu']
                                    : (!empty($row['label']) ? $row['label']
                                    : '#'.$row['rowid'] ));
				$depots_select_options[$row['rowid']] = $depot_label;
			}
        }

    // == load projects
        $projects = array();
        $resql = $db->query("SELECT rowid,title FROM ".MAIN_DB_PREFIX."projet");
        $projects_select_options = array();
        if ($resql) {
            while($row = $resql->fetch_assoc()) {
				$projects[$row['rowid']] = $row;
				$projects_select_options[$row['rowid']] = $row['title'];
			}
        }

    // == load products names, if needed
        if (!empty($arrayfields['n_products']['checked'])){
            $products = array();
            $resql = $db->query("SELECT rowid,label FROM ".MAIN_DB_PREFIX."product");
            if ($resql) {
                while($row = $resql->fetch_assoc()) $products[$row['rowid']] = $row['label'];
            }
        }

    // == fetch optionals attributes and labels
        /*
        $extrafields = new ExtraFields($db);
        $extralabels = $extrafields->fetch_name_optionals_label('projet');
        $search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');
        */
        
	// == prepare languages
		$languages = scandir(STOCKTRANSFERS_MODULE_DOCUMENT_ROOT.'/langs'); 
		$def_lang = !empty($conf->global->STOCKTRANSFERS_MODULE_SETT_16) ? $conf->global->STOCKTRANSFERS_MODULE_SETT_16 : 'auto';
		if ($def_lang=='auto') $def_lang = $langs->getDefaultLang();
		$langs->load("languages"); 

    // == param for Action bar
        $param='';
        if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
        if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;

        // == add GETPOST search params
        foreach ($fsearch as $ff=>$vv){
            $param.='&search_'.$ff.'='.urlencode($vv);
        }

        $form = new Form($db);
        $varpage = empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
        $selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);	// This also change content of $arrayfields

    // == mass actions (i.e. delete a group of lines)
        $arrayofmassactions=array(
	    'delete'=>$langs->trans("Delete")
	);
	$massactionbutton = $form->selectMassAction('', $arrayofmassactions);



/***************************************************
 *
 *	View
 *
****************************************************/

    // == browser top title
        $help_url = '';
        llxHeader('',$langs->trans('stocktransfersBriefTitle'),$help_url);
        //echo _var_export($transfers,'$transfers');die();

    // == misc
        $moreforfilter = true;
        $new_button = '<a href="transfer_edit.php?mainmenu=products&leftmenu=" class="button">'
						.(DOL_VERSION >= 12 && !defined('DISABLE_FONT_AWSOME') ? '<i class="fa fa-plus-square"></i>&nbsp; ' : '')
						.$langs->trans('stocktransfersNewTransfer').'</a>';

?>

    <style><?= str_replace(array(" ","\n","\t"),'',file_get_contents(__DIR__.'/styles.css')) ?></style>

    <!-- ========= header with section title & pagination controls ========= -->

    <form method="POST" id="transfer_searchFormList" name="searchFormList" action="<?= $_SERVER["PHP_SELF"] ?>">

        <input type="hidden" name="token" value="<?= $_SESSION['newtoken'] ?>" />
	<input type="hidden" name="formfilteraction" id="formfilteraction" value="list" />
        <input type="hidden" name="action" value="list" />
        <input type="hidden" name="sortfield" value="<?= $sortfield ?>" />
        <input type="hidden" name="sortorder" value="<?= $sortorder ?>" />

    <?php print_barre_liste($langs->trans('stocktransfersMenuTitle2'), $page, $_SERVER["PHP_SELF"], $param,
                        $sortfield, $sortorder, $massactionbutton, count($transfers), $nbtotalofrecords,
                        'title_products.png', 0, $new_button, '', $limit); ?>


    <!-- ========= action bar ========= -->

    <?php if (empty($action) && $id > 0) { ?>

    <div class="tabsAction">

    <?php   if ($user->rights->stock->mouvement->creer) { ?>
            <a class="butAction" href="<?= $_SERVER["PHP_SELF"].'?id='.$id.'&action=correction' ?>">
                <?= $langs->trans("StockCorrection") ?></a>
    <?php       } ?>

    <?php   if ($user->rights->stock->mouvement->creer) { ?>
            <a class="butAction" href="<?= $_SERVER["PHP_SELF"].'?id='.$id.'&action=transfert' ?>">
                <?= $langs->trans("StockTransfer") ?></a>
    <?php   } ?>

    </div><br />

    <?php } ?>

    <!-- ========= table list ========= -->

    <div class="underbanner clearboth"></div>

    <div class="div-table-responsive">

    <table class="tagtable liste <?= $moreforfilter ? "listwithfilterbefore":"" ?>">
        <thead>

    <!-- ========= header first row (column titles) ========= -->

        <tr class="liste_titre">
            <td>&nbsp;</td> <!-- action column for buttons (edit...) -->
            <?php
                // == field columns
                foreach($arrayfields as $f=>$field){
                    if (!empty($field['checked'])){
						$moreattrib = 'style="white-space:normal;"';
                        print_liste_field_titre($field['label'],$_SERVER["PHP_SELF"],$f,'',$param,$moreattrib,$sortfield,$sortorder);
                    }
                }
				
                // == action column
                print print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'','','align="center"',$sortfield,$sortorder,'maxwidthsearch ');

            ?>
        </tr>

    <!-- ========= header second row (filters) ========= -->

        <tr class="liste_titre">
            <td>&nbsp;</td> <!-- action column for buttons (edit...) -->
            <?php
                // == field columns
                foreach($arrayfields as $f=>$field){
                    if (!empty($field['checked'])){
                        print '<td class="liste_titre" align="left">';

                        if ($f=='n_products'){
                            print '';
                        }else if ($f=='rowid'){
                            print '<input class="flat" style="'.(!empty($field['style']) ? $field['style']:'width:80%;').'" type="text" name="search_'.$f.'" value="'.(!empty($fsearch[$f]) ? dol_escape_htmltag($fsearch[$f]) : '').'" '.(!empty($field['param']) ? $field['param'].' ' : '').'/>';
                        }else if ($f=='status'){
                            print $form->selectarray('search_'.$f,
                                            array(  '0'=>$langs->trans("stocktransfersStatus0"),
                                                    '1'=>$langs->trans("stocktransfersStatus1"),
                                                    '2'=>$langs->trans("stocktransfersStatus2"))
                                            ,$fsearch['status'], 1);
						}else if ($f=='fk_project'){
							print $form->selectarray('search_fk_project', $projects_select_options, $fsearch['fk_project'], 1, '', '', '', '',12);
						}else if ($f=='fk_depot1'){
							print $form->selectarray('search_fk_depot1', $depots_select_options, $fsearch['fk_depot1'], 1, '', '', '', '',12);
						}else if ($f=='fk_depot2'){
							print $form->selectarray('search_fk_depot2', $depots_select_options, $fsearch['fk_depot2'], 1, '', '', '', '',12);
						}else if (($f=='ts_create' || $f=='date1' || $f=='date2') && DOL_VERSION >= 9){
							$ts_start = -1; 
							$ts_end = -1;
							if (!empty($fsearch[$f.'_start'])){
								$ex = explode('-',str_replace('/','-',$fsearch[$f.'_start']));
								$ts_start = mktime(0,0,1,intval($ex[1]),intval($ex[0]),intval($ex[2]));
							}
							if (!empty($fsearch[$f.'_end'])){
								$ex = explode('-',str_replace('/','-',$fsearch[$f.'_end']));
								$ts_end = mktime(0,0,1,intval($ex[1]),intval($ex[0]),intval($ex[2]));
							}
							print '<div class="nowrap">';
							print $form->selectDate($ts_start, 'search_'.$f.'_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
							print '</div>';
							print '<div class="nowrap">';
							print $form->selectDate($ts_end, 'search_'.$f.'_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
							print '</div>';
                        }else{
                            print '<input class="flat" style="'.(!empty($field['style']) ? $field['style']:'width:80%;').'" type="text" name="search_'.$f.'" value="'.(!empty($fsearch[$f]) ? dol_escape_htmltag($fsearch[$f]) : '').'" '.(!empty($field['param']) ? $field['param'].' ' : '').'/>';
						}

                        print '</td>';
                    }
                }

                // == action column
                print '<td class="liste_titre" align="middle">'
                        .$form->showFilterAndCheckAddButtons($massactionbutton?1:0, 'checkforselect', 1)
                        .'</td>';

            ?>
        </tr>
    </thead>

    <!-- ========= body rows ========= -->

    <tbody>

    <?php if (is_array($transfers) && count($transfers)>0){
            foreach ($transfers as $ii=>$ele){
                if (!is_array($ele)) continue;
                if ($ii >= $limit) continue; /* this is because we musn't to include the last row */
                // = prepare list of products
                    $html_list_products = '';
                    if (!empty($ele['n_products']) && !empty($arrayfields['n_products']['checked'])){
                        $a_products = unserialize($ele['s_products']);
                        foreach ($a_products as $p) $html_list_products .= '<li>'._qty($p['n']).' <b>x</b> '.(isset($products[$p['id']]) ? str_replace('"','',$products[$p['id']]) :'pid #'.$p['id']).'</li>';
                        $html_list_products = '<ul style="margin:0;padding:0;">'.$html_list_products.'</ul>';
                    }
				// = prepare PDFlang
					$PDFlang = !empty($ele['lang']) ? $ele['lang'] : $def_lang;
    ?>
        <tr>
            <!-- action column for buttons (edit...) -->
            <td>
                <a class="button" href="transfer_edit.php?mainmenu=products&leftmenu=&rowid=<?= $ele['rowid'] ?>">
                    <span class="fa fa-cog"></span>
                </a>
            </td>
            <?php
                foreach($arrayfields as $f=>$field){ //  use this to render fancy tooltips stored on title attribute of a link class="classfortooltip"
                    if (!empty($field['checked'])){
                        if ($f=='rowid'){
                            print '<td style="text-align:center;">'
                                 .' <a href="transfer_edit.php?mainmenu=products&leftmenu=&rowid='.$ele[$f].'">#'.$ele['rowid'].'</a>'
                                 .'</td>';

                        }else if ($f=='ts_create'){
                            print '<td style="text-align:center;">'.dol_print_date($ele[$f],'dayhour').'</td>';

                        }else if ($f=='fk_depot1' || $f=='fk_depot2'){
                            if (!isset($ele[$f])){
                                print '<td>&nbsp;</td>';
                            }else if (isset($depots[$ele[$f]])){
                                $depot_label = !empty($depots[$ele[$f]]['ref']) ? $depots[$ele[$f]]['ref']
                                    : (!empty($depots[$ele[$f]]['lieu']) ? $depots[$ele[$f]]['lieu']
                                    : (!empty($depots[$ele[$f]]['label']) ? $depots[$ele[$f]]['label']
                                    : '#'.$depots[$ele[$f]]['rowid'] ));
                                $wh_tooltip = str_replace('"','',$depots[$ele[$f]]['lieu'].' ('.$depots[$ele[$f]]['town'].')');
                                print '<td>';
                                
                                if (DOL_VERSION >= 12 && !defined('DISABLE_FONT_AWSOME')){ // we can use fontwawesome icons
									print '<a href="'.DOL_URL_ROOT.'/product/stock/card.php?id='.$ele[$f].'" title="'.$wh_tooltip.'"'
										 .'		><span class="fa fa-box-open"></span></a>';
								}else{
									print ' <a href="'.DOL_URL_ROOT.'/product/stock/card.php?id='.$ele[$f].'" title="'.$wh_tooltip.'">'
										 .'		<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/object_company.png" border="0" /></a>';
								}
								
                                print ' <a href="'.DOL_URL_ROOT.'/product/stock/card.php?id='.$ele[$f].'" title="'.$wh_tooltip.'">'.$depot_label.'</a></td>';
                            }else{
                                print '<td>#'.$ele[$f].'</td>';
                            }

                        }else if ($f=='date1' || $f=='date2'){
                            print '<td style="text-align:center;">'.dol_print_date($ele[$f].' 13:01').'</td>';

                        }else if ($f=='fk_project'){
                            if (empty($ele[$f])){
                                print '<td>&nbsp;</td>';
                            }else if (isset($projects[$ele[$f]])){
								print '<td>';
								
								if (DOL_VERSION >= 12 && !defined('DISABLE_FONT_AWSOME')){ // we can use fontwawesome icons
									print ' <a href="'.DOL_URL_ROOT.'/projet/card.php?id='.$ele[$f].'" '
										 .'		><span class="fa fa-project-diagram"></span></a>';
								}else{
									print ' <a href="'.DOL_URL_ROOT.'/projet/card.php?id='.$ele[$f].'" '
										 .'		><img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/object_project.png" border="0"></a>';
								}
                                print ' <a href="'.DOL_URL_ROOT.'/projet/card.php?id='.$ele[$f].'">'.$projects[$ele[$f]]['title'].'</a></td>';
                            }else{
                                print '<td>#'.$ele[$f].'</td>';
                            }

                        }else if ($f=='status'){
                            if (!isset($ele[$f]))
                                print '<td>&nbsp;</td>';
                            else if ($ele[$f]=='0')
                                print '<td style="text-align:center;">'
										.(DOL_VERSION >= 12 && !defined('DISABLE_FONT_AWSOME') 
											? '<i class="fa fa-lg fa-file-o" title="'.$langs->trans('stocktransfersStatus0').'" style="opacity:0.7;color:inherit;"></i>'
											: img_picto($langs->trans('stocktransfersStatus0'),'statut0')
										 )
										.'</td>';
                            else if ($ele[$f]=='1')
                                print '<td style="text-align:center;">'
										.(DOL_VERSION >= 12 && !defined('DISABLE_FONT_AWSOME') 
											? '<i class="fa fa-lg fa-truck" title="'.$langs->trans('stocktransfersStatus1').'" style="opacity:0.7;color:inherit;"></i>'
											: img_picto($langs->trans('stocktransfersStatus1'),'statut3')
										 )
										.'</td>';
                            else if ($ele[$f]=='2')
                                print '<td style="text-align:center;">'
										.(DOL_VERSION >= 12 && !defined('DISABLE_FONT_AWSOME') 
											? '<i class="fa fa-lg fa-check-circle" title="'.$langs->trans('stocktransfersStatus2').'" style="opacity:0.7;color:inherit;"></i>'
											: img_picto($langs->trans('stocktransfersStatus2'),'statut4')
										 )
										.'</td>';
                            else
                                print '<td>&nbsp;</td>';

                        }else if ($f=='n_products'){
                            if ($html_list_products==''){
                                print '<td style="text-align:center;white-space:nowrap;">--</td>';
                            }else{
                                print '<td style="text-align:center;white-space:nowrap;">'
                                        .(isset($ele[$f]) ? intval($ele[$f]) : '');
								// PDF button
                                print ' &nbsp; <a id="bt_download_pdf_'.$ele['rowid'].'" '
										.'			href="transfer_pdf.php?id='.$ele['rowid'].'&l='.$PDFlang.'" target="_blank" title="'.str_replace('"','',  htmlentities($html_list_products)).'" '
                                        .'			class="classfortooltip button" style="min-width:0;">'
                                        .		(DOL_VERSION >= 12 && !defined('DISABLE_FONT_AWSOME') ? 
													'<i class="fa fa-lg fa-file-pdf"></i>' :
													'<img src="img/pdf.png" style="margin-bottom: -2px;" />'
												)
                                        .'	</a>';
								// PDF language button
                                print '<a href="#" onclick="$(\'#sel_pdf_langcode_'.$ele['rowid'].'\').toggle();return false;" '
                                        .'			style="display:inline-block;vertical-align:middle;">'
                                        .		(DOL_VERSION >= 12 && !defined('DISABLE_FONT_AWSOME') ? 
													'<i class="fa fa-lg fa-language"></i>' :
													'<img src="img/lang.png" style="margin-bottom: -2px;" />'
												)
                                        .'</a>';
                                        
								// hidden language selector for PDF
								print '<br /><select id="sel_pdf_langcode_'.$ele['rowid'].'" '
										.'			 onchange="$(\'#bt_download_pdf_'.$ele['rowid'].'\').attr(\'href\',\'transfer_pdf.php?id='.$ele['rowid'].'&l=\'+$(this).val());"'
										.'			 style="text-align:center;display:none;">';
									foreach ($languages as $langcode){
										if ($langcode=='.' || $langcode=='..') continue;
										print '<option value="'.$langcode.'" '.($langcode==$PDFlang ? "selected='selected'":"").'>'.$langs->trans('Language_'.$langcode).'</option>';
									}
									
								print '</td>';
				
                            }

                        }else{
                            print '<td>'.(isset($ele[$f]) ? $ele[$f] : '').'</td>';
                        }

                    }
                }

                print '<td>&nbsp;</td>';
            ?>
        </tr>

    <?php }} ?>

    </tbody>

    </table>

    </form>

    </div>

    <!-- MODULE VERSION & USER GUIDE LINK -->
    <?php
        require_once STOCKTRANSFERS_MODULE_DOCUMENT_ROOT.'/core/modules/modStocktransfers.class.php';
        $module = new modStockTransfers($db);
        $user_lang = substr($langs->defaultlang,0,2);
    ?>
    <div style="margin: 2rem 0;color: #ccc;display: inline-block;border-top: 1px #ccc solid;border-bottom: 1px #ccc solid;background-color: rgba(0,0,0,0.05);padding: 0.5rem;">
        <span class="help">Stock Transfers <?= $module->version ?>
           &nbsp; | &nbsp; <a href="https://imasdeweb.com/index.php?pag=m_blog&gad=detalle_entrada&entry=<?= $user_lang == 'es' ? '38':'39'?>" target="_blank"><?= $langs->trans('stocktransfersUserGuide') ?></a>
        </span>
    </div>

    <script>
        function js_filter_by(fieldname,fieldvalue){
            $('#transfer_searchFormList input[name=search_'+fieldname+']').val(fieldvalue);
            $('#transfer_searchFormList').submit();
        }

        <!-- JS CODE TO ENABLE Tooltips on all object with class classfortooltip -->
        jQuery(document).ready(function () {
            jQuery(".classfortooltip").tooltip({
                show: { collision: "flipfit", effect:'toggle', delay:50 },
                hide: { delay: 50 }, 	/* If I enable effect:'toggle' here, a bug appears: the tooltip is shown when collpasing a new dir if it was shown before */
                tooltipClass: "mytooltip",
                content: function () {
                    return $(this).prop('title'); /* To force to get title as is */
                }
            });
        });
    </script>
    <?php

    // End of page
    $db->close();
    //llxFooter('$Date: 2009/03/09 11:28:12 $ - $Revision: 1.8 $');
