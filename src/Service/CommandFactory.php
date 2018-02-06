<?php
/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 06.02.18
 * Time: 16:02
 */

namespace seretos\BehatJsonFormatter\Service;


use Behat\Gherkin\Keywords\ArrayKeywords;
use Behat\Gherkin\Lexer;
use Behat\Gherkin\Parser;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class CommandFactory {
    /**
     * @return Finder
     */
    public function createFinder(){
        return new Finder();
    }

    /**
     * @param string $path
     * @return mixed
     */
    public function readJson($path){
        return json_decode(file_get_contents($path),true);
    }

    /**
     * @param string $file
     * @param array $data
     */
    public function saveJson($file, $data){
        $fileSystem = new Filesystem();
        $fileSystem->dumpFile($file,json_encode($data, JSON_PRETTY_PRINT));
    }

    public function createBehatParser(){
        $keywords = [];
        if(file_exists(__DIR__.'/../../../../behat/gherkin/i18n.php')){
            $keywords = include(__DIR__ . '/../../../../i18n.php');
        }else if(file_exists(__DIR__.'/../../vendor/behat/gherkin/i18n.php')){
            $keywords = include(__DIR__ . '/../../vendor/behat/gherkin/i18n.php');
        }

        $keywords = new ArrayKeywords($keywords);
        $lexer  = new Lexer($keywords);
        $parser = new Parser($lexer);
        return $parser;
    }

    public function readFile($file){
        return file_get_contents($file);
    }
}