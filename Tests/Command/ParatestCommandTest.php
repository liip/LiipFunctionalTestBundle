<?php

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\FunctionalTestBundle\Tests\Command;

use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;

class ParatestCommandTest extends WebTestCase
{
    /**
     * Test paratestCommand.
     */
    public function testParatest()
    {
        $kernel = $this->getContainer()->get('kernel');
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput(array(
           'command' => 'test:run', ));

        if (!class_exists('Symfony\Component\Console\Output\BufferedOutput')) {
            $output = new \Symfony\Component\Console\Output\StreamOutput(tmpfile(), \Symfony\Component\Console\Output\StreamOutput::VERBOSITY_NORMAL);
            $application->run($input, $output);
            rewind($output->getStream());
            $content = stream_get_contents($output->getStream());
        } else {
            $output = new \Symfony\Component\Console\Output\BufferedOutput();
            $application->run($input, $output);
            $content = $output->fetch();
        }

        $this->assertContains('Initial schema created', $content);
        $this->assertContains('Done...Running test.', $content);
    }
}
