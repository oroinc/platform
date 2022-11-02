<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\SecurityBundle\Tests\Functional\DataFixtures\SetRolePermissionsTrait;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;
use Oro\Bundle\UserBundle\Entity\Role;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadUserData extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;
    use SetRolePermissionsTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var AclManager $aclManager */
        $aclManager = $this->container->get('oro_security.acl.manager');
        $this->setPermissions(
            $aclManager,
            $manager->getRepository(Role::class)->findOneBy(['role' => 'ROLE_ADMINISTRATOR']),
            ['entity:' . TestActivity::class => []]
        );
        $aclManager->flush();
    }
}
