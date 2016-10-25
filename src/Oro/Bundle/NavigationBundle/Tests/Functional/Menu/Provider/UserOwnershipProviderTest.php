<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\Menu\Provider;

use Oro\Bundle\NavigationBundle\Menu\Provider\UserOwnershipProvider;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * @dbIsolation
 */
class UserOwnershipProviderTest extends WebTestCase
{
    /** @var UserOwnershipProvider */
    protected $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures([
            'Oro\Bundle\NavigationBundle\Tests\Functional\DataFixtures\LoadMenuUpdateData'
        ]);

        $this->provider = $this->getContainer()->get('oro_navigation.ownership_provider.user');

        $user = $this->getContainer()->get('doctrine')
            ->getRepository('OroUserBundle:User')
            ->findOneBy(['email' => 'simple_user@example.com']);

        $token = new UsernamePasswordToken($user, false, 'key');
        $this->client->getContainer()->get('security.token_storage')->setToken($token);
    }

    public function testGetMenuUpdates()
    {
        $updates = $this->provider->getMenuUpdates('application_menu');

        $this->assertCount(2, $updates);
    }
}
