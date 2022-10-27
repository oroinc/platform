<?php

namespace Oro\Bundle\DataGridBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\DataGridBundle\Entity\AbstractGridView;
use Oro\Bundle\DataGridBundle\Entity\AbstractGridViewUser;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\UserBundle\Entity\User;

abstract class AbstractDataGridRepositoryTest extends WebTestCase
{
    /** @var EntityRepository */
    protected $repository;

    /** @var AclHelper */
    protected $aclHelper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->initClient();

        $this->aclHelper = self::getContainer()->get('oro_security.acl_helper');
    }

    /**
     * @param AbstractGridView|AbstractGridViewUser $needle
     * @param array|AbstractGridView[]|AbstractGridViewUser[] $haystack
     */
    protected function assertGridViewExists($needle, array $haystack)
    {
        $found = false;

        foreach ($haystack as $view) {
            if ($view->getId() === $needle->getId()) {
                $found = true;
                break;
            }
        }

        self::assertTrue(
            $found,
            sprintf(
                'GridView with id "%d" not found in array "%s"',
                $needle->getId(),
                implode(', ', array_map(static fn ($item) => $item->getId(), $haystack))
            )
        );
    }

    abstract protected function getUsername(): string;

    /**
     * @return AbstractUser
     */
    protected function getUser()
    {
        /** @var AbstractUser $user */
        $user = $this->getUserRepository()->findOneBy(['username' => $this->getUsername()]);

        $this->setUpTokenStorage($user);

        return $user;
    }

    protected function setUpTokenStorage(AbstractUser $user)
    {
        $token = new UsernamePasswordOrganizationToken(
            $user,
            false,
            'main',
            $user->getOrganization(),
            $user->getUserRoles()
        );

        self::getContainer()->get('security.token_storage')->setToken($token);
    }

    protected function getUserRepository(): EntityRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(User::class);
    }
}
