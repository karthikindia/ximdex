<?php
/**
 *  \details &copy; 2011  Open Ximdex Evolution SL [http://www.ximdex.org]
 *
 *  Ximdex a Semantic Content Management System (CMS)
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published
 *  by the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  See the Affero GNU General Public License for more details.
 *  You should have received a copy of the Affero GNU General Public License
 *  version 3 along with Ximdex (see LICENSE file).
 *
 *  If not, visit http://gnu.org/licenses/agpl-3.0.html.
 *
 *  @author Ximdex DevTeam <dev@ximdex.com>
 *  @version $Revision$
 */



//
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,      |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Hartmut Holzgraefe <hholzgra@php.net>                       |
// |          Christian Stocker <chregu@bitflux.ch>                       |
// +----------------------------------------------------------------------+
//
// $Id: _parse_proppatch.php,v 1.3 2004/01/05 12:41:34 hholzgra Exp $
//

class _parse_proppatch 
{
    /**
     *
     * 
     * @var boolean
     * @access
     */
    var $success;

    /**
     *
     * 
     * @var array
     * @access
     */
    var $props;

    /**
     *
     * 
     * @var integer
     * @access
     */
    var $depth;

    /**
     *
     * 
     * @var mixed
     * @access
     */
    var $mode;

    /**
     *
     * 
     * @var mixed
     * @access
     */
    var $current;

    /**
     * constructor
     * 
     * @param  string  path of input stream 
     * @access public
     */
    function _parse_proppatch($path) 
    {
        $this->success = true;

        $this->depth = 0;
        $this->props = array();
        $had_input = false;

        $f_in = fopen($path, "r");
        if (!$f_in) {
            $this->success = false;
            return;
        }

        $xml_parser = xml_parser_create_ns("UTF-8", " ");

        xml_set_element_handler($xml_parser,
                                array(&$this, "_startElement"),
                                array(&$this, "_endElement"));

        xml_set_character_data_handler($xml_parser,
                                       array(&$this, "_data"));

        xml_parser_set_option($xml_parser,
                              XML_OPTION_CASE_FOLDING, false);

        while($this->success && !feof($f_in)) {
            $line = fgets($f_in);
            if (is_string($line)) {
                $had_input = true;
                $this->success &= xml_parse($xml_parser, $line, false);
            }
        } 
        
        if($had_input) {
            $this->success &= xml_parse($xml_parser, "", true);
        }

        xml_parser_free($xml_parser);

        fclose($f_in);
    }

    /**
     * tag start handler
     *
     * @param  resource  parser
     * @param  string    tag name
     * @param  array     tag attributes
     * @return void
     * @access private
     */
    function _startElement($parser, $name, $attrs) 
    {
        if (strstr($name, " ")) {
            list($ns, $tag) = explode(" ", $name);
            if ($ns == "")
                $this->success = false;
        } else {
            $ns = "";
            $tag = $name;
        }

        if ($this->depth == 1) {
            $this->mode = $tag;
        } 

        if ($this->depth == 3) {
            $prop = array("name" => $tag);
            $this->current = array("name" => $tag, "ns" => $ns, "status"=> 200);
            if ($this->mode == "set") {
                $this->current["val"] = "";     // default set val
            }
        }

        if ($this->depth >= 4) {
            $this->current["val"] .= "<$tag";
            foreach ($attr as $key => $val) {
                $this->current["val"] .= ' '.$key.'="'.str_replace('"','&quot;', $val).'"';
            }
            $this->current["val"] .= ">";
        }

        

        $this->depth++;
    }

    /**
     * tag end handler
     *
     * @param  resource  parser
     * @param  string    tag name
     * @return void
     * @access private
     */
    function _endElement($parser, $name) 
    {
        if (strstr($name, " ")) {
            list($ns, $tag) = explode(" ", $name);
            if ($ns == "")
                $this->success = false;
        } else {
            $ns = "";
            $tag = $name;
        }

        $this->depth--;

        if ($this->depth >= 4) {
            $this->current["val"] .= "</$tag>";
        }

        if ($this->depth == 3) {
            if (isset($this->current)) {
                $this->props[] = $this->current;
                unset($this->current);
            }
        }
    }

    /**
     * input data handler
     *
     * @param  resource  parser
     * @param  string    data
     * @return void
     * @access private
     */
    function _data($parser, $data) {
        if (isset($this->current)) {
            $this->current["val"] .= $data;
        }
    }
}

?>