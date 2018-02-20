<?php
use Behat\Behat\EventDispatcher\Event\AfterFeatureTested;
use Behat\Behat\EventDispatcher\Event\AfterOutlineTested;
use Behat\Behat\EventDispatcher\Event\AfterScenarioTested;
use Behat\Behat\EventDispatcher\Event\AfterStepTested;
use Behat\Behat\EventDispatcher\Event\BeforeFeatureTested;
use Behat\Behat\EventDispatcher\Event\BeforeOutlineTested;
use Behat\Behat\EventDispatcher\Event\BeforeScenarioTested;
use Behat\Behat\EventDispatcher\Event\BeforeStepTested;
use Behat\Behat\Tester\Result\StepResult;
use Behat\Gherkin\Node\BackgroundNode;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\OutlineNode;
use Behat\Gherkin\Node\ScenarioInterface;
use Behat\Gherkin\Node\StepNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Driver\Selenium2Driver;
use Behat\Mink\Mink;
use Behat\Mink\Session;
use Behat\Testwork\Environment\Environment;
use Behat\Testwork\EventDispatcher\Event\AfterSuiteTested;
use Behat\Testwork\EventDispatcher\Event\BeforeSuiteTested;
use Behat\Testwork\Specification\SpecificationIterator;
use Behat\Testwork\Suite\Suite;
use Behat\Testwork\Tester\Result\TestResult;
use Behat\Testwork\Tester\Setup\Teardown;
use PHPUnit\Framework\TestCase;
use seretos\BehatJsonFormatter\Formatter\BehatJsonFormatter;
use seretos\BehatJsonFormatter\Printer\FileOutputPrinter;
use seretos\BehatJsonFormatter\Printer\ScreenshotPrinter;

/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 06.02.18
 * Time: 10:57
 */
class BehatJsonFormatterTest extends TestCase {
    /**
     * @var Mink|PHPUnit_Framework_MockObject_MockObject
     */
    private $mockMink;
    /**
     * @var Session|PHPUnit_Framework_MockObject_MockObject
     */
    private $minkSession;
    /**
     * @var FileOutputPrinter|PHPUnit_Framework_MockObject_MockObject
     */
    private $mockPrinter;
    /**
     * @var ScreenshotPrinter|PHPUnit_Framework_MockObject_MockObject
     */
    private $mockScreenshotPrinter;

    protected function setUp(){
        parent::setUp();

        $this->mockMink = $this->getMockBuilder(Mink::class)
                               ->disableOriginalConstructor()
                               ->getMock();
        $this->minkSession = $this->getMockBuilder(Session::class)
                                  ->disableOriginalConstructor()
                                  ->getMock();
        $this->mockPrinter = $this->getMockBuilder(FileOutputPrinter::class)
                                  ->disableOriginalConstructor()
                                  ->getMock();
        $this->mockScreenshotPrinter = $this->getMockBuilder(ScreenshotPrinter::class)
                                            ->disableOriginalConstructor()
                                            ->getMock();

        $this->mockMink->expects($this->any())
                       ->method('getSession')
                       ->will($this->returnValue($this->minkSession));
    }

    /**
     * @test
     */
    public function getSubscribedEvents(){
        $this->assertSame(['tester.suite_tested.before' => 'onBeforeSuiteTested'
                           ,'tester.suite_tested.after' => 'onAfterSuiteTested'
                           ,'tester.feature_tested.before' => 'onBeforeFeatureTested'
                           ,'tester.scenario_tested.before' => 'onBeforeScenarioTested'
                           ,'tester.scenario_tested.after' => 'onAfterScenarioTested'
                           ,'tester.outline_tested.before' => 'onBeforeOutlineTested'
                           ,'tester.outline_tested.after' => 'onAfterOutlineTested'
                           ,'tester.step_tested.after' => 'onAfterStepTested'],BehatJsonFormatter::getSubscribedEvents());
    }

    /**
     * @test
     */
    public function getName_test(){
        $formatter = new BehatJsonFormatter($this->mockMink,
                                            $this->mockPrinter,
                                            $this->mockScreenshotPrinter,
                                            ['browser_name'=>'firefly'],
                                            false,
                                            '/my/dir');
        $this->assertSame('json',$formatter->getName());
    }

    /**
     * @test
     */
    public function getDescription_test(){
        $formatter = new BehatJsonFormatter($this->mockMink,
                                            $this->mockPrinter,
                                            $this->mockScreenshotPrinter,
                                            ['browser_name'=>'firefly'],
                                            false,
                                            '/my/dir');
        $this->assertSame('Formatter for json',$formatter->getDescription());
    }

    /**
     * @test
     */
    public function getOutputPrinter_test(){
        $formatter = new BehatJsonFormatter($this->mockMink,
                                            $this->mockPrinter,
                                            $this->mockScreenshotPrinter,
                                            ['browser_name'=>'firefly'],
                                            false,
                                            '/my/dir');
        $this->assertSame($this->mockPrinter,$formatter->getOutputPrinter());
    }

    /**
     * @test
     */
    public function setParameter_test(){
        $formatter = new BehatJsonFormatter($this->mockMink,
                                            $this->mockPrinter,
                                            $this->mockScreenshotPrinter,
                                            ['browser_name'=>'firefly'],
                                            false,
                                            '/my/dir');
        $formatter->setParameter('test','val');
    }

    /**
     * @test
     */
    public function getParameter_test(){
        $formatter = new BehatJsonFormatter($this->mockMink,
                                            $this->mockPrinter,
                                            $this->mockScreenshotPrinter,
                                            ['browser_name'=>'firefly'],
                                            false,
                                            '/my/dir');
        $this->assertSame([],$formatter->getParameter('test'));
    }

    /**
     * @test
     */
    public function format_suite_test(){
        $formatter = new BehatJsonFormatter($this->mockMink,
                                            $this->mockPrinter,
                                            $this->mockScreenshotPrinter,
                                            ['browser_name'=>'firefly'],
                                            false,
                                            '/my/dir');

        $suiteEvents = $this->getSuiteEvents('mySuite');

        $formatter->onBeforeSuiteTested($suiteEvents['before']);

        $this->mockPrinter->expects($this->at(0))
                          ->method('setOutputPath')
                          ->with('/my/dir/mySuite.json');

        $this->mockPrinter->expects($this->once())
                          ->method('write')
                          ->with(json_encode(['name'=>'mySuite','features'=>[]], JSON_PRETTY_PRINT));

        $formatter->onAfterSuiteTested($suiteEvents['after']);
    }

    /**
     * @test
     */
    public function format_feature_test(){
        $formatter = new BehatJsonFormatter($this->mockMink,
                                            $this->mockPrinter,
                                            $this->mockScreenshotPrinter,
                                            ['browser_name'=>'firefly'],
                                            false,
                                            '/my/dir');

        $suiteEvents = $this->getSuiteEvents('mySuite');
        $featureEvents = $this->getFeatureEvents('myTitle',
                                                 'myDescription',
                                                 '/my/file.feature',
                                                 'en');

        $formatter->onBeforeSuiteTested($suiteEvents['before']);
        $formatter->onBeforeFeatureTested($featureEvents['before']);

        $this->mockPrinter->expects($this->at(0))
                          ->method('setOutputPath')
                          ->with('/my/dir/mySuite.json');

        $this->mockPrinter->expects($this->once())
                          ->method('write')
                          ->with(json_encode(['name'=>'mySuite',
                                              'features'=>['/my/file.feature' => ['title' => 'myTitle',
                                                                                  'description'=>'myDescription',
                                                                                  'language' => 'en',
                                                                                  'file' => '/my/file.feature'
                                                                                  ,'scenarios'=>[]]
                                              ]], JSON_PRETTY_PRINT));

        $formatter->onAfterSuiteTested($suiteEvents['after']);
    }

    /**
     * @test
     */
    public function format_scenario_test(){
        $formatter = new BehatJsonFormatter($this->mockMink,
                                            $this->mockPrinter,
                                            $this->mockScreenshotPrinter,
                                            ['browser_name'=>'firefly'],
                                            false,
                                            '/my/dir');

        $suiteEvents = $this->getSuiteEvents('mySuite');
        $featureEvents = $this->getFeatureEvents('myTitle',
                                                 'myDescription',
                                                 '/my/file.feature',
                                                 'en');
        $scenarioEvents = $this->getScenarioEvents('myScenario',
                                                   'myTitle',
                                                   '/my/file.feature',
                                                   true);

        $mockSeleniumDriver = $this->getMockBuilder(Selenium2Driver::class)
                                   ->disableOriginalConstructor()
                                   ->getMock();

        $reflection = new \ReflectionClass(Selenium2Driver::class);
        $property = $reflection->getProperty('desiredCapabilities');
        $property->setAccessible(true);
        $property->setValue($mockSeleniumDriver,['browser' => 'firefly']);

        $this->minkSession->expects($this->any())
                          ->method('getDriver')
                          ->will($this->returnValue($mockSeleniumDriver));

        $formatter->onBeforeSuiteTested($suiteEvents['before']);
        $formatter->onBeforeFeatureTested($featureEvents['before']);
        $formatter->onBeforeScenarioTested($scenarioEvents['before']);
        $formatter->onAfterScenarioTested($scenarioEvents['after']);

        $this->mockPrinter->expects($this->at(0))
                          ->method('setOutputPath')
                          ->with('/my/dir/mySuite.json');

        $this->mockPrinter->expects($this->once())
                          ->method('write')
                          ->with(json_encode(['name'=>'mySuite',
                                              'features'=>['/my/file.feature' => ['title' => 'myTitle',
                                                                                  'description'=>'myDescription',
                                                                                  'language' => 'en',
                                                                                  'file' => '/my/file.feature'
                                                                                  ,'scenarios'=>['myScenario' => ['steps' => null,
                                                                                                                  'results' => ['firefly' => ['passed' => true,
                                                                                                                                              'steps' => []]]]]]
                                              ]], JSON_PRETTY_PRINT));

        $formatter->onAfterSuiteTested($suiteEvents['after']);
    }

    /**
     * @test
     */
    public function format_scenario_withSteps_test(){
        $formatter = new BehatJsonFormatter($this->mockMink,
                                            $this->mockPrinter,
                                            $this->mockScreenshotPrinter,
                                            ['browser_name'=>'firefly'],
                                            false,
                                            '/my/dir');

        $suiteEvents = $this->getSuiteEvents('mySuite');
        $featureEvents = $this->getFeatureEvents('myTitle',
                                                 'myDescription',
                                                 '/my/file.feature',
                                                 'en');
        $scenarioEvents = $this->getScenarioEvents('myScenario',
                                                   'myTitle',
                                                   '/my/file.feature',
                                                   true,
                                                   [$this->getStep(3,'test3','when'),
                                                    $this->getStep(4,'test4','then',[['test3','test4']])],
                                                   [$this->getStep(1,'test','when'),
                                                    $this->getStep(2,'test2','then',[['test1','test2']])]);

        $mockSeleniumDriver = $this->getMockBuilder(Selenium2Driver::class)
                                   ->disableOriginalConstructor()
                                   ->getMock();

        $reflection = new \ReflectionClass(Selenium2Driver::class);
        $property = $reflection->getProperty('desiredCapabilities');
        $property->setAccessible(true);
        $property->setValue($mockSeleniumDriver,['browser' => 'firefly']);

        $this->minkSession->expects($this->any())
                          ->method('getDriver')
                          ->will($this->returnValue($mockSeleniumDriver));

        $formatter->onBeforeSuiteTested($suiteEvents['before']);
        $formatter->onBeforeFeatureTested($featureEvents['before']);
        $formatter->onBeforeScenarioTested($scenarioEvents['before']);
        $formatter->onAfterScenarioTested($scenarioEvents['after']);

        $this->mockPrinter->expects($this->at(0))
                          ->method('setOutputPath')
                          ->with('/my/dir/mySuite.json');

        $this->mockPrinter->expects($this->once())
                          ->method('write')
                          ->with(json_encode(['name'=>'mySuite',
                                              'features'=>['/my/file.feature' => ['title' => 'myTitle',
                                                                                  'description'=>'myDescription',
                                                                                  'language' => 'en',
                                                                                  'file' => '/my/file.feature'
                                                                                  ,'scenarios'=>['myScenario' => ['steps' => ['1' => ['text' => 'test','keyword' => 'when','arguments'=>null],
                                                                                                                              '2' => ['text' => 'test2','keyword' => 'then','arguments'=>[['test1','test2']]],
                                                                                                                              '3' => ['text' => 'test3','keyword' => 'when','arguments'=>null],
                                                                                                                              '4' => ['text' => 'test4','keyword' => 'then','arguments'=>[['test3','test4']]]],
                                                                                                                  'results' => ['firefly' => ['passed' => true,
                                                                                                                                              'steps' => []]]]]]
                                              ]], JSON_PRETTY_PRINT));

        $formatter->onAfterSuiteTested($suiteEvents['after']);
    }

    /**
     * @test
     */
    public function format_scenario_withoutSelenium_test(){
        $formatter = new BehatJsonFormatter($this->mockMink,
                                            $this->mockPrinter,
                                            $this->mockScreenshotPrinter,
                                            ['browser_name'=>'firefly'],
                                            false,
                                            '/my/dir');

        $suiteEvents = $this->getSuiteEvents('mySuite');
        $featureEvents = $this->getFeatureEvents('myTitle',
                                                 'myDescription',
                                                 '/my/file.feature',
                                                 'en');
        $scenarioEvents = $this->getScenarioEvents('myScenario',
                                                   'myTitle',
                                                   '/my/file.feature',
                                                   true);

        $this->minkSession->expects($this->any())
                          ->method('getDriver')
                          ->will($this->returnValue(null));

        $formatter->onBeforeSuiteTested($suiteEvents['before']);
        $formatter->onBeforeFeatureTested($featureEvents['before']);
        $formatter->onBeforeScenarioTested($scenarioEvents['before']);
        $formatter->onAfterScenarioTested($scenarioEvents['after']);

        $this->mockPrinter->expects($this->at(0))
                          ->method('setOutputPath')
                          ->with('/my/dir/mySuite.json');

        $this->mockPrinter->expects($this->once())
                          ->method('write')
                          ->with(json_encode(['name'=>'mySuite',
                                              'features'=>['/my/file.feature' => ['title' => 'myTitle',
                                                                                  'description'=>'myDescription',
                                                                                  'language' => 'en',
                                                                                  'file' => '/my/file.feature'
                                                                                  ,'scenarios'=>['myScenario' => ['steps' => null,
                                                                                                                  'results' => ['unknown' => ['passed' => true,
                                                                                                                                              'steps' => []]]]]]
                                              ]], JSON_PRETTY_PRINT));

        $formatter->onAfterSuiteTested($suiteEvents['after']);
    }

    /**
     * @test
     */
    public function format_step_test(){
        $formatter = new BehatJsonFormatter($this->mockMink,
                                            $this->mockPrinter,
                                            $this->mockScreenshotPrinter,
                                            ['browser_name'=>'firefly'],
                                            false,
                                            '/my/dir');

        $suiteEvents = $this->getSuiteEvents('mySuite');
        $featureEvents = $this->getFeatureEvents('myTitle',
                                                 'myDescription',
                                                 '/my/file.feature',
                                                 'en');
        $scenarioEvents = $this->getScenarioEvents('myScenario',
                                                   'myTitle',
                                                   '/my/file.feature',
                                                   true);
        $stepEvents = $this->getStepEvents(1,true,0);

        $mockSeleniumDriver = $this->getMockBuilder(Selenium2Driver::class)
                                   ->disableOriginalConstructor()
                                   ->getMock();

        $reflection = new \ReflectionClass(Selenium2Driver::class);
        $property = $reflection->getProperty('desiredCapabilities');
        $property->setAccessible(true);
        $property->setValue($mockSeleniumDriver,['browser' => 'firefly']);

        $this->minkSession->expects($this->any())
                          ->method('getDriver')
                          ->will($this->returnValue($mockSeleniumDriver));

        $formatter->onBeforeSuiteTested($suiteEvents['before']);
        $formatter->onBeforeFeatureTested($featureEvents['before']);
        $formatter->onBeforeScenarioTested($scenarioEvents['before']);
        $formatter->onAfterStepTested($stepEvents['after']);
        $formatter->onAfterScenarioTested($scenarioEvents['after']);

        $this->mockPrinter->expects($this->at(0))
                          ->method('setOutputPath')
                          ->with('/my/dir/mySuite.json');

        $this->mockPrinter->expects($this->once())
                          ->method('write')
                          ->with(json_encode(['name'=>'mySuite',
                                              'features'=>['/my/file.feature' => ['title' => 'myTitle',
                                                                                  'description'=>'myDescription',
                                                                                  'language' => 'en',
                                                                                  'file' => '/my/file.feature'
                                                                                  ,'scenarios'=>['myScenario' => ['steps' => null,
                                                                                                                  'results' => ['firefly' => ['passed' => true,
                                                                                                                                              'steps' => [ 1 => ['passed' => true,
                                                                                                                                                                 'screenshot' => null]]]]]]]
                                              ]], JSON_PRETTY_PRINT));

        $formatter->onAfterSuiteTested($suiteEvents['after']);
    }

    /**
     * @test
     */
    public function format_step_withScreenshot_test(){
        $formatter = new BehatJsonFormatter($this->mockMink,
                                            $this->mockPrinter,
                                            $this->mockScreenshotPrinter,
                                            ['browser_name'=>'firefly'],
                                            false,
                                            '/my/dir');

        $suiteEvents = $this->getSuiteEvents('mySuite');
        $featureEvents = $this->getFeatureEvents('myTitle',
                                                 'myDescription',
                                                 '/my/file.feature',
                                                 'en');
        $scenarioEvents = $this->getScenarioEvents('myScenario',
                                                   'myTitle',
                                                   '/my/file.feature',
                                                   false);
        $stepEvents = $this->getStepEvents(1,false,99);

        $mockSeleniumDriver = $this->getMockBuilder(Selenium2Driver::class)
                                   ->disableOriginalConstructor()
                                   ->getMock();

        $reflection = new \ReflectionClass(Selenium2Driver::class);
        $property = $reflection->getProperty('desiredCapabilities');
        $property->setAccessible(true);
        $property->setValue($mockSeleniumDriver,['browser' => 'firefly']);

        $this->minkSession->expects($this->any())
                          ->method('getDriver')
                          ->will($this->returnValue($mockSeleniumDriver));

        $this->minkSession->expects($this->any())
                          ->method('getScreenshot')
                          ->will($this->returnValue('myScreenshot'));

        $this->mockScreenshotPrinter->expects($this->once())
                                    ->method('takeScreenshot')
                                    ->with('/my/dir/','firefly','myScreenshot')
                                    ->will($this->returnValue('myImage.png'));

        $formatter->onBeforeSuiteTested($suiteEvents['before']);
        $formatter->onBeforeFeatureTested($featureEvents['before']);
        $formatter->onBeforeScenarioTested($scenarioEvents['before']);
        $formatter->onAfterStepTested($stepEvents['after']);
        $formatter->onAfterScenarioTested($scenarioEvents['after']);

        $this->mockPrinter->expects($this->at(0))
                          ->method('setOutputPath')
                          ->with('/my/dir/mySuite.json');

        $this->mockPrinter->expects($this->once())
                          ->method('write')
                          ->with(json_encode(['name'=>'mySuite',
                                              'features'=>['/my/file.feature' => ['title' => 'myTitle',
                                                                                  'description'=>'myDescription',
                                                                                  'language' => 'en',
                                                                                  'file' => '/my/file.feature'
                                                                                  ,'scenarios'=>['myScenario' => ['steps' => null,
                                                                                                                  'results' => ['firefly' => ['passed' => false,
                                                                                                                                              'steps' => [ 1 => ['passed' => false,
                                                                                                                                                                 'screenshot' => 'myImage.png']]]]]]]
                                              ]], JSON_PRETTY_PRINT));

        $formatter->onAfterSuiteTested($suiteEvents['after']);
    }

    /**
     * @test
     */
    public function format_outline_test(){
        $formatter = new BehatJsonFormatter($this->mockMink,
                                            $this->mockPrinter,
                                            $this->mockScreenshotPrinter,
                                            ['browser_name'=>'firefly'],
                                            false,
                                            '/my/dir');

        $suiteEvents = $this->getSuiteEvents('mySuite');
        $featureEvents = $this->getFeatureEvents('myTitle',
                                                 'myDescription',
                                                 '/my/file.feature',
                                                 'en');
        $scenarioEvents = $this->getScenarioEvents('myScenario',
                                                   'myTitle',
                                                   '/my/file.feature',
                                                   true);
        $stepEvents = $this->getStepEvents(1,true,0);
        $outlineEvents = $this->getOutlineEvents('myOutlineTitle','/my/file.feature', true);

        $mockSeleniumDriver = $this->getMockBuilder(Selenium2Driver::class)
                                   ->disableOriginalConstructor()
                                   ->getMock();

        $reflection = new \ReflectionClass(Selenium2Driver::class);
        $property = $reflection->getProperty('desiredCapabilities');
        $property->setAccessible(true);
        $property->setValue($mockSeleniumDriver,['browser' => 'firefly']);

        $this->minkSession->expects($this->any())
                          ->method('getDriver')
                          ->will($this->returnValue($mockSeleniumDriver));

        $formatter->onBeforeSuiteTested($suiteEvents['before']);
        $formatter->onBeforeFeatureTested($featureEvents['before']);
        $formatter->onBeforeOutlineTested($outlineEvents['before']);
        $formatter->onAfterOutlineTested($outlineEvents['after']);
        $formatter->onBeforeScenarioTested($scenarioEvents['before']);
        $formatter->onAfterStepTested($stepEvents['after']);
        $formatter->onAfterScenarioTested($scenarioEvents['after']);

        $this->mockPrinter->expects($this->at(0))
                          ->method('setOutputPath')
                          ->with('/my/dir/mySuite.json');

        $this->mockPrinter->expects($this->once())
                          ->method('write')
                          ->with(json_encode(['name'=>'mySuite',
                                              'features'=>['/my/file.feature' => ['title' => 'myTitle',
                                                                                  'description'=>'myDescription',
                                                                                  'language' => 'en',
                                                                                  'file' => '/my/file.feature'
                                                                                  ,'scenarios'=>['myOutlineTitle' => ['steps' => null,
                                                                                                                      'results' => ['firefly' => ['passed' => true,
                                                                                                                                                  'steps' => null]]],
                                                                                                 'myScenario' => ['steps' => null,
                                                                                                                  'results' => ['firefly' => ['passed' => true,
                                                                                                                                              'steps' => [ 1 => ['passed' => true,
                                                                                                                                                                 'screenshot' => null]]]]]]]
                                              ]], JSON_PRETTY_PRINT));

        $formatter->onAfterSuiteTested($suiteEvents['after']);
    }

    private function getStep($line, $text, $keyWord, array $table = []){
        $mockStep = $this->getMockBuilder(StepNode::class)
                         ->disableOriginalConstructor()
                         ->getMock();

        $mockStep->expects($this->any())
                 ->method('getLine')
                 ->will($this->returnValue($line));
        $mockStep->expects($this->any())
                 ->method('getText')
                 ->will($this->returnValue($text));
        $mockStep->expects($this->any())
                 ->method('getKeyword')
                 ->will($this->returnValue($keyWord));

        if(count($table) > 0){
            $mockTableNode = $this->getMockBuilder(TableNode::class)
                                  ->disableOriginalConstructor()
                                  ->getMock();
            $mockTableNode->expects($this->any())
                          ->method('getRows')
                          ->will($this->returnValue($table));

            $mockStep->expects($this->any())
                     ->method('getArguments')
                     ->will($this->returnValue([$mockTableNode]));
        }else{
            $mockStep->expects($this->any())
                     ->method('getArguments')
                     ->will($this->returnValue([]));
        }

        return $mockStep;
    }

    private function getOutlineEvents($title, $featureFile, $passed){
        /**
         * @var $mockOutlineEnvironment Environment|PHPUnit_Framework_MockObject_MockObject
         * @var $mockFeatureNode FeatureNode|PHPUnit_Framework_MockObject_MockObject
         * @var $mockOutlineNode OutlineNode|PHPUnit_Framework_MockObject_MockObject
         * @var $mockOutlineTestResult TestResult|PHPUnit_Framework_MockObject_MockObject
         * @var $mockOutlineTearDown Teardown|PHPUnit_Framework_MockObject_MockObject
         */
        $mockOutlineEnvironment = $this->getMockBuilder(Environment::class)
                                    ->disableOriginalConstructor()
                                    ->getMock();
        $mockFeatureNode = $this->getMockBuilder(FeatureNode::class)
                                ->disableOriginalConstructor()
                                ->getMock();
        $mockOutlineNode = $this->getMockBuilder(OutlineNode::class)
                                ->disableOriginalConstructor()
                                ->getMock();
        $mockOutlineTestResult = $this->getMockBuilder(TestResult::class)
                                   ->disableOriginalConstructor()
                                   ->getMock();
        $mockOutlineTearDown = $this->getMockBuilder(Teardown::class)
                                 ->disableOriginalConstructor()
                                 ->getMock();
        $mockBackgroundNode = $this->getMockBuilder(BackgroundNode::class)
                                   ->disableOriginalConstructor()
                                   ->getMock();

        $mockOutlineNode->expects($this->any())
                        ->method('getTitle')
                        ->will($this->returnValue($title));

        $mockFeatureNode->expects($this->any())
                        ->method('getFile')
                        ->will($this->returnValue($featureFile));
        $mockFeatureNode->expects($this->any())
                        ->method('getBackground')
                        ->will($this->returnValue($mockBackgroundNode));

        $mockBackgroundNode->expects($this->any())
                           ->method('getSteps')
                           ->will($this->returnValue([]));

        $mockOutlineNode->expects($this->any())
                        ->method('getSteps')
                        ->will($this->returnValue([]));

        $mockOutlineTestResult->expects($this->any())
                              ->method('isPassed')
                              ->will($this->returnValue($passed));

        $mockBeforeOutlineEvent = new BeforeOutlineTested($mockOutlineEnvironment, $mockFeatureNode, $mockOutlineNode);
        $mockAfterOutlineEvent = new AfterOutlineTested($mockOutlineEnvironment,
                                                        $mockFeatureNode,
                                                        $mockOutlineNode,
                                                        $mockOutlineTestResult,
                                                        $mockOutlineTearDown);

        return ['before' => $mockBeforeOutlineEvent,'after' => $mockAfterOutlineEvent];
    }

    private function getStepEvents($line, $passed, $resultCode){
        /**
         * @var $mockStepEnvironment Environment|PHPUnit_Framework_MockObject_MockObject
         * @var $mockFeatureNode FeatureNode|PHPUnit_Framework_MockObject_MockObject
         * @var $mockStepNode StepNode|PHPUnit_Framework_MockObject_MockObject
         * @var $mockStepTestResult StepResult|PHPUnit_Framework_MockObject_MockObject
         * @var $mockStepTearDown Teardown|PHPUnit_Framework_MockObject_MockObject
         */
        $mockStepEnvironment = $this->getMockBuilder(Environment::class)
                                        ->disableOriginalConstructor()
                                        ->getMock();
        $mockFeatureNode = $this->getMockBuilder(FeatureNode::class)
                                ->disableOriginalConstructor()
                                ->getMock();
        $mockStepNode = $this->getMockBuilder(StepNode::class)
                             ->disableOriginalConstructor()
                             ->getMock();
        $mockStepTestResult = $this->getMockBuilder(StepResult::class)
                                   ->disableOriginalConstructor()
                                   ->getMock();
        $mockStepTearDown = $this->getMockBuilder(Teardown::class)
                                 ->disableOriginalConstructor()
                                 ->getMock();

        $mockStepTestResult->expects($this->any())
                           ->method('isPassed')
                           ->will($this->returnValue($passed));
        $mockStepTestResult->expects($this->any())
                           ->method('getResultCode')
                           ->will($this->returnValue($resultCode));

        $mockStepNode->expects($this->any())
                     ->method('getLine')
                     ->will($this->returnValue($line));

        $mockBeforeScenarioEvent = new BeforeStepTested($mockStepEnvironment, $mockFeatureNode, $mockStepNode);
        $mockAfterScenarioEvent = new AfterStepTested($mockStepEnvironment,
                                                      $mockFeatureNode,
                                                      $mockStepNode,
                                                      $mockStepTestResult,
                                                      $mockStepTearDown);

        return ['before' => $mockBeforeScenarioEvent, 'after' => $mockAfterScenarioEvent];
    }

    private function getScenarioEvents($scenarioTitle,
                                       $featureTitle,
                                       $featureFile,
                                       $passed,
                                       array $scenarioSteps = [],
                                       array $backgroundSteps = []){
        /**
         * @var $mockScenarioEnvironment Environment|PHPUnit_Framework_MockObject_MockObject
         * @var $mockFeatureNode FeatureNode|PHPUnit_Framework_MockObject_MockObject
         * @var $mockScenario ScenarioInterface|PHPUnit_Framework_MockObject_MockObject
         * @var $mockScenarioTestResult TestResult|PHPUnit_Framework_MockObject_MockObject
         * @var $mockScenarioTearDown Teardown|PHPUnit_Framework_MockObject_MockObject
         */
        $mockScenarioEnvironment = $this->getMockBuilder(Environment::class)
                                        ->disableOriginalConstructor()
                                        ->getMock();
        $mockFeatureNode = $this->getMockBuilder(FeatureNode::class)
                                ->disableOriginalConstructor()
                                ->getMock();
        $mockScenario = $this->getMockBuilder(ScenarioInterface::class)
                             ->disableOriginalConstructor()
                             ->getMock();
        $mockScenarioTestResult = $this->getMockBuilder(TestResult::class)
                                      ->disableOriginalConstructor()
                                      ->getMock();
        $mockScenarioTearDown = $this->getMockBuilder(Teardown::class)
                                    ->disableOriginalConstructor()
                                    ->getMock();
        $mockBackgroundNode = $this->getMockBuilder(BackgroundNode::class)
                                   ->disableOriginalConstructor()
                                   ->getMock();

        $mockFeatureNode->expects($this->any())
                        ->method('getTitle')
                        ->will($this->returnValue($featureTitle));
        $mockFeatureNode->expects($this->any())
                        ->method('getFile')
                        ->will($this->returnValue($featureFile));
        $mockFeatureNode->expects($this->any())
                        ->method('getBackground')
                        ->will($this->returnValue($mockBackgroundNode));

        $mockBackgroundNode->expects($this->any())
                           ->method('getSteps')
                           ->will($this->returnValue($backgroundSteps));

        $mockScenario->expects($this->any())
                     ->method('getTitle')
                     ->will($this->returnValue($scenarioTitle));
        $mockScenario->expects($this->any())
                     ->method('getSteps')
                     ->will($this->returnValue($scenarioSteps));

        $mockScenarioTestResult->expects($this->any())
                               ->method('isPassed')
                               ->will($this->returnValue($passed));

        $mockBeforeScenarioEvent = new BeforeScenarioTested($mockScenarioEnvironment, $mockFeatureNode, $mockScenario);
        $mockAfterScenarioEvent = new AfterScenarioTested($mockScenarioEnvironment,
                                                        $mockFeatureNode,
                                                         $mockScenario,
                                                         $mockScenarioTestResult,
                                                         $mockScenarioTearDown);

        return ['before' => $mockBeforeScenarioEvent, 'after' => $mockAfterScenarioEvent];
    }

    private function getFeatureEvents($title, $description, $file, $language){
        /**
         * @var $mockFeatureEnvironment Environment|PHPUnit_Framework_MockObject_MockObject
         * @var $mockFeatureNode FeatureNode|PHPUnit_Framework_MockObject_MockObject
         * @var $mockFeatureTestResult TestResult|PHPUnit_Framework_MockObject_MockObject
         * @var $mockFeatureTearDown Teardown|PHPUnit_Framework_MockObject_MockObject
         */
        $mockFeatureEnvironment = $this->getMockBuilder(Environment::class)
                                       ->disableOriginalConstructor()
                                       ->getMock();
        $mockFeatureNode = $this->getMockBuilder(FeatureNode::class)
                                ->disableOriginalConstructor()
                                ->getMock();
        $mockFeatureTestResult = $this->getMockBuilder(TestResult::class)
                                    ->disableOriginalConstructor()
                                    ->getMock();
        $mockFeatureTearDown = $this->getMockBuilder(Teardown::class)
                                  ->disableOriginalConstructor()
                                  ->getMock();

        $mockFeatureNode->expects($this->any())
                        ->method('getTitle')
                        ->will($this->returnValue($title));
        $mockFeatureNode->expects($this->any())
                        ->method('getDescription')
                        ->will($this->returnValue($description));
        $mockFeatureNode->expects($this->any())
                        ->method('getFile')
                        ->will($this->returnValue($file));
        $mockFeatureNode->expects($this->any())
                        ->method('getLanguage')
                        ->will($this->returnValue($language));

        $mockBeforeFeatureEvent = new BeforeFeatureTested($mockFeatureEnvironment, $mockFeatureNode);
        $mockAfterFeatureEvent = new AfterFeatureTested($mockFeatureEnvironment,
                                                        $mockFeatureNode,
                                                        $mockFeatureTestResult,
                                                        $mockFeatureTearDown);

        return ['before' => $mockBeforeFeatureEvent, 'after' => $mockAfterFeatureEvent];
    }

    private function getSuiteEvents($name){
        /**
         * @var $mockSuiteTestResult TestResult|PHPUnit_Framework_MockObject_MockObject
         * @var $mockSuiteTearDown Teardown|PHPUnit_Framework_MockObject_MockObject
         * @var $mockSuiteEnvironment Environment|PHPUnit_Framework_MockObject_MockObject
         * @var $mockSuiteSpecification SpecificationIterator|PHPUnit_Framework_MockObject_MockObject
         */
        $mockSuiteTestResult = $this->getMockBuilder(TestResult::class)
                                    ->disableOriginalConstructor()
                                    ->getMock();
        $mockSuiteTearDown = $this->getMockBuilder(Teardown::class)
                                  ->disableOriginalConstructor()
                                  ->getMock();

        $mockSuiteEnvironment = $this->getMockBuilder(Environment::class)
                                     ->disableOriginalConstructor()
                                     ->getMock();
        $mockSuiteSpecification = $this->getMockBuilder(SpecificationIterator::class)
                                       ->disableOriginalConstructor()
                                       ->getMock();

        $mockSuite = $this->getMockBuilder(Suite::class)
                          ->disableOriginalConstructor()
                          ->getMock();

        $mockBeforeSuiteEvent = new BeforeSuiteTested($mockSuiteEnvironment,$mockSuiteSpecification);
        $mockAfterSuiteEvent = new AfterSuiteTested($mockSuiteEnvironment,
                                                    $mockSuiteSpecification,
                                                    $mockSuiteTestResult,
                                                    $mockSuiteTearDown);

        $mockSuiteEnvironment->expects($this->any())
                             ->method('getSuite')
                             ->will($this->returnValue($mockSuite));

        $mockSuite->expects($this->any())
                  ->method('getName')
                  ->will($this->returnValue($name));

        return ['before' => $mockBeforeSuiteEvent, 'after' => $mockAfterSuiteEvent];
    }
}