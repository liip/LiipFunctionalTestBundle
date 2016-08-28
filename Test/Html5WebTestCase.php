<?php

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\FunctionalTestBundle\Test;

/**
 * @author Daniel Barsotti
 *
 * The on-line validator: https://validator.nu/
 * The documentation: https://about.validator.nu/
 * Documentation about the web service: https://github.com/validator/validator/wiki/Service:-HTTP-interface
 */
abstract class Html5WebTestCase extends WebTestCase
{
    // <title> can't be empty:
    //
    // HTML5 validation failed:
    //  Line 5: Element “title” must not be empty.
    protected $html5Wrapper = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>HTML5</title>
</head>
<body>
<<CONTENT>>
</body>
</html>
HTML;

    protected $validationServiceAvailable = false;

    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->validationServiceAvailable = $this->isValidationServiceAvailable();
    }

    /**
     * Check if the HTML5 validation service is available.
     */
    public function isValidationServiceAvailable()
    {
        $validationUrl = $this->getHtml5ValidatorServiceUrl();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $validationUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($ch);

        curl_close($ch);

        return $res !== false;
    }

    public function getValidationServiceAvailable()
    {
        return $this->validationServiceAvailable;
    }

    /**
     * Get the URL of the HTML5 validation service from the config.
     *
     * @return string
     */
    protected function getHtml5ValidatorServiceUrl()
    {
        return $this->getContainer()->getParameter('liip_functional_test.html5validation.url');
    }

    /**
     * Allows a subclass to set a custom HTML5 wrapper to test validity of HTML snippets.
     * The $wrapper may contain valid HTML5 code + the <<CONTENT>> placeholder.
     * This placeholder will be replaced by the tested snippet before validation.
     *
     * @param string $wrapper The custom HTML5 wrapper
     */
    protected function setHtml5Wrapper($wrapper)
    {
        $this->html5Wrapper = $wrapper;
    }

    /**
     * Run the HTML5 validation on the content and returns the results as an array.
     *
     * @param string $content The HTML content to validate
     *
     * @return array|false
     */
    public function validateHtml5($content)
    {
        $validationUrl = $this->getHtml5ValidatorServiceUrl();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $validationUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array(
            'out' => 'json',
            'parser' => 'html5',
            'content' => $content,
        ));

        $res = curl_exec($ch);

        curl_close($ch);

        if ($res) {
            return json_decode($res);
        }

        // The HTML5 validation service could not be reached
        return false;
    }

    /**
     * Assert the content passed is valid HTML5. If yes, the function silently
     * runs, and if no it will make the test fail and display a summary of the
     * validation errors along with the line number where they occured.
     *
     * Warning: this function is completely agnostic about what it is actually
     * validating, thus it cannot display the validated URL. It is up to the
     * test programmer to use the $message parameter to add information about the
     * current URL. When the test fails, the message will be displayed in addition
     * to the error summary.
     *
     * @param string $content The content to validate
     * @param string $message An additional message to display when the test fails
     */
    public function assertIsValidHtml5($content, $message = '')
    {
        if (!$this->getValidationServiceAvailable()) {
            return $this->skipTestWithInvalidService();
        }

        $res = $this->validateHtml5($content);
        // json_decode returned null if the validator service didn't
        // return JSON
        if ((null === $res) || (false === $res) || (false === $res->messages)) {
            return $this->skipTestWithInvalidService();
        }

        $err_count = 0;
        $err_msg = 'HTML5 validation failed';
        if ($message != '') {
            $err_msg .= " [$message]";
        }
        $err_msg .= ":\n";

        $ignores = $this->getContainer()->getParameter('liip_functional_test.html5validation.ignores');
        /*
         * unfortunately, the bamboo html5 validator.nu gives back an empty "message" about the error with brightcove object, but we have to ignore the error
         * if our local validator.nu instance is fixed, this stuff should go away
         */
        $ignores_extract = $this->getContainer()->getParameter('liip_functional_test.html5validation.ignores_extract');

        foreach ($res->messages as $row) {
            if ($row->type == 'error') {
                foreach ($ignores as $ignore) {
                    if (preg_match($ignore, $row->message)) {
                        continue 2;
                    }
                }
                foreach ($ignores_extract as $ignore_extract) {
                    if (preg_match($ignore_extract, $row->extract)) {
                        continue 2;
                    }
                }

                ++$err_count;
                if (empty($row->message)) {
                    $err_msg .= "  Line {$row->lastLine}: Empty error message about {$row->extract}\n";
                } else {
                    $err_msg .= "  Line {$row->lastLine}: {$row->message}\n";
                }
            }
        }
        $this->assertTrue($err_count == 0, $err_msg);
    }

    /**
     * Assert the content passed is a valid HTML5 snippets (i.e. that the content,
     * wrapped into a basic HTML5 document body will pass the validation).
     *
     * @param string $snippet The snippet to validate
     * @param string $message An additional message to display when the test fails
     */
    public function assertIsValidHtml5Snippet($snippet, $message = '')
    {
        $content = str_replace('<<CONTENT>>', $snippet, $this->html5Wrapper);
        $this->assertIsValidHtml5($content, $message);
    }

    /**
     * Marks test as skipped with validation server error message.
     */
    protected function skipTestWithInvalidService()
    {
        $url = $this->getHtml5ValidatorServiceUrl();
        $this->markTestSkipped("HTML5 Validator service not found at '$url' !");
    }
}
