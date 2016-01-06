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
    protected $container;
    protected $em;

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('command:test')
            ->setDescription('Test command')
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output); //initialize parent class method

        $this->container = $this->getContainer();

        // This loads Doctrine, you can load your own services as well
        $this->em = $this->container->get('doctrine')
            ->getManager();
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $user = $this->em
            ->getRepository('LiipFunctionalTestBundle:User')
            ->find(1);

        $output->writeln('Name: '.$user->getName());
        $output->writeln('Email: '.$user->getEmail());
    }
}
