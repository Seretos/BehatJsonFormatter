<?php
/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 05.02.18
 * Time: 13:43
 */

namespace seretos\BehatJsonFormatter\Command;


use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class DoubleResultCheckCommand extends BaseCommand {
    /**
     * @var array
     */
    private $scenarios;

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    public function execute (InputInterface $input, OutputInterface $output) {
        $this->scenarios = [];

        $files = $this->loadFiles($input->getOption('jsonDir'),$input->getOption('pattern'));

        $output->writeln(count($files).' files found');
        foreach($files as $file){
            $output->writeln($file);
        }
        $output->writeln('');

        $result = 0;
        $output->writeln('check test execution counts');
        foreach($this->scenarios as $title => $environments){
            $output->write($title.'...');
            foreach($environments as $name => $environment){
                if($environment > 1 || $environment < 1){
                    $output->write(' <error>'.$name.'['.$environment.']</error>');
                    $result = -1;
                }else{
                    $output->write(' <info>'.$name.'['.$environment.']</info>');
                }
            }
            $output->writeln('');
        }
        return $result;
    }

    /**
     * Configure this Command.
     * @return void
     */
    protected function configure () {
        $this->setName('behat:double:result:check')
             ->addOption('jsonDir',
                         'j',
                         InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                         'the json directory',
                         ['./build'])
            ->addOption('pattern',
                        'p',
                        InputOption::VALUE_REQUIRED,
                        'the search pattern')
             ->setDescription('check for double behat execution')
             ->setHelp(<<<EOT
The <info>%command.name%</info> check for double behat execution

Example (<comment>1</comment>): <info>parse all json files in ./build for double execution check</info>

     php %command.full_name% --jsonDir=./build
EOT
             );
    }

    private function loadFiles($directories, $pattern){
        /* @var $finder Finder*/
        $finder = $this->getContainer()
            ->get('behat.json.formatter.command.factory')
            ->createFinder();

        $files = [];
        foreach($directories as $directory){
            foreach($finder->in($directory)
                        ->path($pattern)
                        ->files()
                        ->name('*.json') as $file){
                /* @var $file SplFileInfo*/
                $this->parseJson($file->getRealPath());
                $files[] = $file->getRealPath();
            }
        }

        return $files;
    }

    private function parseJson($path){
        $json = $this->getContainer()
            ->get('behat.json.formatter.command.factory')
            ->readJson($path);
        if(isset($json['features'])) {
            foreach ($json['features'] as $key => $feature) {
                if(isset($feature['scenarios'])) {
                    foreach ($feature['scenarios'] as $title => $scenario) {
                        if (!isset($this->scenarios[$title])) {
                            $this->scenarios[$title] = [];
                        }
                        foreach ($scenario['results'] as $environment => $result) {
                            if (!isset($this->scenarios[$title][$environment])) {
                                $this->scenarios[$title][$environment] = 0;
                            }
                            $this->scenarios[$title][$environment]++;
                        }
                    }
                }
            }
        }
    }
}