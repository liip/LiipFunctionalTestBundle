<?php

namespace TestBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class TestCommand extends Command
{
    protected function configure()
    {
        $this->setName('test:foo');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->write("I'm the test:foo command!");
    }
}
