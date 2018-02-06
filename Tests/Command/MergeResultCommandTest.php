<?php
use PHPUnit\Framework\TestCase;
use seretos\BehatJsonFormatter\Command\MergeResultCommand;
use seretos\BehatJsonFormatter\Service\CommandFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Finder;

/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 06.02.18
 * Time: 16:46
 */
class MergeResultCommandTest extends TestCase {
    /**
     * @var CommandFactory|PHPUnit_Framework_MockObject_MockObject
     */
    private $mockFactory;
    /**
     * @var MergeResultCommand
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

    protected function setUp(){
        parent::setUp();

        $this->mockFactory = $this->getMockBuilder(CommandFactory::class)
                                  ->disableOriginalConstructor()
                                  ->getMock();
        $mockContainer = $this->getMockBuilder(ContainerInterface::class)
                              ->disableOriginalConstructor()
                              ->getMock();

        $this->mockInput = $this->getMockBuilder(InputInterface::class)
                                ->disableOriginalConstructor()
                                ->getMock();
        $this->mockOutput = $this->getMockBuilder(OutputInterface::class)
                                 ->disableOriginalConstructor()
                                 ->getMock();

        $mockContainer->expects($this->any())
                      ->method('get')
                      ->will($this->returnValueMap([['behat.json.formatter.command.factory',
                                                     ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                                                     $this->mockFactory]]));

        $this->command = new MergeResultCommand();
        $this->command->setContainer($mockContainer);
    }

    /**
     * @test
     */
    public function execute_suiteOnly(){
        $this->mockInput->expects($this->any())
                        ->method('getOption')
                        ->will($this->returnValueMap([['jsonDir',['./build']],
                                                      ['pattern',''],
                                                      ['output','./result.json']]));


        $this->singleFileMock(['name' => 'mySuite'],
                              ['suites' => ['mySuite' => ['name' => 'mySuite',
                                                          'features' => []]],
                               'results' => []]);
        $this->assertSame(0,$this->command->execute($this->mockInput,$this->mockOutput));
    }

    /**
     * @test
     */
    public function execute_featureOnly(){
        $this->mockInput->expects($this->any())
                        ->method('getOption')
                        ->will($this->returnValueMap([['jsonDir',['./build']],
                                                      ['pattern',''],
                                                      ['output','./result.json']]));


        $this->singleFileMock(['name' => 'mySuite','features' => ['/path/to/my.feature' => ['language' => 'en',
                                                                                            'description' => 'my description',
                                                                                            'title' => 'my title']]],
                              ['suites' => ['mySuite' => ['name' => 'mySuite',
                                                          'features' => ['/path/to/my.feature' => ['title' => 'my title'
                                                                                                   ,'description' => 'my description'
                                                                                                   ,'language' => 'en'
                                                                                                   ,'file' => '/path/to/my.feature'
                                                                                                   ,'scenarios' => []]]]],
                               'results' => []]);
        $this->assertSame(0,$this->command->execute($this->mockInput,$this->mockOutput));
    }

    /**
     * @test
     */
    public function execute_scenarioOnly(){
        $this->mockInput->expects($this->any())
                        ->method('getOption')
                        ->will($this->returnValueMap([['jsonDir',['./build']],
                                                      ['pattern',''],
                                                      ['output','./result.json']]));


        $this->singleFileMock(['name' => 'mySuite','features' => ['/path/to/my.feature' => ['language' => 'en',
                                                                                            'description' => 'my description',
                                                                                            'title' => 'my title',
                                                                                            'scenarios' => ['title1' => ['steps' => [],'results' => []]]]]],
                              ['suites' => ['mySuite' => ['name' => 'mySuite',
                                                          'features' => ['/path/to/my.feature' => ['title' => 'my title'
                                                                                                   ,'description' => 'my description'
                                                                                                   ,'language' => 'en'
                                                                                                   ,'file' => '/path/to/my.feature'
                                                                                                   ,'scenarios' => ['title1' => ['steps' => null, 'results' => []]]]]]],
                               'results' => []]);
        $this->assertSame(0,$this->command->execute($this->mockInput,$this->mockOutput));
    }

    /**
     * @test
     */
    public function execute_stepOnly(){
        $this->mockInput->expects($this->any())
                        ->method('getOption')
                        ->will($this->returnValueMap([['jsonDir',['./build']],
                                                      ['pattern',''],
                                                      ['output','./result.json']]));


        $this->singleFileMock(['name' => 'mySuite','features' => ['/path/to/my.feature' => ['language' => 'en',
                                                                                            'description' => 'my description',
                                                                                            'title' => 'my title',
                                                                                            'scenarios' => ['title1' => ['steps' => ['1' => ['keyword' => 'when','text' => 'my text']],'results' => []]]]]],
                              ['suites' => ['mySuite' => ['name' => 'mySuite',
                                                          'features' => ['/path/to/my.feature' => ['title' => 'my title'
                                                                                                   ,'description' => 'my description'
                                                                                                   ,'language' => 'en'
                                                                                                   ,'file' => '/path/to/my.feature'
                                                                                                   ,'scenarios' => ['title1' => ['steps' => ['1' => ['text' => 'my text','keyword' => 'when','arguments' => null]], 'results' => []]]]]]],
                               'results' => []]);
        $this->assertSame(0,$this->command->execute($this->mockInput,$this->mockOutput));
    }

    /**
     * @test
     */
    public function execute_resultOnly(){
        $this->mockInput->expects($this->any())
            ->method('getOption')
            ->will($this->returnValueMap([['jsonDir',['./build']],
                ['pattern',''],
                ['output','./result.json']]));


        $this->singleFileMock(['name' => 'mySuite','features' => ['/path/to/my.feature' => ['language' => 'en',
            'description' => 'my description',
            'title' => 'my title',
            'scenarios' => ['title1' => ['steps' => ['1' => ['keyword' => 'when','text' => 'my text']],'results' => ['firefly' => ['passed' => true, 'steps' => ['1' => ['passed' => true,'screenshot' => null]]]]]]]]],
            ['suites' => ['mySuite' => ['name' => 'mySuite',
                'features' => ['/path/to/my.feature' => ['title' => 'my title'
                    ,'description' => 'my description'
                    ,'language' => 'en'
                    ,'file' => '/path/to/my.feature'
                    ,'scenarios' => ['title1' => ['steps' => ['1' => ['text' => 'my text','keyword' => 'when','arguments' => null]], 'results' => ['firefly' => ['passed' => true,'steps' => ['1' => ['passed' => true, 'screenshot' => null]]]]]]]]]],
                'results' => ['firefly' => ['passed' => 1,'failed' => 0]]]);
        $this->assertSame(0,$this->command->execute($this->mockInput,$this->mockOutput));
    }

    /**
     * @test
     */
    public function execute_resultOnlyFailed(){
        $this->mockInput->expects($this->any())
            ->method('getOption')
            ->will($this->returnValueMap([['jsonDir',['./build']],
                ['pattern',''],
                ['output','./result.json']]));


        $this->singleFileMock(['name' => 'mySuite','features' => ['/path/to/my.feature' => ['language' => 'en',
            'description' => 'my description',
            'title' => 'my title',
            'scenarios' => ['title1' => ['steps' => ['1' => ['keyword' => 'when','text' => 'my text'],'2' => ['keyword' => 'when','text' => 'my text 2']],'results' => ['firefly' => ['passed' => false, 'steps' => ['1' => ['passed' => true,'screenshot' => null],'2' => ['passed' => false,'screenshot' => null]]]]]]]]],
            ['suites' => ['mySuite' => ['name' => 'mySuite',
                'features' => ['/path/to/my.feature' => ['title' => 'my title'
                    ,'description' => 'my description'
                    ,'language' => 'en'
                    ,'file' => '/path/to/my.feature'
                    ,'scenarios' => ['title1' => ['steps' => ['1' => ['text' => 'my text','keyword' => 'when','arguments' => null],'2' => ['text' => 'my text 2','keyword' => 'when','arguments' => null]], 'results' => ['firefly' => ['passed' => false,'steps' => ['1' => ['passed' => true, 'screenshot' => null],'2' => ['passed' => false, 'screenshot' => null]]]]]]]]]],
                'results' => ['firefly' => ['passed' => 0,'failed' => 1]]]);
        $this->assertSame(0,$this->command->execute($this->mockInput,$this->mockOutput));
    }

    /**
     * @test
     */
    public function execute_multipleFiles(){
        $input1 = ['name' => 'mySuite',
            'features' => ['my1.feature' => ['title' => 'my first title','language' => 'de','description'=>'my first description']]];
        $input2 = ['name' => 'myOtherSuite',
            'features' => ['my2.feature' => ['title' => 'my second title', 'language' => 'en','description'=>'my second description']]];
        $input3 = ['name' => 'mySuite',
            'features' => ['my3.feature' => ['title' => 'my third title','language' => 'fr','description'=>'my third description']]];
        $output = ['suites' => [
            'mySuite' => ['name' => 'mySuite',
                'features' => ['my1.feature' => ['title' => 'my first title','language'=>'de','description'=>'my first description','file' => 'my1.feature','scenarios'=>[]],
                    'my3.feature' => ['title' => 'my third title','language'=>'fr','description'=>'my third description','file'=>'my3.feature','scenarios'=>[]]]],
            'myOtherSuite' => ['name' => 'myOtherSuite',
                'features' => ['my2.feature' => ['title' => 'my second title','language'=>'en','description'=>'my second description','file'=>'my2.feature','scenarios'=>[]]]]
        ],'results' => []];

        $this->mockInput->expects($this->any())
            ->method('getOption')
            ->will($this->returnValueMap([['jsonDir',['./build']],
                ['pattern',''],
                ['output','./result.json']]));

        $mockFinder = $this->getMockBuilder(Finder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockFile1 = $this->getMockBuilder(SplFileInfo::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockFile2 = $this->getMockBuilder(SplFileInfo::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockFile3 = $this->getMockBuilder(SplFileInfo::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockFinder->expects($this->once())
            ->method('in')
            ->with('./build')
            ->will($this->returnValue($mockFinder));
        $mockFinder->expects($this->once())
            ->method('path')
            ->with('')
            ->will($this->returnValue($mockFinder));
        $mockFinder->expects($this->once())
            ->method('files')
            ->will($this->returnValue($mockFinder));
        $mockFinder->expects($this->once())
            ->method('name')
            ->with('*.json')
            ->will($this->returnValue([$mockFile1,$mockFile2,$mockFile3]));

        $mockFile1->expects($this->once())
            ->method('getRealPath')
            ->will($this->returnValue('/path/to/file.json'));
        $mockFile2->expects($this->once())
            ->method('getRealPath')
            ->will($this->returnValue('/path/to/file2.json'));
        $mockFile3->expects($this->once())
            ->method('getRealPath')
            ->will($this->returnValue('/path/to/file3.json'));

        $this->mockFactory->expects($this->once())
            ->method('createFinder')
            ->will($this->returnValue($mockFinder));

        $this->mockFactory->expects($this->any())
            ->method('readJson')
            ->will($this->returnValueMap([['/path/to/file.json',$input1],
                ['/path/to/file2.json',$input2],
                ['/path/to/file3.json',$input3]]));

        $this->mockFactory->expects($this->once())
            ->method('saveJson')
            ->with('./result.json',$output);

        $this->assertSame(0,$this->command->execute($this->mockInput,$this->mockOutput));
    }

    private function singleFileMock($inputJson, $outputJson){
        $mockFinder = $this->getMockBuilder(Finder::class)
                           ->disableOriginalConstructor()
                           ->getMock();

        $mockFile = $this->getMockBuilder(SplFileInfo::class)
                         ->disableOriginalConstructor()
                         ->getMock();

        $mockFinder->expects($this->once())
                   ->method('in')
                   ->with('./build')
                   ->will($this->returnValue($mockFinder));
        $mockFinder->expects($this->once())
                   ->method('path')
                   ->with('')
                   ->will($this->returnValue($mockFinder));
        $mockFinder->expects($this->once())
                   ->method('files')
                   ->will($this->returnValue($mockFinder));
        $mockFinder->expects($this->once())
                   ->method('name')
                   ->with('*.json')
                   ->will($this->returnValue([$mockFile]));

        $mockFile->expects($this->once())
                 ->method('getRealPath')
                 ->will($this->returnValue('/path/to/file.json'));

        $this->mockFactory->expects($this->once())
                          ->method('createFinder')
                          ->will($this->returnValue($mockFinder));

        $this->mockFactory->expects($this->once())
                          ->method('readJson')
                          ->with('/path/to/file.json')
                          ->will($this->returnValue($inputJson));

        $this->mockFactory->expects($this->once())
                          ->method('saveJson')
                          ->with('./result.json',$outputJson);
    }
}