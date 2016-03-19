<?php

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\FunctionalTestBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestCommand extends ContainerAwareCommand
{
    private $container;

    protected function configure()
    {
        parent::configure();

        $this->setName('liipfunctionaltestbundle:test')
            ->setDescription('Test command');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->container = $this->getContainer();
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Symfony version check
        $version = \Symfony\Component\HttpKernel\Kernel::VERSION_ID;
        $output->writeln('Symfony version: '.$version);
        $output->writeln('Environment: '.$this->container->get('kernel')->getEnvironment());
        $output->writeln('Verbosity level set: '.$output->getVerbosity());

        // Check for the version of Symfony: 20803 is the 2.8
        if ($version >= 20803) {
            $output->writeln('Environment: '.$this->container->get('kernel')->getEnvironment(), OutputInterface::VERBOSITY_NORMAL);

            // Write a line with OutputInterface::VERBOSITY_NORMAL (also if this level is set by default by Console)
            $output->writeln('Verbosity level: NORMAL', OutputInterface::VERBOSITY_NORMAL);

            // Write a line with OutputInterface::VERBOSITY_VERBOSE
            $output->writeln('Verbosity level: VERBOSE', OutputInterface::VERBOSITY_VERBOSE);

            // Write a line with OutputInterface::VERBOSITY_VERY_VERBOSE
            $output->writeln('Verbosity level: VERY_VERBOSE', OutputInterface::VERBOSITY_VERY_VERBOSE);

            // Write a line with OutputInterface::VERBOSITY_DEBUG
            $output->writeln('Verbosity level: DEBUG', OutputInterface::VERBOSITY_DEBUG);
        } else {
            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
                $output->writeln('Verbosity level: NORMAL');
            }

            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                $output->writeln('Verbosity level: VERBOSE');
            }

            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
                $output->writeln('Verbosity level: VERY_VERBOSE');
            }

            if ($output->getVerbosity() == OutputInterface::VERBOSITY_DEBUG) {
                $output->writeln('Verbosity level: DEBUG');
            }
        }
    }
}
