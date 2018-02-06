<?php
/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 04.02.18
 * Time: 20:07
 */

namespace seretos\BehatJsonFormatter\Entity;


use JsonSerializable;

class BehatFeature implements JsonSerializable{
    /**
     * @var string
     */
    private $title;
    /**
     * @var string
     */
    private $file;
    /**
     * @var string
     */
    private $description;
    /**
     * @var string
     */
    private $language;
    /**
     * @var BehatScenario[]
     */
    private $scenarios;

    public function __construct () {
        $this->scenarios = [];
    }

    /**
     * @return string
     */
    public function getTitle () {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return BehatFeature
     */
    public function setTitle ($title) {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getFile () {
        return $this->file;
    }

    /**
     * @param string $file
     *
     * @return BehatFeature
     */
    public function setFile ($file) {
        $this->file = $file;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription () {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return BehatFeature
     */
    public function setDescription ($description) {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getLanguage () {
        return $this->language;
    }

    /**
     * @param string $language
     *
     * @return BehatFeature
     */
    public function setLanguage ($language) {
        $this->language = $language;

        return $this;
    }

    /**
     * @return BehatScenario[]
     */
    public function getScenarios () {
        return $this->scenarios;
    }

    /**
     * @param BehatScenario[] $scenarios
     *
     * @return BehatFeature
     */
    public function setScenarios ($scenarios) {
        $this->scenarios = $scenarios;

        return $this;
    }

    /**
     * @param               $key
     * @param BehatScenario $scenario
     *
     * @return $this
     */
    public function setScenario($key, BehatScenario $scenario){
        $this->scenarios[$key] = $scenario;

        return $this;
    }

    /**
     * @param $key
     *
     * @return null|BehatScenario
     */
    public function getScenario($key){
        if(isset($this->scenarios[$key])) {
            return $this->scenarios[$key];
        }
        return null;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link  http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    function jsonSerialize () {
        return ['title' => $this->getTitle()
                ,'description'=>$this->getDescription()
                ,'language' => $this->getLanguage()
                ,'file'=>$this->getFile()
                ,'scenarios'=>$this->getScenarios()];
    }
}