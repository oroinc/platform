<?php

namespace Oro\Bundle\SecurityBundle\Acl\Domain;

use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionSelector;
use Symfony\Component\Security\Acl\Domain\Acl;
use Symfony\Component\Security\Acl\Domain\FieldEntry;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Model\AclInterface;

/**
 * Fills the root ACL with dynamic generated full access root field ACEs for Role SIDs.
 * This prevents the adding root field ACEs to the DB.
 */
class FullAccessFieldRootAclBuilder
{
    private AclExtensionSelector $extensionSelector;

    public function __construct(AclExtensionSelector $extensionSelector)
    {
        $this->extensionSelector = $extensionSelector;
    }

    /**
     * Fills the root ACL with dynamic generated full access root field ACEs for Role SIDs
     */
    public function fillFieldRootAces(AclInterface $acl, array $sids = []): void
    {
        $fieldName = RootAclWrapper::ROOT_FIELD_NAME;

        $rootOid = $acl->getObjectIdentity();
        $extension = $this->extensionSelector->selectByExtensionKey($rootOid->getIdentifier());
        if (null === $extension) {
            return;
        }
        $fieldExtension = $extension->getFieldExtension();
        if (null === $fieldExtension) {
            return;
        }
        $maskBuilders = $fieldExtension->getAllMaskBuilders();

        $rootEntries = [];
        foreach ($sids as $sid) {
            if (!$sid instanceof RoleSecurityIdentity) {
                continue;
            }

            foreach ($maskBuilders as $maskBuilder) {
                $rootEntries[$fieldName][] = new FieldEntry(
                    null,
                    $acl,
                    $fieldName,
                    $sid,
                    'all',
                    $maskBuilder->getMaskForGroup('SYSTEM'),
                    true,
                    false,
                    false
                );
            }
        }

        if (!empty($rootEntries)) {
            $this->addRootEntriesToAcl($acl, $rootEntries);
        }
    }

    private function addRootEntriesToAcl(AclInterface $acl, array $rootEntries): void
    {
        $aclReflection = new \ReflectionClass(Acl::class);
        $aclClassFieldAcesProperty = $aclReflection->getProperty('classFieldAces');
        $aclClassFieldAcesProperty->setAccessible(true);
        $aclClassFieldAcesProperty->setValue($acl, $rootEntries);
    }
}
