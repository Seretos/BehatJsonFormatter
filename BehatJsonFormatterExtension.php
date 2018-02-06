<?php
/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 04.02.18
 * Time: 04:00
 */

namespace seretos\BehatJsonFormatter;

use Behat\Testwork\ServiceContainer\Extension as ExtensionInterface;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use seretos\BehatJsonFormatter\Formatter\BehatJsonFormatter;
use seretos\BehatJsonFormatter\Printer\FileOutputPrinter;
use seretos\BehatJsonFormatter\Printer\ScreenshotPrinter;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class BehatJsonFormatterExtension implements ExtensionInterface {

    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     */
    public function process (ContainerBuilder $container) {
    }

    /**
     * Returns the extension config key.
     *
     * @return string
     */
    public function getConfigKey () {
        return "json";
    }

    /**
     * Initializes other extensions.
     *
     * This method is called immediately after all extensions are activated but
     * before any extension `configure()` method is called. This allows extensions
     * to hook into the configuration of other extensions providing such an
     * extension point.
     *
     * @param ExtensionManager $extensionManager
     */
    public function initialize (ExtensionManager $extensionManager) {
    }

    /**
     * Setups configuration for the extension.
     *
     * @param ArrayNodeDefinition $builder
     */
    public function configure (ArrayNodeDefinition $builder) {
        $builder->children()->scalarNode('output_path')->defaultValue('.');
        $builder->children()->scalarNode('step_screenshots')->defaultValue(false);
    }

    /**
     * Loads extension services into temporary container.
     *
     * @param ContainerBuilder $container
     * @param array            $config
     */
    public function load (ContainerBuilder $container, array $config) {
        $printerDefinition = new Definition(FileOutputPrinter::class);
        $container->setDefinition('json.printer',$printerDefinition);

        $screenshotPrinterDefinition = new Definition(ScreenshotPrinter::class);
        $container->setDefinition('json.printer.screenshot',$screenshotPrinterDefinition);

        $definition = new Definition(BehatJsonFormatter::class);

        $definition->addArgument(new Reference('mink'));
        $definition->addArgument(new Reference('json.printer'));
        $definition->addArgument(new Reference('json.printer.screenshot'));
        $definition->addArgument('%mink.parameters%');
        $definition->addArgument($config['step_screenshots']);
        $definition->addArgument($config['output_path']);

        $container->setDefinition("json.formatter", $definition)
                  ->addTag("output.formatter");
    }
}