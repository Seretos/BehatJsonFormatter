<?php
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioInterface;
use Behat\Gherkin\Parser;
use PHPUnit\Framework\TestCase;
use seretos\BehatJsonFormatter\Command\ValidateResultCommand;
use seretos\BehatJsonFormatter\Service\CommandFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Finder;

/**
 * Created by PhpStorm.
 * User: Seredos
 * Date: 06.02.2018
 * Time: 23:06
 */
class ValidateResultCommandTest extends TestCase
{
    /**
     * @var CommandFactory|PHPUnit_Framework_MockObject_MockObject
     */
    private $mockFactory;
    /**
     * @var ValidateResultCommand
     */
    private $command;
    /**
     * @var InputInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $mockInput;
    /**
     * @var OutputInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $mockOutput;
    /**
     * @var Parser|PHPUnit_Framework_MockObject_MockObject
     */
    private $mockParser;
    /**
     * @var Finder|PHPUnit_Framework_MockObject_MockObject
     */
    private $mockFinder;

    protected function setUp(){
        parent::setUp();

        $this->mockFactory = $this->getMockBuilder(CommandFactory::class)->disableOriginalConstructor()->getMock();
        $mockContainer = $this->getMockBuilder(ContainerInterface::class)->disableOriginalConstructor()->getMock();

        $this->mockInput = $this->getMockBuilder(InputInterface::class)->disableOriginalConstructor()->getMock();
        $this->mockOutput = $this->getMockBuilder(OutputInterface::class)->disableOriginalConstructor()->getMock();
        $this->mockParser = $this->getMockBuilder(Parser::class)->disableOriginalConstructor()->getMock();
        $this->mockFinder = $this->getMockBuilder(Finder::class)->disableOriginalConstructor()->getMock();

        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap([['behat.json.formatter.command.factory',
                ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                $this->mockFactory]]));

        $this->mockFactory->expects($this->once())
            ->method('createBehatParser')
            ->will($this->returnValue($this->mockParser));
        $this->mockFactory->expects($this->once())
            ->method('createFinder')
            ->will($this->returnValue($this->mockFinder));
        $this->mockFactory->expects($this->any())
            ->method('getKeywords')
            ->will($this->returnValue(['en' => ['scenario' => 'Scenario', 'scenario_outline' => 'Scenario Template|Scenario Outline']]));

        $this->command = new ValidateResultCommand();
        $this->command->setContainer($mockContainer);
    }

    /**
     * @test
     */
    public function execute_withoutFeatures(){
        $this->mockInput->expects($this->any())
            ->method('getOption')
            ->will($this->returnValueMap([['json','./result.json'],
                ['featureDir','./features']]));

        $this->mockFactory->expects($this->once())
            ->method('readJson')
            ->with('./result.json')
            ->will($this->returnValue([]));

        $this->mockFinder->expects($this->at(0))
            ->method('in')
            ->with('./features')
            ->will($this->returnValue($this->mockFinder));
        $this->mockFinder->expects($this->at(1))
            ->method('files')
            ->will($this->returnValue($this->mockFinder));
        $this->mockFinder->expects($this->at(2))
            ->method('name')
            ->with('*.feature')
            ->will($this->returnValue([]));

        $this->mockOutput->expects($this->at(0))->method('writeln')->with('feature files:');
        $this->mockOutput->expects($this->at(1))->method('writeln')->with('');
        $this->mockOutput->expects($this->at(2))->method('writeln')->with('<error>no feature files found!</error>');

        $this->assertSame(-1,$this->command->execute($this->mockInput,$this->mockOutput));
    }

    /**
     * @test
     */
    public function execute_withCommentedScenario(){
        $mockFeature1 = $this->getMockBuilder(SplFileInfo::class)->disableOriginalConstructor()->getMock();
        $featureContent = <<<EOT
#
#   Scenario: my commented scenario
EOT;

        $this->mockInput->expects($this->any())
            ->method('getOption')
            ->will($this->returnValueMap([['json','./result.json'],
                ['featureDir','./features']]));

        $this->mockFactory->expects($this->once())
            ->method('readJson')
            ->with('./result.json')
            ->will($this->returnValue([]));

        $this->mockFinder->expects($this->at(0))
            ->method('in')
            ->with('./features')
            ->will($this->returnValue($this->mockFinder));
        $this->mockFinder->expects($this->at(1))
            ->method('files')
            ->will($this->returnValue($this->mockFinder));
        $this->mockFinder->expects($this->at(2))
            ->method('name')
            ->with('*.feature')
            ->will($this->returnValue([$mockFeature1]));

        $mockFeature1->expects($this->any())->method('getRealPath')->will($this->returnValue('/path/to/file.feature'));

        $this->mockFactory->expects($this->any())
            ->method('readFile')
            ->will($this->returnValueMap([['/path/to/file.feature',$featureContent]]));

        $this->mockParser->expects($this->any())
            ->method('parse')
            ->will($this->returnValueMap([[$featureContent,null,null]]));

        $this->mockOutput->expects($this->at(0))->method('writeln')->with('feature files:');
        $this->mockOutput->expects($this->at(1))->method('writeln')->with('/path/to/file.feature');
        $this->mockOutput->expects($this->at(2))->method('writeln')->with('');
        $this->mockOutput->expects($this->at(3))->method('writeln')->with('<error>empty file: /path/to/file.feature</error>');
        $this->mockOutput->expects($this->at(4))->method('writeln')->with('<error>the file /path/to/file.feature has commented scenarios</error>');

        $this->assertSame(-1,$this->command->execute($this->mockInput,$this->mockOutput));
    }

    /**
     * @test
     */
    public function execute_withNotExecutedScenario(){
        $mockFeature1 = $this->getMockBuilder(SplFileInfo::class)->disableOriginalConstructor()->getMock();
        $mockFeatureNode = $this->getMockBuilder(FeatureNode::class)->disableOriginalConstructor()->getMock();
        $mockScenarioNode = $this->getMockBuilder(ScenarioInterface::class)->disableOriginalConstructor()->getMock();
        $featureContent = <<<EOT
@language: en
Feature: my Feature
   Scenario: my hidden scenario
EOT;

        $mockFeatureNode->expects($this->any())
            ->method('getScenarios')
            ->will($this->returnValue([$mockScenarioNode]));
        $mockFeatureNode->expects($this->any())
            ->method('getLanguage')
            ->will($this->returnValue('en'));

        $mockScenarioNode->expects($this->any())
            ->method('getTitle')
            ->will($this->returnValue('my hidden scenario'));

        $this->mockInput->expects($this->any())
            ->method('getOption')
            ->will($this->returnValueMap([['json','./result.json'],
                ['featureDir','./features']]));

        $this->mockFactory->expects($this->once())
            ->method('readJson')
            ->with('./result.json')
            ->will($this->returnValue([]));

        $this->mockFinder->expects($this->at(0))
            ->method('in')
            ->with('./features')
            ->will($this->returnValue($this->mockFinder));
        $this->mockFinder->expects($this->at(1))
            ->method('files')
            ->will($this->returnValue($this->mockFinder));
        $this->mockFinder->expects($this->at(2))
            ->method('name')
            ->with('*.feature')
            ->will($this->returnValue([$mockFeature1]));

        $mockFeature1->expects($this->any())->method('getRealPath')->will($this->returnValue('/path/to/file.feature'));

        $this->mockFactory->expects($this->any())
            ->method('readFile')
            ->will($this->returnValueMap([['/path/to/file.feature',$featureContent]]));

        $this->mockParser->expects($this->any())
            ->method('parse')
            ->will($this->returnValueMap([[$featureContent,null,$mockFeatureNode]]));

        $this->mockOutput->expects($this->at(0))->method('writeln')->with('feature files:');
        $this->mockOutput->expects($this->at(1))->method('writeln')->with('/path/to/file.feature');
        $this->mockOutput->expects($this->at(2))->method('writeln')->with('');
        $this->mockOutput->expects($this->at(3))->method('writeln')->with('<error>scenario not executed: my hidden scenario</error>');

        $this->assertSame(-1,$this->command->execute($this->mockInput,$this->mockOutput));
    }

    /**
     * @test
     */
    public function execute_withExecutedScenario(){
        $mockFeature1 = $this->getMockBuilder(SplFileInfo::class)->disableOriginalConstructor()->getMock();
        $mockFeatureNode = $this->getMockBuilder(FeatureNode::class)->disableOriginalConstructor()->getMock();
        $mockScenarioNode = $this->getMockBuilder(ScenarioInterface::class)->disableOriginalConstructor()->getMock();
        $featureContent = <<<EOT
@language: en
Feature: my Feature
   Scenario: my scenario
EOT;

        $mockFeatureNode->expects($this->any())
            ->method('getScenarios')
            ->will($this->returnValue([$mockScenarioNode]));
        $mockFeatureNode->expects($this->any())
            ->method('getLanguage')
            ->will($this->returnValue('en'));
        $mockFeatureNode->expects($this->any())
            ->method('getTitle')
            ->will($this->returnValue('my Feature'));

        $mockScenarioNode->expects($this->any())
            ->method('getTitle')
            ->will($this->returnValue('my scenario'));

        $this->mockInput->expects($this->any())
            ->method('getOption')
            ->will($this->returnValueMap([['json','./result.json'],
                ['featureDir','./features']]));

        $this->mockFactory->expects($this->once())
            ->method('readJson')
            ->with('./result.json')
            ->will($this->returnValue(['suites' => [
                0=>['features' =>
                    [
                        '/my/feature' => ['title' => 'my Feature','scenarios' => ['my scenario' => []]]
                    ]
                ]
            ]]));

        $this->mockFinder->expects($this->at(0))
            ->method('in')
            ->with('./features')
            ->will($this->returnValue($this->mockFinder));
        $this->mockFinder->expects($this->at(1))
            ->method('files')
            ->will($this->returnValue($this->mockFinder));
        $this->mockFinder->expects($this->at(2))
            ->method('name')
            ->with('*.feature')
            ->will($this->returnValue([$mockFeature1]));

        $mockFeature1->expects($this->any())->method('getRealPath')->will($this->returnValue('/path/to/file.feature'));

        $this->mockFactory->expects($this->any())
            ->method('readFile')
            ->will($this->returnValueMap([['/path/to/file.feature',$featureContent]]));

        $this->mockParser->expects($this->any())
            ->method('parse')
            ->will($this->returnValueMap([[$featureContent,null,$mockFeatureNode]]));

        $this->mockOutput->expects($this->at(0))->method('writeln')->with('feature files:');
        $this->mockOutput->expects($this->at(1))->method('writeln')->with('/path/to/file.feature');
        $this->mockOutput->expects($this->at(2))->method('writeln')->with('');
        $this->mockOutput->expects($this->at(3))->method('writeln')->with('<info>my scenario</info>');

        $this->assertSame(0,$this->command->execute($this->mockInput,$this->mockOutput));
    }
}