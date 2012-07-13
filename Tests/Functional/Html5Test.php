<?php

namespace Liip\FunctionalTestBundle\Tests\Functional;

use Liip\FunctionalTestBundle\Tests\Functional\app\BaseFunctionalTestCase;

class Html5Test extends BaseFunctionalTestCase
{
    protected $html5Valid = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title></title>
</head>
<body>
    <h1>Welcome on the test page</h1>
    <p>Lorem ipsum dolor si samet</p>
</body>
</html>
HTML;

    protected $html5Invalid = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title></title>
</head>
<body>
    <h1>Welcome on the test page</h1>
    <p>Lorem ipsum dolor si samet
        <invalidTag>foo</invalidTag>
    </p>
</body>
</html>
HTML;


    // Theses 3 functions allow to locally change the service url for test purpose
    protected $tempUrl = null; 
    function temporaryChangeServiceUrl($newUrl){
        $this->tempUrl = $newUrl;
    }
    function restoreServiceUrl(){
        $this->tempUrl = null;
    }
    function getHtml5ValidatorServiceUrl() {
        if ($this->tempUrl !== null){
            return $this->tempUrl;
        }
        return parent::getHtml5ValidatorServiceUrl();
    }

    function testGetHtml5ValidatorServiceUrl()
    {
        $this->assertEquals('http://validator.nu/', $this->getHtml5ValidatorServiceUrl());
        $this->temporaryChangeServiceUrl('http://foo');
        $this->assertEquals('http://foo', $this->getHtml5ValidatorServiceUrl());
        $this->restoreServiceUrl();
        $this->assertEquals('http://validator.nu/', $this->getHtml5ValidatorServiceUrl());
        
    }

    function testValidateHtml5()
    {
        // Test when service is not avaliable
        $this->temporaryChangeServiceUrl('file://no.validator');
        $errors = $this->validateHtml5('html');
        $this->assertEquals(false, $errors);
        $this->restoreServiceUrl();

        // Test on the normal service with valid html
        $error = $this->validateHtml5($this->html5Valid);
        $this->assertEquals(true, is_object($error), '->validateHtml5() should return an object');
        $this->assertEquals(true, is_array($error->messages), '$result->messages must be an array');
        $this->assertCount(0, $error->messages);

        // Test on the normal service with valid html
        $error = $this->validateHtml5($this->html5Invalid);
        $this->assertEquals(true, is_object($error), '->validateHtml5() should return an object');
        $this->assertEquals(true, is_array($error->messages), '$result->messages must be an array');
        $this->assertCount(1, $error->messages);
    }

    function testAssertIsValidHtml5()
    {
        $this->assertIsValidHtml5($this->html5Valid);
    }

    function testAssertIsValidHtml5Snippet()
    {
        $this->assertIsValidHtml5Snippet('<h1>I\'m valid</h1>');
    }

    function testAssertIsValidHtml5SnippetWithCustomWrapper()
    {
        $this->setHtml5Wrapper('<html><body><<CONTENT>></body></html>');
        $this->assertIsValidHtml5Snippet('<h1>I\'m valid</h1>');
    }

}