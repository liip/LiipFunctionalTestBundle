<?php

namespace Liip\FunctionalTestBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Command used to update the project.
 */
class RunParatestCommand extends ContainerAwareCommand
{
    private $output;
    private $process = 5;
    private $testDbPath;
    private $paratestPath;
    private $phpunit;
    private $xmlConfig;

    /**
     * Configuration of the command.
     */
    protected function configure()
    {
        $this
            ->setName('paratest:run')
            ->setDescription('Run phpunit tests with multiple process')
            ->addArgument('options', InputArgument::OPTIONAL, 'Options')
        ;
    }

    protected function prepare()
    {
        $container = $this->getContainer();

        $this->process = $container->getParameter('liip_functional_test.paratest.process');
        $this->paratestPath = $container->getParameter('liip_functional_test.paratest.path');
        $this->phpunit = $container->getParameter('liip_functional_test.paratest.phpunit');
        $this->xmlConfig = $container->getParameter('liip_functional_test.paratest.xml_config');

        $this->testDbPath = $container->getParameter('kernel.cache_dir');

        $this->output->writeln('Cleaning old dbs in '.$this->testDbPath.' ...');
        $cleanProcess = new Process('rm -fr '.$this->testDbPath.'/dbTest.db '.$this->testDbPath.'/dbTest*.db*');
        $cleanProcess->run();
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

        if (is_file($this->paratestPath) !== true) {
            $this->output->writeln('Error : Install paratest first');
        } else {
            $this->output->writeln('Done...Running test.');
            $runProcess = new Process($this->paratestPath.' '.
                '-c '.$this->xmlConfig.' '.
                '--phpunit '.$this->phpunit.' '.
                '--runner WrapRunner -p '.$this->process.' '.
                $input->getArgument('options')
            );
            $runProcess->run(function ($type, $buffer) use ($output) {
                $output->write($buffer);
            });
        }
    }
}
