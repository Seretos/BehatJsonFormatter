<?php
/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 04.02.18
 * Time: 21:39
 */

namespace seretos\BehatJsonFormatter\Entity;


use JsonSerializable;

class BehatEnvironmentResult implements JsonSerializable{
    /**
     * @var boolean
     */
    private $passed;
    /**
     * @var BehatEnvironmentStepResult[]
     */
    private $steps;

    /**
     * @return bool
     */
    public function isPassed () {
        return $this->passed;
    }

    /**
     * @param bool $passed
     *
     * @return BehatEnvironmentResult
     */
    public function setPassed ($passed) {
        $this->passed = $passed;

        return $this;
    }

    /**
     * @return BehatEnvironmentStepResult[]
     */
    public function getSteps () {
        return $this->steps;
    }

    /**
     * @param BehatEnvironmentStepResult[] $steps
     *
     * @return BehatEnvironmentResult
     */
    public function setSteps ($steps) {
        $this->steps = $steps;

        return $this;
    }

    /**
     * @param int $line
     * @param BehatEnvironmentStepResult $step
     * @return BehatEnvironmentResult
     */
    public function setStep($line, BehatEnvironmentStepResult $step){
        $this->steps[$line] = $step;
        return $this;
    }

    /**
     * @param $line
     *
     * @return BehatEnvironmentStepResult
     */
    public function getStep($line){
        return $this->steps[$line];
    }

    /**
     * Specify data which should be serialized to JSON
     * @link  http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    function jsonSerialize () {
        return ['passed' =>$this->isPassed(),'steps' => $this->getSteps()];
    }
}