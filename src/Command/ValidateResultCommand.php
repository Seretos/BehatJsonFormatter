<?php
/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 05.02.18
 * Time: 16:47
 */

namespace seretos\BehatJsonFormatter\Command;

use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioInterface;
use seretos\BehatJsonFormatter\Service\CommandFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class ValidateResultCommand extends BaseCommand {
    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    public function execute (InputInterface $input, OutputInterface $output) {
        /* @var $factory CommandFactory*/
        $factory = $this->getContainer()->get('behat.json.formatter.command.factory');

        $parser = $factory->createBehatParser();

        $json = $factory->readJson($input->getOption('json'));

        $files = $this->getFeatureFiles($output, $input->getOption('featureDir'));

        if(count($files) === 0){
            $output->writeln('<error>no feature files found!</error>');
            return -1;
        }

        $result = 0;
        foreach($files as $file){
            $fileContent = $factory->readFile($file);

            $feature = $parser->parse($fileContent);
            $language = 'en';
            if($feature === null || $feature->getScenarios() === null){
                $output->writeln('<error>empty file: '.$file.'</error>');
                $result = -1;
            }else {
                $language = $feature->getLanguage();
                foreach ($feature->getScenarios() as $scenario) {
                    if(!$this->hasScenario($scenario, $feature,$json)){
                        $output->writeln('<error>scenario not executed: '.$scenario->getTitle().'</error>');
                        $result = -1;
                    }else{
                        $output->writeln('<info>'.$scenario->getTitle().'</info>');
                    }
                }
            }
            $search = $factory->getKeywords()[$language]['scenario'].'|'.$factory->getKeywords()[$language]['scenario_outline'];

            preg_match_all('/#[\h]*('.$search.'):/', $fileContent, $matches, PREG_SET_ORDER, 0);
            if(count($matches)>0){
                $output->writeln('<error>the file '.$file.' has commented scenarios</error>');
                $result = -1;
            }
        }
        return $result;
    }

    private function getFeatureFiles(OutputInterface $output, $featureDir){
        /* @var $finder Finder*/
        $finder = $this->getContainer()->get('behat.json.formatter.command.factory')->createFinder();
        $files = [];
        $output->writeln('feature files:');
        foreach($finder->in($featureDir)
                    ->files()
                    ->name('*.feature') as $file){
            /* @var $file SplFileInfo*/
            $files[] = $file->getRealPath();
            $output->writeln($file->getRealPath());
        }
        $output->writeln('');
        return $files;
    }

    private function hasScenario(ScenarioInterface $featureScenario, FeatureNode $featureNode,$json){
        if(isset($json['suites']))
            foreach ($json['suites'] as $suite)
                foreach ($suite['features'] as $feature)
                    if ($featureNode->getTitle() == $feature['title'])
                        foreach ($feature['scenarios'] as $currentTitle => $scenario)
                            if ($featureScenario->getTitle() == $currentTitle)
                                return true;

        return false;
    }

    /**
     * Configure this Command.
     * @return void
     */
    protected function configure () {
        $this->setName('behat:validate:result')
             ->addOption('json',
                         'j',
                         InputOption::VALUE_REQUIRED,
                         'the json file',
                         './result.json')
            ->addOption('featureDir',
                        'f',
                        InputOption::VALUE_REQUIRED,
                        'the feature directory',
                        './features')
             ->setDescription('check json results')
             ->setHelp(<<<EOT

EOT
             );
    }
}