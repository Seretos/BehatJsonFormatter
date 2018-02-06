<?php
use PHPUnit\Framework\TestCase;
use seretos\BehatJsonFormatter\Command\DoubleResultCheckCommand;
use seretos\BehatJsonFormatter\Service\CommandFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 06.02.18
 * Time: 16:06
 */
class DoubleResultCheckCommandTest extends TestCase {
    /**
     * @var CommandFactory|PHPUnit_Framework_MockObject_MockObject
     */
    private $mockFactory;
    /**
     * @var DoubleResultCheckCommand
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

        $this->command = new DoubleResultCheckCommand();
        $this->command->setContainer($mockContainer);
    }

    /**
     * @test
     */
    public function execute_singleFile(){
        $this->mockInput->expects($this->any())
                        ->method('getOption')
                        ->will($this->returnValueMap([['jsonDir',['./build']],['pattern','']]));

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

        $mockFile->expects($this->any())
                 ->method('getRealPath')
                 ->will($this->returnValue('/path/to/file.json'));

        $this->mockFactory->expects($this->once())
                          ->method('createFinder')
                          ->will($this->returnValue($mockFinder));

        $this->mockFactory->expects($this->once())
                          ->method('readJson')
                          ->with('/path/to/file.json')
                          ->will($this->returnValue(['features' => ['fKey1' => ['scenarios' => ['sKey1' => ['results' => ['firefly' => []]]]]]]));

        $this->mockOutput->expects($this->at(0))
            ->method('writeln')
            ->with('1 files found');
        $this->mockOutput->expects($this->at(1))
            ->method('writeln')
            ->with('/path/to/file.json');
        $this->mockOutput->expects($this->at(2))
            ->method('writeln')
            ->with('');

        $this->mockOutput->expects($this->at(3))
                         ->method('writeln')
                         ->with('check test execution counts');
        $this->mockOutput->expects($this->at(4))
                         ->method('write')
                         ->with('sKey1...');
        $this->mockOutput->expects($this->at(5))
                         ->method('write')
                         ->with(' <info>firefly[1]</info>');
        $this->mockOutput->expects($this->at(6))
                         ->method('writeln')
                         ->with('');

        $this->assertSame(0,$this->command->execute($this->mockInput,$this->mockOutput));
    }

    /**
     * @test
     */
    public function execute_multiFile_withError(){
        $this->mockInput->expects($this->any())
                        ->method('getOption')
                        ->will($this->returnValueMap([['jsonDir',['./build']],['pattern','']]));

        $mockFinder = $this->getMockBuilder(Finder::class)
                           ->disableOriginalConstructor()
                           ->getMock();

        $mockFile1 = $this->getMockBuilder(SplFileInfo::class)
                          ->disableOriginalConstructor()
                          ->getMock();
        $mockFile2 = $this->getMockBuilder(SplFileInfo::class)
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
                   ->will($this->returnValue([$mockFile1,$mockFile2]));

        $mockFile1->expects($this->any())
                  ->method('getRealPath')
                  ->will($this->returnValue('/path/to/file.json'));
        $mockFile2->expects($this->any())
                  ->method('getRealPath')
                  ->will($this->returnValue('/path/to/file2.json'));

        $this->mockFactory->expects($this->once())
                          ->method('createFinder')
                          ->will($this->returnValue($mockFinder));

        $this->mockFactory->expects($this->at(1))
                          ->method('readJson')
                          ->with('/path/to/file.json')
                          ->will($this->returnValue(['features' => ['fKey1' => ['scenarios' => ['sKey1' => ['results' => ['firefly' => []]]]]]]));
        $this->mockFactory->expects($this->at(2))
                          ->method('readJson')
                          ->with('/path/to/file2.json')
                          ->will($this->returnValue(['features' => ['fKey2' => ['scenarios' => ['sKey1' => ['results' => ['firefly' => []]]]]]]));

        $this->mockOutput->expects($this->at(0))
            ->method('writeln')
            ->with('2 files found');
        $this->mockOutput->expects($this->at(1))
            ->method('writeln')
            ->with('/path/to/file.json');
        $this->mockOutput->expects($this->at(2))
            ->method('writeln')
            ->with('/path/to/file2.json');
        $this->mockOutput->expects($this->at(3))
            ->method('writeln')
            ->with('');

        $this->mockOutput->expects($this->at(4))
                         ->method('writeln')
                         ->with('check test execution counts');
        $this->mockOutput->expects($this->at(5))
                         ->method('write')
                         ->with('sKey1...');
        $this->mockOutput->expects($this->at(6))
                         ->method('write')
                         ->with(' <error>firefly[2]</error>');
        $this->mockOutput->expects($this->at(7))
                         ->method('writeln')
                         ->with('');

        $this->assertSame(-1,$this->command->execute($this->mockInput,$this->mockOutput));
    }

    /**
     * @test
     */
    public function execute_multiDir_withPattern(){
        $this->mockInput->expects($this->any())
                        ->method('getOption')
                        ->will($this->returnValueMap([['jsonDir',['./build','./build2']],['pattern','pattern']]));

        $mockFinder = $this->getMockBuilder(Finder::class)
                           ->disableOriginalConstructor()
                           ->getMock();

        $mockFile1 = $this->getMockBuilder(SplFileInfo::class)
                          ->disableOriginalConstructor()
                          ->getMock();
        $mockFile2 = $this->getMockBuilder(SplFileInfo::class)
                          ->disableOriginalConstructor()
                          ->getMock();

        $mockFinder->expects($this->at(0))
                   ->method('in')
                   ->with('./build')
                   ->will($this->returnValue($mockFinder));
        $mockFinder->expects($this->at(1))
                   ->method('path')
                   ->with('pattern')
                   ->will($this->returnValue($mockFinder));
        $mockFinder->expects($this->at(2))
                   ->method('files')
                   ->will($this->returnValue($mockFinder));
        $mockFinder->expects($this->at(3))
                   ->method('name')
                   ->with('*.json')
                   ->will($this->returnValue([$mockFile1]));

        $mockFinder->expects($this->at(4))
                   ->method('in')
                   ->with('./build2')
                   ->will($this->returnValue($mockFinder));
        $mockFinder->expects($this->at(5))
                   ->method('path')
                   ->with('pattern')
                   ->will($this->returnValue($mockFinder));
        $mockFinder->expects($this->at(6))
                   ->method('files')
                   ->will($this->returnValue($mockFinder));
        $mockFinder->expects($this->at(7))
                   ->method('name')
                   ->with('*.json')
                   ->will($this->returnValue([$mockFile2]));

        $mockFile1->expects($this->any())
                  ->method('getRealPath')
                  ->will($this->returnValue('/path/to/file.json'));
        $mockFile2->expects($this->any())
                  ->method('getRealPath')
                  ->will($this->returnValue('/path/to/file2.json'));

        $this->mockFactory->expects($this->once())
                          ->method('createFinder')
                          ->will($this->returnValue($mockFinder));

        $this->mockFactory->expects($this->at(1))
                          ->method('readJson')
                          ->with('/path/to/file.json')
                          ->will($this->returnValue(['features' => ['fKey1' => ['scenarios' => ['sKey1' => ['results' => ['firefly' => []]]]]]]));
        $this->mockFactory->expects($this->at(2))
                          ->method('readJson')
                          ->with('/path/to/file2.json')
                          ->will($this->returnValue(['features' => ['fKey2' => ['scenarios' => ['sKey2' => ['results' => ['firefly' => []]]]]]]));

        $this->mockOutput->expects($this->at(0))
            ->method('writeln')
            ->with('2 files found');
        $this->mockOutput->expects($this->at(1))
            ->method('writeln')
            ->with('/path/to/file.json');
        $this->mockOutput->expects($this->at(2))
            ->method('writeln')
            ->with('/path/to/file2.json');
        $this->mockOutput->expects($this->at(3))
            ->method('writeln')
            ->with('');

        $this->mockOutput->expects($this->at(4))
                         ->method('writeln')
                         ->with('check test execution counts');
        $this->mockOutput->expects($this->at(5))
                         ->method('write')
                         ->with('sKey1...');
        $this->mockOutput->expects($this->at(6))
                         ->method('write')
                         ->with(' <info>firefly[1]</info>');
        $this->mockOutput->expects($this->at(7))
                         ->method('writeln')
                         ->with('');

        $this->mockOutput->expects($this->at(8))
                         ->method('write')
                         ->with('sKey2...');
        $this->mockOutput->expects($this->at(9))
                         ->method('write')
                         ->with(' <info>firefly[1]</info>');
        $this->mockOutput->expects($this->at(10))
                         ->method('writeln')
                         ->with('');

        $this->assertSame(0,$this->command->execute($this->mockInput,$this->mockOutput));
    }
}