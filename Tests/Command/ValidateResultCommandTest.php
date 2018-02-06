<?php
use PHPUnit\Framework\TestCase;
use seretos\BehatJsonFormatter\Command\ValidateResultCommand;
use seretos\BehatJsonFormatter\Service\CommandFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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

        $this->command = new ValidateResultCommand();
        $this->command->setContainer($mockContainer);
    }
}