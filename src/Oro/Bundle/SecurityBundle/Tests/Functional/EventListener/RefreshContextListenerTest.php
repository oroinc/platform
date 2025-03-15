<?php

namespace Oro\Bundle\SecurityBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class RefreshContextListenerTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
    }

    private function assertTokenEntities(EntityManagerInterface $entityManager, TokenInterface $token): void
    {
        $unitOfWork = $entityManager->getUnitOfWork();

        self::assertEquals(
            UnitOfWork::STATE_MANAGED,
            $unitOfWork->getEntityState($token->getUser())
        );

        if ($token instanceof OrganizationAwareTokenInterface) {
            self::assertEquals(
                UnitOfWork::STATE_MANAGED,
                $unitOfWork->getEntityState($token->getOrganization())
            );
        }
    }

    public function testSecurityContextAfterClear(): void
    {
        // any route just to initialize security context
        $this->client->request('GET', $this->getUrl('oro_user_index'));

        $token = self::getContainer()->get('security.token_storage')->getToken();
        self::assertNotEmpty($token);
        self::assertInstanceOf(TokenInterface::class, $token);

        $entityManager = self::getContainer()->get('doctrine')->getManager();
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        // entities must be fresh before clear
        $this->assertTokenEntities($entityManager, $token);

        $entityManager->clear();

        // ...and after clear
        $this->assertTokenEntities($entityManager, $token);
    }

    public function testUserReloadFailed(): void
    {
        // any route just to initialize security context
        $this->client->request('GET', $this->getUrl('oro_user_index'));

        self::getContainer()->get('security.token_storage')->getToken()->setUser(new User());

        $entityManager = self::getContainer()->get('doctrine')->getManager();
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        $entityManager->clear();
        self::assertNull(self::getContainer()->get('security.token_storage')->getToken());
    }

    public function testRefreshNotExistingUser(): void
    {
        // any route just to initialize security context
        $this->client->request('GET', $this->getUrl('oro_user_index'));
        $user = new User();
        ReflectionUtil::setId($user, 999);

        self::getContainer()->get('security.token_storage')->getToken()->setUser($user);

        $entityManager = self::getContainer()->get('doctrine')->getManager();
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        $entityManager->clear();
        self::assertNull(self::getContainer()->get('security.token_storage')->getToken());
    }
}
