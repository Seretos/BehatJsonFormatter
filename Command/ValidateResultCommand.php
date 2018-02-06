<?php
/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 05.02.18
 * Time: 16:47
 */

namespace seretos\BehatJsonFormatter\Command;


use Behat\Gherkin\Keywords\ArrayKeywords;
use Behat\Gherkin\Lexer;
use Behat\Gherkin\Parser;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class ValidateResultCommand extends ContainerAwareCommand{
    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    public function execute (InputInterface $input, OutputInterface $output) {
        $finder = new Finder();

        $keywords = new ArrayKeywords(array(
            'en' => array(
                'feature'          => 'Feature',
                'background'       => 'Background',
                'scenario'         => 'Scenario',
                'scenario_outline' => 'Scenario Outline|Scenario Template',
                'examples'         => 'Examples|Scenarios',
                'given'            => 'Given',
                'when'             => 'When',
                'then'             => 'Then',
                'and'              => 'And',
                'but'              => 'But'
            ),
            'de' =>
                array (
                    'and' => 'Und|*',
                    'background' => 'Grundlage',
                    'but' => 'Aber|*',
                    'examples' => 'Beispiele',
                    'feature' => 'Funktionalität',
                    'given' => 'Gegeben seien|Gegeben sei|Angenommen|*',
                    'name' => 'German',
                    'native' => 'Deutsch',
                    'scenario' => 'Szenario',
                    'scenario_outline' => 'Szenariogrundriss',
                    'then' => 'Dann|*',
                    'when' => 'Wenn|*',
                )));
        $lexer  = new Lexer($keywords);
        $parser = new Parser($lexer);

        $json = json_decode(file_get_contents($input->getOption('json')),true);

        $result = 0;
        foreach($finder->in($input->getOption('featureDir'))
                       ->files()
                       ->name('*.feature') as $file){
            /* @var $file SplFileInfo*/
            $fileContent = file_get_contents($file->getRealPath());
            $feature = $parser->parse($fileContent);

            preg_match_all('/#[\h]*(Szenario|Szenariogrundriss):/', $fileContent, $matches, PREG_SET_ORDER, 0);
            if(count($matches)>0){
                $output->writeln('<error>the file '.$file->getRealPath().' has commented scenarios</error>');
                $result = -1;
            }

            if($feature === null || $feature->getScenarios() === null){
                $output->writeln('<error>empty file: '.$file->getRealPath().'</error>');
                $result = -1;
            }else {
                foreach ($feature->getScenarios() as $scenario) {
                    if(!$this->hasScenario($scenario->getTitle(),$json)){
                        $output->writeln('<error>scenario not executed: '.$scenario->getTitle().'</error>');
                        $result = -1;
                    }
                    //var_dump($scenario->getTitle());
                }
            }
        }
        return $result;
    }

    private function hasScenario($title,$json){
        foreach($json['suites'] as $suite){
            foreach($suite['features'] as $feature){
                foreach($feature['scenarios'] as $currentTitle => $scenario){
                    if($title == $currentTitle){
                        return true;
                    }
                }
            }
        }
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