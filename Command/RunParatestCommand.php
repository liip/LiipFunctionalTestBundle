<?php

namespace Liip\FunctionalTestBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Bundle\FrameworkBundle\Console\Application;

/**
 * Command used to update the project.
 */
class RunParatestCommand extends ContainerAwareCommand
{
    private $output;
    private $process;
    private $testDbPath;
    private $phpunit;

    /**
     * Configuration of the command.
     */
    protected function configure()
    {
        $this
            ->setName('paratest:run')
            ->setDescription('Run phpunit tests with multiple processes')
            // Pass arguments from this command "paratest:run" to the paratest command.
            ->addArgument('options', InputArgument::OPTIONAL, 'Options')
        ;
    }

    protected function prepare()
    {
        $this->phpunit = $this->getContainer()->getParameter('liip_functional_test.paratest.phpunit');
        $this->process = $this->getContainer()->getParameter('liip_functional_test.paratest.process');

        $this->testDbPath = $this->getContainer()->get('kernel')->getCacheDir();
        $this->output->writeln("Cleaning old dbs in $this->testDbPath ...");
        $createDirProcess = new Process('mkdir -p '.$this->testDbPath);
        $createDirProcess->run();
        $cleanProcess = new Process("rm -fr $this->testDbPath/dbTest.db $this->testDbPath/dbTest*.db*");
        $cleanProcess->run();
        $this->output->writeln("Creating Schema in $this->testDbPath ...");
        $application = new Application($this->getContainer()->get('kernel'));
        $input = new ArrayInput(array('doctrine:schema:create', '--env' => 'test'));
        $application->run($input, $this->output);

        $this->output->writeln('Initial schema created');
        $input = new ArrayInput(array(
            'doctrine:fixtures:load',
            '-n' => '',
            '--env' => 'test',
        ));
        $application->run($input, $this->output);

        $this->output->writeln('Initial schema populated, duplicating....');
        for ($a = 0; $a < $this->process; ++$a) {
            $test = new Process("cp $this->testDbPath/dbTest.db ".$this->testDbPath."/dbTest$a.db");
            $test->run();
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
        if (is_file('vendor/bin/paratest') !== true) {
            $this->output->writeln('Error : Install paratest first');
        } else {
            $this->output->writeln('Done...Running test.');
            $runProcess = new Process('vendor/bin/paratest '.
                '-c phpunit.xml.dist '.
                '--phpunit '.$this->phpunit.' '.
                '--runner WrapRunner '.
                '-p '.$this->process.' '.
                $input->getArgument('options')
            );
            $runProcess->run(function ($type, $buffer) use ($output) {
                $output->write($buffer);
            });
        }
    }
}
