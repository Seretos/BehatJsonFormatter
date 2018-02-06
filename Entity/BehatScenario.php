<?php
/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 04.02.18
 * Time: 20:22
 */

namespace seretos\BehatJsonFormatter\Entity;


use JsonSerializable;

class BehatScenario implements JsonSerializable{
    /**
     * @var BehatEnvironmentResult[]
     */
    private $environmentResults;
    /**
     * @var BehatStep[]
     */
    private $steps;

    public function __construct () {
        $this->environmentResults = [];
    }

    /**
     * @return BehatEnvironmentResult[]
     */
    public function getEnvironmentResults () {
        return $this->environmentResults;
    }

    /**
     * @param BehatEnvironmentResult[] $environmentResults
     *
     * @return BehatScenario
     */
    public function setEnvironmentResults ($environmentResults) {
        $this->environmentResults = $environmentResults;

        return $this;
    }

    /**
     * @param                        $environment
     * @param BehatEnvironmentResult $result
     *
     * @return $this
     */
    public function addEnvironmentResult($environment, BehatEnvironmentResult $result){
        $this->environmentResults[$environment] = $result;

        return $this;
    }

    /**
     * @param $environment
     *
     * @return BehatEnvironmentResult
     */
    public function getEnvironmentResult($environment){
        return $this->environmentResults[$environment];
    }

    /**
     * @return BehatStep[]
     */
    public function getSteps () {
        return $this->steps;
    }

    /**
     * @param BehatStep[] $steps
     *
     * @return BehatScenario
     */
    public function setSteps ($steps) {
        $this->steps = $steps;

        return $this;
    }

    /**
     * @param int $line
     * @param BehatStep $step
     *
     * @return int
     */
    public function setStep($line, BehatStep $step){
        $this->steps[$line] = $step;

        return count($this->steps)-1;
    }

    /**
     * @param $line
     *
     * @return BehatStep
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
        return ['steps' => $this->getSteps(),'results' => $this->getEnvironmentResults()];
    }
}