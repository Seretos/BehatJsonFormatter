<?php
/**
 * Created by PhpStorm.
 * User: Seredos
 * Date: 06.02.2018
 * Time: 20:29
 */

namespace seretos\BehatJsonFormatter\Command;


use seretos\BehatJsonFormatter\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BaseCommand extends Command implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer(){
        if($this->container === null){
            /* @var $app Application*/
            $app = $this->getApplication();
            $this->container = $app->getContainer();
        }
        return $this->container;
    }
}