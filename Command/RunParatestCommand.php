<?php

namespace Liip\FunctionalTestBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Command used to update the project.
 */
class RunParatestCommand extends ContainerAwareCommand
{
    private $container;
    private $configuration;
    private $output;
    private $process = 5;
    private $phpunit = './bin/phpunit';

    /**
     * Configuration of the command.
     */
    protected function configure()
    {
        $this
            ->setName('test:run')
            ->setDescription('Run phpunit tests with multiple process')
        ;

    }

    protected function prepare()
    {
        $this->configuration = $this->getContainer()->hasParameter("liip_functional_test");
        $paratestCfg = ( !isset($this->configuration['paratest'])) ? array('process' => $this->process, 'phpunit' => $this->phpunit) : $this->configuration['paratest'];

        $this->process = ( !empty($this->configuration['process']) ) ? $paratestCfg['process'] : $this->process;
        $this->phpunit = ( !empty($this->configuration['phpunit']) ) ? $paratestCfg['phpunit'] : $this->phpunit;
        $testDbPath = $this->getContainer()->get('kernel')->getRootDir();
        $this->output->writeln("Cleaning old dbs in $testDbPath ...");
        $createDirProcess = new Process('mkdir -p ' . $testDbPath . '/cache/test/');
        $createDirProcess->run();
        $cleanProcess = new Process("rm -fr $testDbPath/cache/test/dbTest.db $testDbPath/cache/test/dbTest*.db*");
        $cleanProcess->run();
        $this->output->writeln("Creating Schema in $testDbPath ...");
        $createProcess = new Process('php app/console doctrine:schema:create --env=test');
        $createProcess->run();

        $this->output->writeln("Initial schema created");
        $populateProcess = new Process("php app/console doctrine:fixtures:load -n --fixtures $testDbPath/../src/overlord/AppBundle/Tests/DataFixtures/ORM/ --env=test");
        $populateProcess->run();

        if ($populateProcess->isSuccessful()) {
            $this->output->writeln('Initial schema populated, duplicating....');
            for ($a = 0; $a < $this->process; $a++) {
                $test = new Process("cp $testDbPath/cache/test/dbTest.db " . $testDbPath . "/cache/test/dbTest$a.db");
                $test->run();
            }
        } else {
            $this->output->writeln("Can t populate");
        }
    }

    /**
     * Content of the command.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->prepare();
        $this->output->writeln('Done...Running test.');
        $runProcess = new Process( $testDbPath  . "../vendor/bin/paratest -c app/ --phpunit " .$this->phpunit." --runner WrapRunner  -p ". $this->process);
        $runProcess->run(function ($type, $buffer) {
            echo $buffer;
        });
    }
}
