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
 *	\file       htdocs/stocktransfers/core/boxes/box_stocktransfers.php
 *      \defgroup   stocktransfers Module Stock transfers
 *      \brief      Box for Dolibarr dashobard
 *      \version    v 1.0 2017/11/20
 */

include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';

$langs->load("stocktransfers");


/**
 * Class to manage the box to show last stocktransfers
 */
class box_stocktransfers extends ModeleBoxes
{
	var $boxcode="laststocktransfers";
	var $boximg="object_products";
	var $boxlabel='stocktransfersBoxTitle';
	var $depends = array("stocktransfers");

	var $db;
	var $enabled = 1;

	var $info_box_head = array();
	var $info_box_contents = array();


	/**
	 *  Constructor
	 *
	 *  @param  DoliDB	$db      	Database handler
     *  @param	string	$param		More parameters
	 */
	function __construct($db,$param='')
	{
		global $conf, $user;

		$this->db = $db;
		$this->hidden =  !$user->rights->stock->mouvement->lire || !$user->rights->produit;

	}

	/**
     *  Load data for box to show them later
     *
     *  @param	int		$max        Maximum number of records to load
     *  @return	void
	 */
	function loadBox($max=5)
	{
		global $user, $langs, $db, $conf;
		$langs->load("boxes");

		$this->max = $max;

                $this->info_box_head = array('text' => $langs->trans("stocktransfersBoxTitle",$max));

                // == check permissions
                    if (!$user->rights->produit && !$user->rights->fournisseur && !$user->rights->societe){
                        $this->info_box_contents[0][0] = array(
                            'td' => '',
                            'text' => $langs->trans("ReadPermissionNotAllowed"),
                        );
                        return;
                    }

                // == STOCKTRANSFERS_MODULE DOCUMENT_ROOT & URL_ROOT
					if (!defined('STOCKTRANSFERS_MODULE_DOCUMENT_ROOT')){
						if (file_exists(DOL_DOCUMENT_ROOT.'/custom/stocktransfers/core/modules/modStocktransfers.class.php')){
							define('STOCKTRANSFERS_MODULE_DOCUMENT_ROOT',DOL_DOCUMENT_ROOT.'/custom/stocktransfers');
							define('STOCKTRANSFERS_MODULE_URL_ROOT',DOL_URL_ROOT.'/custom/stocktransfers');
						}else{
							define('STOCKTRANSFERS_MODULE_DOCUMENT_ROOT',DOL_DOCUMENT_ROOT.'/stocktransfers');
							define('STOCKTRANSFERS_MODULE_URL_ROOT',DOL_URL_ROOT.'/stocktransfers');
						}
					}

                // == load data
                    include_once STOCKTRANSFERS_MODULE_DOCUMENT_ROOT.'/lib/stocktransfers_transfer.class.php';
                    $transfer = new StockTransfer($db);
                    $transfers = $transfer->getLatestTransfers(array('max'=>$max));
                    if (!is_array($transfers) || count($transfers)==0){
                        $this->enabled = 1;
                        $this->hidden = false;
                        return;
                    }

                // == load depots
                    $depots = array();
                    $resql = $db->query("SELECT * FROM ".MAIN_DB_PREFIX."entrepot");
                    if ($resql) {
                        while($row = $resql->fetch_assoc()) $depots[$row['rowid']] = $row;
                    }

                // == render
                    dol_syslog(get_class($this)."::loadBox", LOG_DEBUG);
                    $status_picto = array('0'=>'0','1'=>'1','2'=>'3','3'=>'4');
                    $line = 0;
                    foreach ($transfers as $t){

                        // = column: date and link to purchase
                            $url = STOCKTRANSFERS_MODULE_URL_ROOT.'/transfer_edit.php?mainmenu=products&leftmenu=&rowid='.$t['rowid'];
                            $picto_link = "<a href='$url'><img src='theme/".$conf->theme."/img/object_product.png' /></a>";
                            $text_link = " <a href='$url'>".$t['ts_create']."</a>";

                            $this->info_box_contents[$line][] = array(
                                'td' => 'align="left"',
                                'text' => $picto_link.$text_link,
                                'asis' => 1,
                            );

                        // = column: depot 1
                            $url = 'product/stock/card.php?id='.$t['fk_depot1'];
                            $picto_link = "<a href='$url'><img src='theme/".$conf->theme."/img/object_company.png' /></a>";
							$text_link = " <a href='$url'>".(!empty($depots[$t['fk_depot1']]) ? $depots[$t['fk_depot1']]['ref'] : '#'.$t['fk_depot1'])."</a>";
                            $this->info_box_contents[$line][] = array(
                                'td' => 'align="left"',
                                'text' => $picto_link.$text_link,
                                'asis' => 1,
                            );

                        // = column: depot 2
                            $url = 'product/stock/card.php?id='.$t['fk_depot2'];
                            $picto_link = "<a href='$url'><img src='theme/".$conf->theme."/img/object_company.png' /></a>";
                            $text_link = " <a href='$url'>".(!empty($depots[$t['fk_depot2']]) ? $depots[$t['fk_depot2']]['ref'] : '#'.$t['fk_depot2'])."</a>";
                            $this->info_box_contents[$line][] = array(
                                'td' => 'align="left"',
                                'text' => $picto_link.$text_link,
                                'asis' => 1,
                            );

                        // = column: number of products included
                            $this->info_box_contents[$line][] = array(
                                'td' => 'align="center"',
                                'text' => $t['n_products']. ' '. $langs->trans('STProducts')
                            );

                        // = column: status
                            $text = '';
                            if (!isset($t['status']))
                                $text = '';
                            else if ($t['status']=='0')
                                $text = img_picto($langs->trans('stocktransfersStatus0'),'statut0');
                            else if ($t['status']=='1')
                                $text = img_picto($langs->trans('stocktransfersStatus1'),'statut3');
                            else if ($t['status']=='2')
                                $text = img_picto($langs->trans('stocktransfersStatus2'),'statut4');

                            $this->info_box_contents[$line][] = array(
                                'td' => 'align="center" width="18"',
                                'text' => $text
                            );

                        $line++;
                    }

                    //if ($num==0) $this->info_box_contents[$line][0] = array('td' => 'align="center"','text'=>$langs->trans("NoRecordedCustomers"));

	}

	/**
	 *	Method to show box
	 *
	 *	@param	array	$head       Array with properties of box title
	 *	@param  array	$contents   Array with properties of box lines
	 *  @param	int		$nooutput	No print, only return string
	 *	@return	string
	 */
    function showBox($head = null, $contents = null, $nooutput=0)
    {
		return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}

}
