<?php
/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 06.02.18
 * Time: 15:09
 */

namespace seretos\BehatJsonFormatter\Printer;


class ScreenshotPrinter {
    public function takeScreenshot($path, $prefix, $data){
        $filename = sprintf('%s_%s_%s.%s', $prefix, date('c'), uniqid('', true), 'png');
        file_put_contents($path . $filename, $data);

        return $filename;
    }
}