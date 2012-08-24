<?php

namespace Liip\FunctionalTestBundle\Tests\Functional;

use Liip\FunctionalTestBundle\Tests\Functional\app\BaseFunctionalTestCase;

use Symfony\Component\Security\Core\User\UserInterface;

// A User test class needed to validate login
class TestUser implements UserInterface
{
    function getUsername()      { return 'john';}
    function getRoles()         { return array('ROLE_ADMIN');}
    function getPassword()      { return 'PasSwoRd';}
    function getSalt()          { return '123';}
    function eraseCredentials() {}
    function equals(UserInterface $user) { return $user->getUsername() == 'john';}
}

class SecurityTest extends BaseFunctionalTestCase
{
    function testMakeClient()
    {
        // First test acces without been login
        $client = $this->makeClient(false);
        $client->request('GET', '/secure');
        $this->assertEquals(401, $client->getResponse()->getStatusCode());

        // Now test access been login
        $this->markTestSkipped('This is not working yet...');
        $user = new TestUser();
        $this->loginAs($user, 'main');
        $client = $this->makeClient(true);
        $client->request('GET', '/secure');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains('Hello john', $client->getResponse()->getContent());
    }
}