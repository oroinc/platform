<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Security;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Security\UserProvider;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData;

class UserProviderTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadUserData::SIMPLE_USER, LoadUserData::SIMPLE_USER_PASSWORD)
        );
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadUserData::class]);
    }

    private function refreshUser($user)
    {
        /** @var UserProvider $userProvider */
        $userProvider = $this->getContainer()->get('oro_user.tests.security.provider');

        return $userProvider->refreshUser($user);
    }

    public function testUserReloadWhenEntityIsChangedByReference()
    {
        // init tokens
        $this->client->request('GET', $this->getUrl('oro_user_profile_view'));
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        /** @var User $loggedUser */
        $loggedUser = $this->getContainer()->get('oro_security.token_accessor')->getUser();
        $originalId = $loggedUser->getId();
        $this->assertInstanceOf(User::class, $loggedUser);
        $this->assertSame(LoadUserData::SIMPLE_USER, $loggedUser->getUsername(), 'logged user username');

        /** @var User $customerUser */
        $customerUser = $this->getReference(LoadUserData::SIMPLE_USER);
        $customerUser->setUsername(LoadUserData::SIMPLE_USER_2);
        $this->assertSame(LoadUserData::SIMPLE_USER_2, $loggedUser->getUsername(), 'username after change');
        $this->assertSame($originalId, $customerUser->getId());
        $this->assertSame($originalId, $loggedUser->getId());

        $this->refreshUser($customerUser);

        $this->assertSame(LoadUserData::SIMPLE_USER, $loggedUser->getUsername(), 'username after refresh');
        $this->assertSame($originalId, $loggedUser->getId());
    }

    public function testReloadUserWithNotManagedEntity()
    {
        // init tokens
        $this->client->request('GET', $this->getUrl('oro_user_profile_view'));
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        /** @var User $loggedUser */
        $loggedUser = $this->getContainer()->get('oro_security.token_accessor')->getUser();
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(ClassUtils::getClass($loggedUser));

        $originalId = $loggedUser->getId();
        $this->assertInstanceOf(User::class, $loggedUser);
        $this->assertSame(LoadUserData::SIMPLE_USER, $loggedUser->getUsername(), 'logged user username');

        $loggedUser->setUsername(LoadUserData::SIMPLE_USER_2);
        $em->detach($loggedUser);

        $loggedUser = $this->refreshUser($loggedUser);

        $this->assertSame(LoadUserData::SIMPLE_USER, $loggedUser->getUsername(), 'username after refresh');
        $this->assertSame($originalId, $loggedUser->getId());
    }
}
