<?php

namespace Oro\Bundle\DataGridBundle\Tests\Functional\Entity\Repository;

use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\DataGridBundle\Entity\AbstractGridView;
use Oro\Bundle\DataGridBundle\Entity\AbstractGridViewUser;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\SecurityProBundle\ORM\Walker\AclHelper;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\AbstractUser;

abstract class AbstractDataGridRepositoryTest extends WebTestCase
{
    /** @var ObjectRepository */
    protected $repository;

    /** @var AclHelper */
    protected $aclHelper;

    protected function setUp()
    {
        parent::setUp();

        $this->initClient();

        $this->aclHelper = $this->getContainer()->get('oro_security.acl_helper');
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

        $this->assertTrue(
            $found,
            sprintf(
                'GridView with id "%d" not found in array "%s"',
                $needle->getId(),
                implode(
                    ', ',
                    array_map(
                        function ($item) {
                            /** @var AbstractGridView|AbstractGridViewUser $item */
                            return $item->getId();
                        },
                        $haystack
                    )
                )
            )
        );
    }

    /**
     * @return string
     */
    abstract protected function getUsername();

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

    /**
     * @param AbstractUser $user
     */
    protected function setUpTokenStorage(AbstractUser $user)
    {
        $token = new UsernamePasswordOrganizationToken(
            $user,
            false,
            'main',
            $user->getOrganization(),
            $user->getRoles()
        );

        $this->getContainer()->get('security.token_storage')->setToken($token);
    }

    /**
     * @return ObjectRepository
     */
    protected function getUserRepository()
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass('OroUserBundle:User')
            ->getRepository('OroUserBundle:User');
    }
}
