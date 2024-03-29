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
 *	\file       htdocs/stockTransfers/lib/stocktransfers.lib.php
 *      \ingroup    stockTransfers
 *      \brief      Mix of functions for Import bank movements module
 *      \version    v 1.0 2017/11/20
 */

$linktohelp='EN:Module_stockTransfers_En|CA:Modul_stockTransfers|ES:Modulo_stockTransfers';

/*
    these are to guarantee compatibility to Dolibarr versions previous to 10 and 11, where didn't exist newToken() and currentToken()
*/
if (!function_exists('newToken')){
	function newToken(){
		return empty($_SESSION['newtoken']) ? '' : $_SESSION['newtoken'];
	}
}
if (!function_exists('currentToken')){
	function currentToken(){
		return isset($_SESSION['token']) ? $_SESSION['token'] : '';
	}
}

/*
    this function should not be necessary, but i've not understood why the dolibarr price() function doesn't render thousands separator
*/
function _price($floatval){
    global $langs, $db, $conf;
    $dec = $langs->transnoentitiesnoconv("SeparatorDecimal");
    if ($dec==',')
        return number_format(floatval($floatval),2,',','.');
    else
        return number_format(floatval($floatval),2,'.',',');
}
function _qty($floatval){
    global $langs, $db, $conf;
    $dec = $langs->transnoentitiesnoconv("SeparatorDecimal");
    $num_decimals = $value = !empty($conf->global->STOCKTRANSFERS_MODULE_SETT_14) ? intval($conf->global->STOCKTRANSFERS_MODULE_SETT_14) : 0;
    if ($dec==',')
        return number_format(floatval($floatval),$num_decimals,',','.');
    else
        return number_format(floatval($floatval),$num_decimals,'.',',');
}

function _render_view($viewname,Array $vars){
    global $langs, $db, $conf;
    // == passed vars
        if (count($vars)>0){
            foreach($vars as $__k__=>$__v__){
                ${$__k__} = $__v__;
            }
        }
    // == we save a copy of the content already existing at the output buffer (for no interrump it)
        $existing_render = ob_get_clean( );
    // == we begin a new output
        ob_start( );
        include(dirname(__FILE__).'/views/'.$viewname.'.php');
    // == we get the current output
        $render = ob_get_clean( );
    // == we re-send to output buffer the existing content before to arrive to this function ;)
        ob_start( );
        echo $existing_render;

        return $render;
}

function _var($arr, $title=''){
		return _var_export($arr, $title);
}

function _var_export($arr, $title=''){
        if ($title!='' && phpversion() > '5.3.0' && class_exists('Tracy\Debugger')){
            eval("Tracy\Debugger::barDump(\$arr,\$title);");
        }

        $html = !empty($title) ? '<h3>'.$title.'</h3>' : '';
	$html .= "\n<div style='margin-left:100px;font-size:10px;font-family:sans-serif;'>";
	if (is_array($arr)){
            if (count($arr)==0){
                $html .= "&nbsp;";
            }else{
                $ii=0;
                foreach ($arr as $k=>$ele){
                    $html .= "\n\t<div style='float:left;'><b>$k <span style='color:#822;'>&rarr;</span> </b></div>"
                            ."\n\t<div style='border:1px #ddd solid;font-size:10px;font-family:sans-serif;'>"._var_export($ele)."</div>";
                    $html .= "\n\t<div style='float:none;clear:both;'></div>";
                    $ii++;
                }
            }
	}else if ($arr===NULL){
            $html .= "&nbsp;";
	}else if ($arr === 'b:0;' || substr($arr,0,2)=='a:'){
            $uns = @unserialize($arr);
            if (is_array($uns))
                $html .= htmlspecialchars($arr).'<br /><br />'._var_export($uns).'<br />';
            else
                $html .= htmlspecialchars($arr);
        }else{
            $html .= htmlspecialchars($arr);
        }
	$html .= "</div>";
	return $html;
}

function _multi_translation($a_keys,$languages){
	
	$translations = array();
	foreach($a_keys as $key){
		$translations[$key] = array();
	}
	
	$languages = scandir(STOCKTRANSFERS_MODULE_DOCUMENT_ROOT.'/langs');
	foreach ($languages as $langcode){ 
		if ($langcode=='.' || $langcode=='..') continue;
		$ex_lang = explode('_',$langcode);
		$lang = $ex_lang[0];
		$file_path = STOCKTRANSFERS_MODULE_DOCUMENT_ROOT.'/langs/'.$langcode.'/stocktransfers.lang';
		if (!file_exists($file_path) || !is_readable($file_path)) continue;
		$fp = @fopen(STOCKTRANSFERS_MODULE_DOCUMENT_ROOT.'/langs/'.$langcode.'/stocktransfers.lang', "r");
		if (!$fp) continue;
		while (($line = fgets($fp, 4096)) !== false) {
			if (trim($line)=='') continue;
			$ex = explode('=',$line);
			if (count($ex)<2) continue;
			if (isset($translations[$ex[0]])){
				$translations[$ex[0]][$lang] = $ex[1];
			}
		}
	}

	return $translations;

}

function _json_decode_translation($const,$defaultLang){
	global $conf;
	
	$sett = !empty($conf->global->$const) ? $conf->global->$const : '';
	if (!preg_match('/\{/',$sett)){ // already NOT is a JSON format... so it's a recently updated module to version 1.23
		$s_translations = array();
		$s_translations[$defaultLang] = $sett;
	}else{
		$s_translations = json_decode($sett,JSON_OBJECT_AS_ARRAY);
	}
	return $s_translations;
}
