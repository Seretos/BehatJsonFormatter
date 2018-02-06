<?php
/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 04.02.18
 * Time: 04:13
 */

namespace seretos\BehatJsonFormatter\Printer;

use Behat\Testwork\Output\Printer\OutputPrinter as PrinterInterface;
use Symfony\Component\Filesystem\Filesystem;

class FileOutputPrinter implements PrinterInterface {

    private $path;
    /**
     * Sets output path.
     *
     * @param string $path
     */
    public function setOutputPath ($path) {
        $this->path = $path;
    }

    /**
     * Returns output path.
     *
     * @return null|string
     *
     * @deprecated since 3.1, to be removed in 4.0
     */
    public function getOutputPath () {
        return '.';
    }

    /**
     * Sets output styles.
     *
     * @param array $styles
     */
    public function setOutputStyles (array $styles) {
    }

    /**
     * Returns output styles.
     *
     * @return array
     *
     * @deprecated since 3.1, to be removed in 4.0
     */
    public function getOutputStyles () {
        return [];
    }

    /**
     * Forces output to be decorated.
     *
     * @param Boolean $decorated
     */
    public function setOutputDecorated ($decorated) {
    }

    /**
     * Returns output decoration status.
     *
     * @return null|Boolean
     *
     * @deprecated since 3.1, to be removed in 4.0
     */
    public function isOutputDecorated () {
        return true;
    }

    /**
     * Sets output verbosity level.
     *
     * @param integer $level
     */
    public function setOutputVerbosity ($level) {
    }

    /**
     * Returns output verbosity level.
     *
     * @return integer
     *
     * @deprecated since 3.1, to be removed in 4.0
     */
    public function getOutputVerbosity () {
        return 0;
    }

    /**
     * Writes message(s) to output stream.
     *
     * @param string|array $messages message or array of messages
     */
    public function write ($messages) {
        $fileSystem = new Filesystem();
        $fileSystem->mkdir(dirname($this->path));
        $fileSystem->dumpFile($this->path, $messages);
    }

    /**
     * Writes newlined message(s) to output stream.
     *
     * @param string|array $messages message or array of messages
     */
    public function writeln ($messages = '') {
    }

    /**
     * Clear output stream, so on next write formatter will need to init (create) it again.
     */
    public function flush () {
    }
}