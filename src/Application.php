<?php
/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 06.02.18
 * Time: 15:45
 */

namespace seretos\BehatJsonFormatter;


use seretos\BehatJsonFormatter\Command\DoubleResultCheckCommand;
use seretos\BehatJsonFormatter\Command\MergeResultCommand;
use seretos\BehatJsonFormatter\Command\ValidateResultCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Application extends BaseApplication implements ContainerAwareInterface{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function doRun (InputInterface $input, OutputInterface $output) {
        $this->registerCommands();

        return parent::doRun($input, $output);
    }

        /**
     * Sets the container.
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     */
    public function setContainer (ContainerInterface $container = null) {
        $this->container = $container;
    }

    public function getContainer(){
        return $this->container;
    }

    protected function registerCommands () {
        $this->addCommands([new DoubleResultCheckCommand(), new MergeResultCommand(), new ValidateResultCommand()]);
    }
}