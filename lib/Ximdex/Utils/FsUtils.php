<?php
/**
 *  \details &copy; 2011  Open Ximdex Evolution SL [http.org]
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
 *  If not, visit http/licenses/agpl-3.0.html.
 *
 * @author Ximdex DevTeam <dev@ximdex.com>
 * @version $Revision$
 */

namespace Ximdex\Utils;

use League\Flysystem\Config;
use League\Flysystem\Plugin\ListPaths;
use Ximdex\Logger;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local;
use League\Flysystem\MountManager;

class FsUtils
{

    /**
     * @var MountManager
     */
    private static $manager = null;

    /**
     * @return MountManager
     */
    private static function getManager()
    {
        if (empty(self::$manager)){
            self::setupAdapters();
        }
        return self::$manager;
    }

    private static function setupAdapters(){
        $adapterData = new Local(XIMDEX_ROOT_PATH . '/data2');
        $data = new Filesystem($adapterData, new Config([
            'disable_asserts' => true,
        ]));
        $data->addPlugin(new ListPaths());
        $ximdexAdapter = new Local(XIMDEX_ROOT_PATH);
        $ximdex = new Filesystem($ximdexAdapter, new Config([
            'disable_asserts' => true,
        ]));
        $ximdex->addPlugin(new ListPaths());
        self::$manager = new MountManager([
            'data' => $data,
            'ximdex' => $ximdex,
        ]);


    }

    private static function getStoragePath($path = ""){
        if (empty($path)){
            throw new \Exception('Empty path');
        }
        if( strpos($path, XIMDEX_ROOT_PATH) ===  0 ) {
            $path = substr($path, strlen(XIMDEX_ROOT_PATH));
        }
        Logger::debug($path);
        if( strpos($path, '/') === 0 ) {
            $path = substr($path, 1);
        }
        if( strpos($path, 'data/') ===  0) {
            $path = substr($path, 5);
            $path = "data://{$path}";
        }else{
            if( strpos($path, '/') === 0 ) {
                $path = substr($path, 1);
            }
            $path = "ximdex://{$path}";
        }
        Logger::debug($path);
        return $path;
    }

    public static function exists($path){
        $storage = self::getStoragePath($path);
        $manager = self::getManager();
        return $manager->has($storage);
    }


    /**
     * @param $file
     * @return mixed|null
     */
    static public function get_mime_type($file)
    {
        $storage = self::getStoragePath($file);
        $manager = self::getManager();
        return $manager->getMimetype($storage);
    }

    /**
     * @param $filename
     * @param $data
     * @param null $flags
     * @param null $context
     * @return bool
     */
    static public function file_put_contents($filename, $data)
    {
        $storage = self::getStoragePath($filename);
        $manager = self::getManager();
        return $manager->put($storage, $data);
    }

    /**
     * @param $path
     * @param int $mode
     * @param bool $recursive
     * @return bool
     */
    static public function mkdir($path)
    {
        $storage = self::getStoragePath($path);
        $manager = self::getManager();
        return $manager->createDir($storage);
    }

    /**
     * @param $filename
     * @return false|string
     * @internal param $aaa
     * @internal param $storage
     */
    static public function file_get_contents($filename)
    {
        $storage = self::getStoragePath($filename);
        $manager = self::getManager();
        return $manager->read($storage);
    }

    /**
     * @param $file
     * @return string
     */
    static public function get_name($file)
    {
        $path_parts = pathinfo($file);
        return $path_parts['filename'];
    }

    /**
     * @param $file
     * @return bool
     */
    static public function get_extension($file)
    {
        if (empty($file)) {
            return false;
        }

        $path_parts = pathinfo($file);
        return $path_parts['extension'];
    }

    /**
     * @param $path
     * @param bool $recursive
     * @param array $excluded
     * @return array|null
     */
    static public function readFolder($path, $recursive = true, $excluded = array())
    {

        if (!is_dir($path)) {
            return null;
        }
        $storage = self::getStoragePath($path);
        $manager = self::getManager();
        $files = $manager->listPaths("{$storage}", $recursive);

//		assert($recursive);
        if (!is_array($excluded)) $excluded = array($excluded);
        $excluded = array_merge(array('.', '..', '.svn'), $excluded);
        $files = scandir($path);
        $files = array_values(array_diff($files, $excluded));

        return $files;
    }

    /**
     * Static function
     * Function which deletes a folder and all its content (as deltree command of msdos)
     *
     * @param string $folder
     * @return boolean
     */
    static public function deltree($folder)
    {
        $storage = self::getStoragePath($folder);
        $manager = self::getManager();
        return $manager->deleteDir($storage);
    }

    /**
     * @param $file
     * @return bool
     */
    static public function delete($file)
    {
        $storage = self::getStoragePath($file);
        $manager = self::getManager();
        $manager->delete($storage);
    }

    /**
     * @param $containerFolder
     * @param string $sufix
     * @param string $prefix
     * @return string
     */
    static public function getUniqueFile($containerFolder, $sufix = '', $prefix = '' )
    {
        $storage = self::getStoragePath($containerFolder);
        $manager = self::getManager();
        do {
            $fileName = Strings::generateUniqueID();
            $tmpFile = sprintf("%s/%s%s%s", $containerFolder, $prefix, $fileName, $sufix);
        } while ($manager->has($storage));
        Logger::debug("getUniqueFile: return: {$fileName} | container: $containerFolder");
        return $fileName;
    }

    /**
     * @param $containerFolder
     * @param string $sufix
     * @param string $prefix
     * @return string
     */
    static public function getUniqueFolder($containerFolder, $sufix = '', $prefix = '' )
    {
        $storage = self::getStoragePath($containerFolder);
        $manager = self::getManager();
        do {
            $tmpFolder = sprintf("%s/%s%s%s/", $containerFolder, $prefix,  Strings::generateRandomChars(8), $sufix);
        } while ($manager->has($storage));
        return $tmpFolder;
    }

    static public function copy($sourceFile, $destFile )
    {
        $storageS = self::getStoragePath($sourceFile);
        $storageD = self::getStoragePath($destFileFile);
        $manager = self::getManager();
        return $manager->copy($storage, $storage);
    }

    /**
     * Get the files in the folder (and descendant) with an extension.
     * @param $path string Folder to read
     * @param $extensions array Extension to file
     * @param $recursive boolean Indicate if has to recursive read of path folder
     * @return array Found files.
     */
    static public function getFolderFilesByExtension($path, $extensions = array(), $recursive = true)
    {

        if (!is_dir($path)) {
            return null;
        }
        $storage = self::getStoragePath($path);
        $manager = self::getManager();
        $excluded = array('.', '..', '.svn');

        $files = $manager->listPaths($storage, $recursive);
        $files = array_values(array_diff($files, $excluded));

        return array_values($files);
    }

}
