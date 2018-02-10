<?php
/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 05.02.18
 * Time: 15:04
 */

namespace seretos\BehatJsonFormatter\Command;


use seretos\BehatJsonFormatter\Entity\BehatEnvironmentResult;
use seretos\BehatJsonFormatter\Entity\BehatEnvironmentStepResult;
use seretos\BehatJsonFormatter\Entity\BehatFeature;
use seretos\BehatJsonFormatter\Entity\BehatScenario;
use seretos\BehatJsonFormatter\Entity\BehatStep;
use seretos\BehatJsonFormatter\Entity\BehatSuite;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class MergeResultCommand extends BaseCommand {
    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    public function execute (InputInterface $input, OutputInterface $output) {
        /* @var $finder Finder*/
        $finder = $this->getContainer()
                       ->get('behat.json.formatter.command.factory')
                       ->createFinder();
        $directories = $input->getOption('jsonDir');
        $suites = [];
        foreach($directories as $directory){
            foreach($finder->in($directory)
                           ->path($input->getOption('pattern'))
                           ->files()
                           ->name('*.json') as $file){
                /* @var $file SplFileInfo*/
                $suites = $this->parseJson($file->getRealPath(), $suites);
            }
        }

        $results = $this->parseEnvironmentResults($suites);
        foreach ($results as $environment => $result){
            $output->writeln($environment.': <info>'.$result['passed'].'</info>/<error>'.$result['failed'].'</error>');
        }
        $file = $input->getOption('output');

        $data = json_decode(json_encode(['suites' => $suites, 'results' => $results], JSON_PRETTY_PRINT),true);
        $this->getContainer()
             ->get('behat.json.formatter.command.factory')
             ->saveJson($file,$data);

        return 0;
    }

    /**
     * Configure this Command.
     * @return void
     */
    protected function configure () {
        $this->setName('behat:merge:result')
             ->addOption('jsonDir',
                         'j',
                         InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                         'the json directory',
                         ['./build'])
             ->addOption('output',
                        'o',
                        InputOption::VALUE_REQUIRED,
                        'the json result file',
                        './result.json')
            ->addOption('pattern',
                        'p',
                        InputOption::VALUE_REQUIRED,
                        'the search pattern')
             ->setDescription('merge behat json results')
             ->setHelp(<<<EOT
The <info>%command.name%</info> merge behat json results

Example (<comment>1</comment>): <info>merge all jsons in ./build directory</info>

     php %command.full_name% --jsonDir=./build
EOT
             );
    }

    private function parseEnvironmentResults(array $suites){
        $resultArr = [];

        foreach($suites as $suite){
            /* @var $suite BehatSuite*/
            foreach($suite->getFeatures() as $feature){
                foreach($feature->getScenarios() as $scenario){
                    foreach ($scenario->getEnvironmentResults() as $environment => $result){
                        if(!isset($resultArr[$environment])){
                            $resultArr[$environment] = ['passed' => 0,'failed' => 0];
                        }
                        if($result->isPassed()){
                            $resultArr[$environment]['passed']++;
                        }else{
                            $resultArr[$environment]['failed']++;
                        }
                    }
                }
            }
        }
        return $resultArr;
    }

    private function parseJson($path, $suites){
        $json = $this->getContainer()
                     ->get('behat.json.formatter.command.factory')
                     ->readJson($path);
        if(!isset($suites[$json['name']])){
            $suite = new BehatSuite();
            $suite->setName($json['name']);
            $suites[$json['name']] = $suite;
        }else{
            $suite = $suites[$json['name']];
        }

        if(isset($json['features'])) {
            $this->parseFeatures($json['features'], $suite);
        }

        return $suites;
    }

    private function parseFeatures(array $features, BehatSuite $suite){
        foreach($features as $file => $feature){
            $suiteFeature = $suite->getFeature($file);
            if($suiteFeature === null){
                $suiteFeature = new BehatFeature();
                $suiteFeature->setLanguage($feature['language'])
                             ->setFile($file)
                             ->setDescription($feature['description'])
                             ->setTitle($feature['title']);
            }
            if(isset($feature['scenarios'])) {
                $this->parseScenarios($feature['scenarios'], $suiteFeature);
            }
            $suite->setFeature($file, $suiteFeature);
        }
    }

    private function parseScenarios(array $scenarios, BehatFeature $suiteFeature){
        foreach($scenarios as $title => $scenario){
            $featureScenario = $suiteFeature->getScenario($title);
            if($featureScenario === null){
                $featureScenario = new BehatScenario();
            }

            $this->parseSteps($scenario['steps'],$featureScenario);

            $this->parseResults($scenario['results'],$featureScenario);

            $suiteFeature->setScenario($title, $featureScenario);
        }
    }

    private function parseSteps(array $steps, BehatScenario $featureScenario){
        foreach($steps as $line => $step){
            $scenarioStep = $featureScenario->getStep($line);
            if($scenarioStep === null){
                $scenarioStep = new BehatStep();
                $scenarioStep->setKeyWord($step['keyword'])
                    ->setText($step['text']);
                if(isset($step['arguments'])){
                    $scenarioStep->setArguments($step['arguments']);
                }
            }
            $featureScenario->setStep($line, $scenarioStep);
        }
    }

    private function parseResults(array $results, BehatScenario $featureScenario){
        foreach($results as $environment => $result){
            $scenarioEnvironment = $featureScenario->getEnvironmentResult($environment);
            if($scenarioEnvironment === null){
                $scenarioEnvironment = new BehatEnvironmentResult();
                $scenarioEnvironment->setPassed($result['passed']);
            }

            if($result['steps'] !== null) {
                $this->parseResultSteps($result['steps'], $scenarioEnvironment);
            }

            $featureScenario->addEnvironmentResult($environment,$scenarioEnvironment);
        }
    }

    private function parseResultSteps(array $steps, BehatEnvironmentResult $scenarioEnvironment){
        foreach($steps as $line => $step){
            $stepResult = $scenarioEnvironment->getStep($line);
            if($stepResult === null){
                $stepResult = new BehatEnvironmentStepResult();
                $stepResult->setPassed($step['passed'])
                           ->setScreenshot($step['screenshot']);
            }
            $scenarioEnvironment->setStep($line,$stepResult);
        }
    }
}