<?php
/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 05.02.18
 * Time: 12:26
 */

namespace seretos\BehatJsonFormatter\Entity;


use JsonSerializable;

class BehatEnvironmentStepResult implements JsonSerializable{
    /**
     * @var boolean
     */
    private $passed;
    /**
     * @var string
     */
    private $screenshot;

    /**
     * @return bool
     */
    public function isPassed () {
        return $this->passed;
    }

    /**
     * @param bool $passed
     *
     * @return BehatEnvironmentStepResult
     */
    public function setPassed ($passed) {
        $this->passed = $passed;

        return $this;
    }

    /**
     * @return string
     */
    public function getScreenshot () {
        return $this->screenshot;
    }

    /**
     * @param string $screenshot
     *
     * @return BehatEnvironmentStepResult
     */
    public function setScreenshot ($screenshot) {
        $this->screenshot = $screenshot;

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
        return ['passed' => $this->isPassed(),'screenshot' => $this->getScreenshot()];
    }
}