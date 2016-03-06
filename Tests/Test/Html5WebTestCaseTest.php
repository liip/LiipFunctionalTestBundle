<?php

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\FunctionalTestBundle\Tests\Test;

use Liip\FunctionalTestBundle\Test\Html5WebTestCase;

/**
 * Test methods that are mocked in Html5WebTestCaseMockTest.
 */
class Html5WebTestCaseTest extends Html5WebTestCase
{
    public function __construct()
    {
        parent::__construct('', array(), '');
    }

    public function testGetValidationServiceAvailable()
    {
        $this->assertInternalType(
            'bool',
            $this->getValidationServiceAvailable()
        );
    }

    public function testGetHtml5ValidatorServiceUrl()
    {
        $this->assertSame(
            'https://validator.nu/',
            $this->getHtml5ValidatorServiceUrl()
        );
    }

    public function testSetHtml5Wrapper()
    {
        $this->setHtml5Wrapper('foo bar');

        $this->assertSame(
            'foo bar',
            $this->html5Wrapper
        );
    }
}
