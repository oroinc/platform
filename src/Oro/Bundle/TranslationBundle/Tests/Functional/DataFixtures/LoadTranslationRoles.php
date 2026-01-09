<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\SecurityBundle\Tests\Functional\DataFixtures\SetRolePermissionsTrait;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

class LoadTranslationRoles extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;
    use SetRolePermissionsTrait;

    public const ROLE_TRANSLATOR = 'ROLE_TRANSLATOR';

    #[\Override]
    public function load(ObjectManager $manager)
    {
        $role = new Role(self::ROLE_TRANSLATOR);
        $role->setLabel('Translator');
        $manager->persist($role);
        $manager->flush();

        /** @var AclManager $aclManager */
        $aclManager = $this->container->get('oro_security.acl.manager');
        $this->setPermissions(
            $aclManager,
            $role,
            [
                'entity:' . Language::class => ['VIEW_SYSTEM', 'EDIT_SYSTEM', 'CREATE_SYSTEM', 'TRANSLATE_SYSTEM'],
                'action:oro_importexport_import' => ['EXECUTE'],
                'action:oro_importexport_export' => ['EXECUTE'],
            ]
        );
        $aclManager->flush();
    }
}
