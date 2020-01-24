<?php

declare(strict_types=1);

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\FunctionalTestBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Process\Process;

/**
 * Command used to update the project.
 */
class RunParatestCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    private $output;

    private $process;

    private $testDbPath;

    private $phpunit;

    /**
     * Configuration of the command.
     */
    protected function configure(): void
    {
        $this
            ->setName('paratest:run')
            ->setDescription('Run phpunit tests with multiple processes')
            // Pass arguments from this command "paratest:run" to the paratest command.
            ->addArgument('options', InputArgument::OPTIONAL, 'Options')
        ;
    }

    protected function prepare(): void
    {
        $this->phpunit = $this->container->getParameter('liip_functional_test.paratest.phpunit');
        $this->process = $this->container->getParameter('liip_functional_test.paratest.process');
    }

    /**
     * Content of the command.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->prepare();
        if (true !== is_file('vendor/bin/paratest')) {
            $this->output->writeln('Error : Install paratest first');

            return 1;
        } else {
            $this->output->writeln('Done...Running test.');
            $runProcess = new Process(['vendor/bin/paratest '.
                '-c phpunit.xml.dist '.
                '--phpunit '.$this->phpunit.' '.
                '-p '.$this->process.' '.
                $input->getArgument('options'),
            ]);
            $runProcess->run(function ($type, $buffer) use ($output): void {
                $output->write($buffer);
            });

            return 0;
        }
    }
}
