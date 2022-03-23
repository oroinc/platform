<?php

namespace Oro\Bundle\EmailBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadRolesData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Updates root ACE of the ADMINISTRATOR role to set VIEW_PRIVATE permission to SYSTEM access level.
 */
class UpdateViewPrivateRootPermissionForAdminRole extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadRolesData::class];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        if (!$this->container->get(ApplicationState::class)->isInstalled()) {
            return;
        }

        /** @var AclManager $aclManager */
        $aclManager = $this->container->get('oro_security.acl.manager');
        if (!$aclManager->isAclEnabled()) {
            return;
        }

        $sid = $aclManager->getSid(LoadRolesData::ROLE_ADMINISTRATOR);
        foreach ($aclManager->getAllExtensions() as $extension) {
            $rootOid = $aclManager->getRootOid($extension->getExtensionKey());
            foreach ($extension->getAllMaskBuilders() as $maskBuilder) {
                $mask = $maskBuilder->hasMaskForGroup('SYSTEM')
                    ? $maskBuilder->getMaskForGroup('SYSTEM')
                    : $maskBuilder->getMaskForGroup('ALL');
                $aclManager->setPermission($sid, $rootOid, $mask, true);
            }
        }

        $aclManager->flush();
    }
}
