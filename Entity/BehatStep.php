<?php
/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 04.02.18
 * Time: 22:36
 */

namespace seretos\BehatJsonFormatter\Entity;


use Behat\Gherkin\Node\TableNode;
use JsonSerializable;

class BehatStep implements JsonSerializable{
    /**
     * @var string
     */
    private $text;
    /**
     * @var array
     */
    private $arguments;
    /**
     * @var string
     */
    private $keyWord;

    /**
     * @return string
     */
    public function getText () {
        return $this->text;
    }

    /**
     * @param string $text
     *
     * @return BehatStep
     */
    public function setText ($text) {
        $this->text = $text;

        return $this;
    }

    /**
     * @return array
     */
    public function getArguments () {
        return $this->arguments;
    }

    /**
     * @param array $arguments
     *
     * @return BehatStep
     */
    public function setArguments ($arguments) {
        $this->arguments = $arguments;

        return $this;
    }

    /**
     * @return string
     */
    public function getKeyWord () {
        return $this->keyWord;
    }

    /**
     * @param string $keyWord
     *
     * @return BehatStep
     */
    public function setKeyWord ($keyWord) {
        $this->keyWord = $keyWord;

        return $this;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link  http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    function jsonSerialize () {
        return ['text' => $this->getText(),
                'keyword'=>$this->getKeyWord(),
                'arguments' => $this->getArguments()];
    }
}