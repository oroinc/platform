<?php

namespace Oro\Bundle\SecurityBundle\Tests\Functional\EventListener;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @dbIsolation
 */
class RefreshContextListenerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
    }

    public function testSecurityContextAfterClear()
    {
        // any route just to initialize security context
        $this->client->request('GET', $this->getUrl('oro_user_index'));

        $token = $this->getContainer()->get('security.context')->getToken();
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

    /**
     * @param EntityManager $entityManager
     * @param TokenInterface $token
     */
    protected function assertTokenEntities(EntityManager $entityManager, TokenInterface $token)
    {
        $unitOfWork = $entityManager->getUnitOfWork();

        $this->assertEquals(
            UnitOfWork::STATE_MANAGED,
            $unitOfWork->getEntityState($token->getUser())
        );

        if ($token instanceof OrganizationContextTokenInterface) {
            $this->assertEquals(
                UnitOfWork::STATE_MANAGED,
                $unitOfWork->getEntityState($token->getOrganizationContext())
            );
        }
    }

    public function testUserReloadFailed()
    {
        // any route just to initialize security context
        $this->client->request('GET', $this->getUrl('oro_user_index'));

        $this->getContainer()->get('security.context')->getToken()->setUser(new User());

        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $this->assertInstanceOf('Doctrine\ORM\EntityManager', $entityManager);

        $entityManager->clear();
        $this->assertNull($this->getContainer()->get('security.context')->getToken());
    }

    public function testRefreshNotExistingUser()
    {
        // any route just to initialize security context
        $this->client->request('GET', $this->getUrl('oro_user_index'));
        $user = new User();
        $reflection = new \ReflectionProperty(get_class($user), 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($user, 999);

        $this->getContainer()->get('security.context')->getToken()->setUser($user);

        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $this->assertInstanceOf('Doctrine\ORM\EntityManager', $entityManager);

        $entityManager->clear();
        $this->assertNull($this->getContainer()->get('security.context')->getToken());
    }
}
