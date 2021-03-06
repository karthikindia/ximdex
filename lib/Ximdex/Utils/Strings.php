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


namespace Ximdex\Utils ;

/**
 *
 *
 */
class Strings {
    /**
     * 
     * @param $texto
     * @return string
     */
    public static function convertText($texto) {

        //$texto = str_replace('\"','',$texto);
        $texto = str_replace(" ","_",$texto);
        $texto = str_replace("!","",$texto);
        $texto = str_replace("?","",$texto);
        $texto = str_replace("(","",$texto);
        $texto = str_replace(")","",$texto);
        $texto = str_replace("[","",$texto);
        $texto = str_replace("]","",$texto);
        $string = htmlentities($texto);
        $texto = preg_replace("/\&(.)[^;]*;/", "\\1", $string);
        $texto = str_replace("\'","",$texto);
        $texto = str_replace('\q','',$texto);

        return $texto;
    }
    /**
     *
     * @param $string
     * @return string
     */
    public static function stripslashes($string) {

        if (get_magic_quotes_gpc())
            return stripslashes($string);

        return $string;
    }

    public static function normalize($string){
        $string = self::convertText($string);
        $source = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŔŕ';
        $target = 'aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr';
        $decodedString = utf8_decode($string);
        $decodedString = strtr($decodedString, utf8_decode($source), $target);
        return utf8_encode($decodedString);
    }

    /**
     *
     * @return string
     */
    public static function generateUniqueID() {

        return md5(uniqid(rand(),1));
    }
    /**
     *
     * @param $quantity
     * @param $includeSmallLetters
     * @param $includeCapitals
     * @param $includeNumbers
     * @return string
     */
    public static function generateRandomChars($quantity, $includeSmallLetters = true, $includeCapitals = false, $includeNumbers = false) {

        if (!($includeSmallLetters || $includeCapitals || $includeNumbers)) {
            return '';
        }
        if (!$quantity > 0) {
            return '';
        }
        $randomListToUse = array();
        $charsCount = 0;

        if ($includeSmallLetters) {
            for ($i = ord('a'); $i <= ord('z'); $i++) {
                $randomListToUse[] = chr($i);
                $charsCount ++;
            }
        }

        if ($includeNumbers) {
            for ($i = ord('0'); $i <= ord('9'); $i++) {
                $randomListToUse[] = chr($i);
                $charsCount ++;
            }
        }

        if ($includeCapitals) {
            for ($i = ord('A'); $i <= ord('Z'); $i++) {
                $randomListToUse[] = chr($i);
                $charsCount ++;
            }
        }

        $randomString = '';

        for ($i = 0; $i < $quantity; $i++) {
            $randomString .= $randomListToUse[rand(0, $charsCount - 1)];
        }

        return $randomString;

    }
}
