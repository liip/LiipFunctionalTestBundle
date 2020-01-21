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

namespace Liip\Acme\Tests\App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class TestInteractiveCommand extends Command
{
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('liipfunctionaltestbundle:test:interactive')
            ->setDescription('Interactive test command')
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $question = new Question('Please enter the input', 'AcmeDemoBundle');

        $answer = $helper->ask($input, $output, $question);

        $output->writeln(PHP_EOL);
        $output->writeln(sprintf('Value of answer: %s', $answer));

        return 0;
    }
}
