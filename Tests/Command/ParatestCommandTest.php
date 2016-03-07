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

class ParatestCommandTest extends WebTestCase
{
    private $display;

    /**
     * This method tests both the default setting of `runCommand()` and the kernel reusing, as, to reuse kernel,
     * it is needed a kernel is yet instantiated. So we test these two conditions here, to not repeat the code.
     */
    public function testParatest()
    {
        // Run command without options
        $this->display = $this->runCommand('test:run');
        // Test default values
        $this->assertContains('Initial schema created', $this->display);
        $this->assertNotContains('Can t populate', $this->display);
        $this->assertContains('Initial schema populated, duplicating....', $this->display);
        $this->assertContains('Done...Running test.', $this->display);

    }
}
