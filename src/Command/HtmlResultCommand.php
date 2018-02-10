<?php
/**
 * Created by PhpStorm.
 * User: Seredos
 * Date: 07.02.2018
 * Time: 20:08
 */

namespace seretos\BehatJsonFormatter\Command;


use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Twig_Environment;
use Twig_Loader_Filesystem;

class HtmlResultCommand extends BaseCommand
{
    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    public function execute (InputInterface $input, OutputInterface $output) {
        $loader = new Twig_Loader_Filesystem(__DIR__.'/../../Resources/views');
        $twig = new Twig_Environment($loader);

        $path = rtrim($input->getOption('output'),'/').'/';

        $template = $twig->load('index.html.twig');
        $json = $this->getContainer()->get('behat.json.formatter.command.factory')
            ->readJson($input->getOption('json'));

        $params = ['headerName' => $input->getOption('headerName'),
            'bar' => $this->getBarParameters($json),
            'features' => $this->getFeatureTableParameters($json),
            'featureContents' => $this->getFeatureContentParameters($json)];

        file_put_contents($path.'index.html',$template->render($params));

        return 0;
    }

    private function getFeatureContentParameters(array $json){
        $params = [];

        foreach($json['suites'] as $suite){
            foreach($suite['features'] as $file => $feature){
                $id = $this->getFeatureId($file);
                $params[$id] = ['title' => $feature['title'],'id' => $id,
                    'description'=>$feature['description'],
                    'scenarios' => $this->hightlightStepArguments($feature['scenarios'])];
            }
        }

        return $params;
    }

    private function hightlightStepArguments(array $scenarios){
        foreach($scenarios as $scenarioId => $scenario){
            foreach($scenario['steps'] as $stepId => $step){
                $scenarios[$scenarioId]['steps'][$stepId]['text'] = preg_replace('/"(.[^"]*)"/','<b class="text-primary">"${1}"</b>',$step['text']);
            }
        }
        return $scenarios;
    }

    private function getBarParameters(array $json){
        $params = ['tests' => 0, 'executions' => 0,'environments' => []];

        foreach($json['suites'] as $suite){
            if(isset($suite['features'])){
                foreach($suite['features'] as $feature){
                    if(isset($feature['scenarios'])){
                        foreach($feature['scenarios'] as $scenario){
                            $params['tests']++;
                            if(isset($scenario['results'])){
                                foreach($scenario['results'] as $environment => $result){
                                    $params['executions']++;
                                    $params['environments'] = $this->getEnvironmentArr($params['environments'],$environment,$result);
                                }
                            }
                        }
                    }
                }
            }
        }

        return $params;
    }

    private function getFeatureId($file){
        return  preg_replace('/[^A-Za-z0-9\-]/', '', $file);
    }

    private function getFeatureTableParameters(array $json){
        $params = ['suites' => [],'environments' => []];

        foreach($json['suites'] as $suite){
            $params['suites'][$suite['name']] = [];
            foreach($suite['features'] as $file => $feature){
                $id = $this->getFeatureId($file);
                $tableFeature = ['title' => $feature['title'], 'scenarios' => 0, 'environments' => [],'id' => $id];

                foreach($feature['scenarios'] as $scenario){
                    $tableFeature['scenarios']++;
                    foreach($scenario['results'] as $environment => $result){
                        if(!in_array($environment,$params['environments'],true)){
                            $params['environments'][] = $environment;
                        }
                        $tableFeature['environments'] = $this->getEnvironmentArr($tableFeature['environments'],$environment,$result);
                    }
                }

                $params['suites'][$suite['name']]['features'][$feature['title']] = $tableFeature;
            }
            ksort($params['suites'][$suite['name']]['features']);
        }

        return $params;
    }

    private function getEnvironmentArr(array $arr, $environment, $result){
        if(!isset($arr[$environment])){
            $arr[$environment]['passed'] = 0;
            $arr[$environment]['failed'] = 0;
        }
        if($result['passed'] !== true){
            $arr[$environment]['failed']++;
        }else{
            $arr[$environment]['passed']++;
        }
        return $arr;
    }

    /**
     * Configure this Command.
     * @return void
     */
    protected function configure () {
        $this->setName('behat:json:html:result')
            ->addOption('json',
                'j',
                InputOption::VALUE_REQUIRED,
                'the json file',
                './result.json')
            ->addOption('output',
                'f',
                InputOption::VALUE_REQUIRED,
                'the feature directory',
                './')
            ->addOption('headerName',
                'd',
                InputOption::VALUE_REQUIRED,
                'the header name (for example branch or tag name)')
            ->setDescription('convert merged json result to html')
            ->setHelp(<<<EOT

EOT
            );
    }
}