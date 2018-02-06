<?php
/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 04.02.18
 * Time: 04:03
 */

namespace seretos\BehatJsonFormatter\Formatter;

use Behat\Behat\EventDispatcher\Event\AfterOutlineTested;
use Behat\Behat\EventDispatcher\Event\AfterScenarioTested;
use Behat\Behat\EventDispatcher\Event\AfterStepTested;
use Behat\Behat\EventDispatcher\Event\BeforeFeatureTested;
use Behat\Behat\EventDispatcher\Event\BeforeOutlineTested;
use Behat\Behat\EventDispatcher\Event\BeforeScenarioTested;
use Behat\Gherkin\Node\BackgroundNode;
use Behat\Gherkin\Node\ScenarioInterface;
use Behat\Gherkin\Node\StepNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Driver\Selenium2Driver;
use Behat\Mink\Mink;
use Behat\Testwork\EventDispatcher\Event\AfterSuiteTested;
use Behat\Testwork\EventDispatcher\Event\BeforeSuiteTested;
use Behat\Testwork\Output\Formatter;
use Behat\Testwork\Output\Printer\OutputPrinter;
use seretos\BehatJsonFormatter\Entity\BehatEnvironmentResult;
use seretos\BehatJsonFormatter\Entity\BehatEnvironmentStepResult;
use seretos\BehatJsonFormatter\Entity\BehatFeature;
use seretos\BehatJsonFormatter\Entity\BehatScenario;
use seretos\BehatJsonFormatter\Entity\BehatStep;
use seretos\BehatJsonFormatter\Entity\BehatSuite;
use seretos\BehatJsonFormatter\Printer\FileOutputPrinter;
use seretos\BehatJsonFormatter\Printer\ScreenshotPrinter;

class BehatJsonFormatter implements Formatter {
    /**
     * @var FileOutputPrinter
     */
    private $printer;
    /**
     * @var ScreenshotPrinter
     */
    private $screenshotPrinter;
    /**
     * @var BehatSuite
     */
    private $currentSuite;
    /**
     * @var BehatScenario
     */
    private $currentScenario;
    /**
     * @var string
     */
    private $browser;
    /**
     * @var string
     */
    private $output;
    /**
     * @var BehatEnvironmentStepResult[]
     */
    private $stepResults;
    /**
     * @var Mink
     */
    private $mink;
    /**
     * @var boolean
     */
    private $stepScreenshots;

    public function __construct (Mink $mink,
                                 FileOutputPrinter $printer,
                                 ScreenshotPrinter $screenshotPrinter,
                                 $parameters,
                                 $stepScreenshots,
                                 $output) {
        $this->printer = $printer;
        $this->screenshotPrinter = $screenshotPrinter;
        $this->browser = $parameters['browser_name'];
        $this->output = rtrim($output, '/').'/';
        $this->mink = $mink;
        $this->stepScreenshots = $stepScreenshots;
    }

    /**
     * @param BeforeSuiteTested $event
     */
    public function onBeforeSuiteTested(BeforeSuiteTested $event) {
        $this->currentSuite = new BehatSuite();
        $this->currentSuite->setName($event->getSuite()->getName());
    }

    /**
     * @param AfterSuiteTested $event
     */
    public function onAfterSuiteTested(AfterSuiteTested $event) {
        if($event !== null) {
            $file = $this->currentSuite->getName().'.json';
            $this->printer->setOutputPath($this->output.$file);
            $this->printer->write(json_encode($this->currentSuite, JSON_PRETTY_PRINT));
        }
    }

    /**
     * @param BeforeFeatureTested $event
     */
    public function onBeforeFeatureTested(BeforeFeatureTested $event) {
        $feature = new BehatFeature();
        $feature->setTitle($event->getFeature()->getTitle())
                ->setDescription($event->getFeature()->getDescription())
                ->setFile($event->getFeature()->getFile())
                ->setLanguage($event->getFeature()->getLanguage());
        $this->currentSuite->setFeature($event->getFeature()->getFile(), $feature);
    }

    /**
     * @param BeforeScenarioTested $event
     */
    public function onBeforeScenarioTested(BeforeScenarioTested $event) {
        $scenario = new BehatScenario();
        $result = new BehatEnvironmentResult();
        $this->stepResults = [];
        $scenario->addEnvironmentResult($this->getEnvironment(),
                                        $result);
        $feature = $this->currentSuite->getFeature($event->getFeature()->getFile());
        $this->importSteps($scenario,
                           $event->getScenario(),
                           $event->getFeature()->getBackground());
        $feature->setScenario($event->getScenario()->getTitle(), $scenario);
        $this->currentScenario = $scenario;
    }

    /**
     * @param AfterScenarioTested $event
     */
    public function onAfterScenarioTested(AfterScenarioTested $event) {
        $scenario = $this->currentSuite->getFeature($event->getFeature()->getFile())
                                       ->getScenario($event->getScenario()->getTitle());
        $scenario->getEnvironmentResult($this->getEnvironment())
                 ->setPassed($event->getTestResult()->isPassed())
                 ->setSteps($this->stepResults);
    }

    /**
     * @param AfterStepTested $event
     */
    public function onAfterStepTested(AfterStepTested $event) {
        $event->getStep()->getLine();
        $stepResult = new BehatEnvironmentStepResult();
        $stepResult->setPassed($event->getTestResult()->isPassed());
        if(($this->stepScreenshots ||
            (!$event->getTestResult()->isPassed()
             && $event->getTestResult()->getResultCode() == 99)
           ) && $this->mink->getSession()->getDriver() instanceof Selenium2Driver){
            $screenshot = $this->mink->getSession()->getScreenshot();
            $file = $this->screenshotPrinter->takeScreenshot($this->output,$this->browser,$screenshot);
            $stepResult->setScreenshot($file);
        }
        $this->stepResults[$event->getStep()->getLine()] = $stepResult;
    }

    /**
     * @param BeforeOutlineTested $event
     */
    public function onBeforeOutlineTested(BeforeOutlineTested $event) {
        $scenario = new BehatScenario();

        $this->stepResults = [];
        $feature = $this->currentSuite->getFeature($event->getFeature()->getFile());
        $this->importSteps($scenario,
                           $event->getOutline(),
                           $event->getFeature()->getBackground());
        $feature->setScenario($event->getOutline()->getTitle(),$scenario);
        $this->currentScenario = $scenario;
    }

    /**
     * @param AfterOutlineTested $event
     */
    public function onAfterOutlineTested(AfterOutlineTested $event) {
        $result = new BehatEnvironmentResult();
        $result->setPassed($event->getTestResult()->isPassed());
        $this->currentScenario->addEnvironmentResult($this->getEnvironment(),
                                                     $result);
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2')))
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents () {
        return array(
//            'tester.exercise_completed.before' => 'onBeforeExercise',
//            'tester.exercise_completed.after' => 'onAfterExercise',
            'tester.suite_tested.before' => 'onBeforeSuiteTested',
            'tester.suite_tested.after' => 'onAfterSuiteTested',
            'tester.feature_tested.before' => 'onBeforeFeatureTested',
//            'tester.feature_tested.after' => 'onAfterFeatureTested',
            'tester.scenario_tested.before' => 'onBeforeScenarioTested',
//            'tester.scenario_tested.after' => 'onAfterScenarioTested',
            'tester.outline_tested.before' => 'onBeforeOutlineTested',
            'tester.outline_tested.after' => 'onAfterOutlineTested',
            'tester.step_tested.after' => 'onAfterStepTested',
        );
    }

    private function importSteps(BehatScenario $scenario, ScenarioInterface $scenarioNode, BackgroundNode $backgroundNode = null){
        if($backgroundNode!==null){
            foreach($backgroundNode->getSteps() as $step){
                $scenario->setStep($step->getLine(), $this->convertStep($step));
            }
        }
        foreach($scenarioNode->getSteps() as $step){
            $scenario->setStep($step->getLine(), $this->convertStep($step));
        }
    }

    private function convertStep(StepNode $step){
        $importStep = new BehatStep();
        $importStep->setText($step->getText())
                   ->setKeyWord($step->getKeyword());
        foreach ($step->getArguments() as $argument){
            if($argument instanceof TableNode){
                $importStep->setArguments($argument->getRows());
            }
        }
        return $importStep;
    }

    private function getEnvironment(){
        $browser = $this->browser;
        if(!$this->mink->getSession()->getDriver() instanceof Selenium2Driver){
            $browser = 'unknown';
        }
        return $browser;
    }

    /**
     * Returns formatter name.
     *
     * @return string
     */
    public function getName () {
        return "json";
    }

    /**
     * Returns formatter description.
     *
     * @return string
     */
    public function getDescription () {
        return "Formatter for json";
    }

    /**
     * Returns formatter output printer.
     *
     * @return OutputPrinter
     */
    public function getOutputPrinter () {
        return $this->printer;
    }

    /**
     * Sets formatter parameter.
     *
     * @param string $name
     * @param mixed  $value
     */
    public function setParameter ($name, $value) {
    }

    /**
     * Returns parameter name.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getParameter ($name) {
        return [];
    }
}