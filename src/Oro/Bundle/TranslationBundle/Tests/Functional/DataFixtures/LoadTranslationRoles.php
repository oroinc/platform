<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\UserBundle\Entity\Role;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

class LoadTranslationRoles extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    const ROLE_TRANSLATOR = 'ROLE_TRANSLATOR';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->loadTranslatorRole($manager, $this->container->get('oro_security.acl.manager'));
    }

    /**
     * @param ObjectManager $objectManager
     * @param AclManager $aclManager
     */
    protected function loadTranslatorRole(ObjectManager $objectManager, AclManager $aclManager)
    {
        $role = new Role(self::ROLE_TRANSLATOR);
        $role->setLabel('Translator');

        $objectManager->persist($role);
        $objectManager->flush();

        if (!$aclManager->isAclEnabled()) {
            return;
        }

        $permissions = [
            sprintf('entity:%s', Language::class) => ['VIEW_LOCAL', 'EDIT_LOCAL', 'CREATE_LOCAL', 'TRANSLATE_LOCAL'],
            sprintf('action:oro_importexport_import') => ['EXECUTE'],
            sprintf('action:oro_importexport_export') => ['EXECUTE'],
        ];

        $this->setPermissions($aclManager, $aclManager->getSid($role), $permissions);
    }

    /**
     * @param AclManager $aclManager
     * @param SecurityIdentityInterface $sid
     * @param array $permissions
     */
    protected function setPermissions(AclManager $aclManager, SecurityIdentityInterface $sid, array $permissions)
    {
        foreach ($permissions as $permission => $acls) {
            $oid = $aclManager->getOid($permission);
            $extension = $aclManager->getExtensionSelector()->select($oid);
            $maskBuilders = $extension->getAllMaskBuilders();

            foreach ($maskBuilders as $maskBuilder) {
                $maskBuilder->reset();

                foreach ($acls as $acl) {
                    if ($maskBuilder->hasMask('MASK_' . $acl)) {
                        $maskBuilder->add($acl);
                    }
                }

                $aclManager->setPermission($sid, $oid, $maskBuilder->get());
            }
        }

        $aclManager->flush();
    }
}
