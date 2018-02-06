<?php
/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 06.02.18
 * Time: 16:02
 */

namespace seretos\BehatJsonFormatter\Service;


use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class CommandFactory {
    public function createFinder(){
        return new Finder();
    }

    public function readJson($path){
        return json_decode(file_get_contents($path),true);
    }

    public function saveJson($file, $data){
        $fileSystem = new Filesystem();
        $fileSystem->dumpFile($file,json_encode($data, JSON_PRETTY_PRINT));
    }
}