<?php

namespace Oro\Bundle\SecurityBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class RefreshContextListenerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
    }

    public function testSecurityContextAfterClear()
    {
        // any route just to initialize security context
        $this->client->request('GET', $this->getUrl('oro_user_index'));

        $token = $this->getContainer()->get('security.token_storage')->getToken();
        $this->assertNotEmpty($token);
        $this->assertInstanceOf('Symfony\Component\Security\Core\Authentication\Token\TokenInterface', $token);

        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $this->assertInstanceOf('Doctrine\ORM\EntityManager', $entityManager);

        // entities must be fresh before clear
        $this->assertTokenEntities($entityManager, $token);

        $entityManager->clear();

        // ...and after clear
        $this->assertTokenEntities($entityManager, $token);
    }

    private function assertTokenEntities(EntityManager $entityManager, TokenInterface $token): void
    {
        $unitOfWork = $entityManager->getUnitOfWork();

        $this->assertEquals(
            UnitOfWork::STATE_MANAGED,
            $unitOfWork->getEntityState($token->getUser())
        );

        if ($token instanceof OrganizationAwareTokenInterface) {
            $this->assertEquals(
                UnitOfWork::STATE_MANAGED,
                $unitOfWork->getEntityState($token->getOrganization())
            );
        }
    }

    public function testUserReloadFailed()
    {
        // any route just to initialize security context
        $this->client->request('GET', $this->getUrl('oro_user_index'));

        $this->getContainer()->get('security.token_storage')->getToken()->setUser(new User());

        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $this->assertInstanceOf('Doctrine\ORM\EntityManager', $entityManager);

        $entityManager->clear();
        $this->assertNull($this->getContainer()->get('security.token_storage')->getToken());
    }

    public function testRefreshNotExistingUser()
    {
        // any route just to initialize security context
        $this->client->request('GET', $this->getUrl('oro_user_index'));
        $user = new User();
        ReflectionUtil::setId($user, 999);

        $this->getContainer()->get('security.token_storage')->getToken()->setUser($user);

        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $this->assertInstanceOf('Doctrine\ORM\EntityManager', $entityManager);

        $entityManager->clear();
        $this->assertNull($this->getContainer()->get('security.token_storage')->getToken());
    }
}
