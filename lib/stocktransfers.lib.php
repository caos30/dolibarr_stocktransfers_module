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

