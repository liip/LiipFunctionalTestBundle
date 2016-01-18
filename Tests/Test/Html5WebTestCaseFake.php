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
 * {@inheritdoc}
 * Extends Html5WebTestCase and return fake results.
 */
class Html5WebTestCaseFake extends Html5WebTestCase
{
    /**
     * {@inheritdoc}
     * Fake that the validator service is available.
     */
    public function isValidationServiceAvailable()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     * Return fake correct result.
     */
    public function validateHtml5($content)
    {
        return (object) array('messages' => array());
    }
}
