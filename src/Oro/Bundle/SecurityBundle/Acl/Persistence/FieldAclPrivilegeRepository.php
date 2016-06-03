<?php

namespace Oro\Bundle\SecurityBundle\Acl\Persistence;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface as SID;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity as OID;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\SecurityBundle\Acl\Extension\FieldAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Permission\MaskBuilder;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionInterface;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\SecurityBundle\Model\AclPrivilegeIdentity;
use Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadata;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Model\AclPermission;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;

class FieldAclPrivilegeRepository extends AclPrivilegeRepository
{
    /** @var EntityFieldProvider */
    protected $fieldProvider;

    /**
     * @param AclManager          $manager
     * @param TranslatorInterface $translator
     * @param EntityFieldProvider $fieldProvider
     */
    public function __construct(
        AclManager $manager,
        TranslatorInterface $translator,
        EntityFieldProvider $fieldProvider
    ) {
        parent::__construct($manager, $translator);

        $this->fieldProvider = $fieldProvider;
    }

    /**
     * @param SID    $sid
     * @param string $className
     *
     * @return ArrayCollection|AclPrivilege[]
     */
    public function getFieldsPrivileges(SID $sid, $className)
    {
        $extensionKey = FieldAclExtension::NAME;
        $extension = $this->manager->getExtensionSelector()->selectByExtensionKey($extensionKey);
        $entityClass = $this->getClassMetadata($className, $extension);
        $objectIdentity = new OID($extensionKey, $className);
        $oids[] = $objectIdentity;
        $acls = $this->findAcls($sid, $oids);

        // with relations, without virtual and unidirectional fields, without entity details and without exclusions
        // there could be ACL AclExclusionProvider to filter restricted fields, so for ACL UI it shouldn't be used
        $fieldsArray = $this->fieldProvider->getFields($className, true, false, false, false, false);
        $privileges = new ArrayCollection();
        foreach ($fieldsArray as $fieldInfo) {
            if (array_key_exists('identifier', $fieldInfo) && $fieldInfo['identifier']) {
                // we should not limit access to identifier fields.
                continue;
            }

            $privilege = new AclPrivilege();
            $privilege->setIdentity(
                new AclPrivilegeIdentity(
                    sprintf(
                        '%s+%s:%s',
                        $objectIdentity->getIdentifier(),
                        $fieldInfo['name'],
                        $objectIdentity->getType()
                    ),
                    $fieldInfo['label']
                )
            );
            $privilege->setGroup($entityClass->getGroup())
                ->setExtensionKey($extensionKey);

            $this->addFieldPermissions($sid, $privilege, $objectIdentity, $acls, $extension, $fieldInfo['name']);
            $privileges->add($privilege);
        }

        $this->sortPrivileges($privileges);

        return $privileges;
    }

    /**
     * @param SID             $sid
     * @param OID             $oid
     * @param ArrayCollection $privileges
     *
     * @throws \Exception
     */
    public function saveFieldPrivileges(SID $sid, OID $oid, ArrayCollection $privileges)
    {
        $extension = $this->manager->getExtensionSelector()->select('field:' . $oid->getType());

        /** @var MaskBuilder[] $maskBuilders */
        $maskBuilders = [];
        $this->prepareMaskBuilders($maskBuilders, $extension);

        /** @var AclPrivilege $privilege */
        foreach ($privileges as $privilege) {
            // compile masks
            $masks = $this->getPermissionMasks($privilege->getPermissions(), $extension, $maskBuilders);

            $fieldName = explode('+', explode(':', $privilege->getIdentity()->getId())[0])[1];

            foreach ($this->manager->getFieldAces($sid, $oid, $fieldName) as $ace) {
                if (!$ace->isGranting()) {
                    // denying ACE is not supported
                    continue;
                }

                $mask = $this->findSimilarMask($masks, $ace->getMask(), $extension);

                // as we have already processed $mask, remove it from $masks collection
                if ($mask !== false) {
                    $this->manager->setFieldPermission($sid, $oid, $fieldName, $mask);
                    $this->removeMask($masks, $mask);
                }
            }

            // check if we have new masks so far, and process them if any
            foreach ($masks as $mask) {
                $this->manager->setFieldPermission($sid, $oid, $fieldName, $mask);
            }
        }

        $this->manager->flush();
    }

    /**
     * @param string                $className
     * @param AclExtensionInterface $extension
     *
     * @return EntitySecurityMetadata
     */
    protected function getClassMetadata($className, $extension)
    {
        $entityClasses = array_filter(
            $extension->getClasses(),
            function (EntitySecurityMetadata $entityMetadata) use ($className) {
                return $entityMetadata->getClassName() == $className;
            }
        );

        return reset($entityClasses);
    }

    /**
     * Adds field permissions to the given $privilege.
     *
     * @param SID                   $sid
     * @param AclPrivilege          $privilege
     * @param OID                   $oid
     * @param \SplObjectStorage     $acls
     * @param AclExtensionInterface $extension
     * @param string                $field
     */
    protected function addFieldPermissions(
        SID $sid,
        AclPrivilege $privilege,
        OID $oid,
        \SplObjectStorage $acls,
        AclExtensionInterface $extension,
        $field
    ) {
        $allowedPermissions = $extension->getAllowedPermissions($oid, $field);
        $acl = $this->findAclByOid($acls, $oid);
        $this->addAclPermissions($sid, $field, $privilege, $allowedPermissions, $extension, null, $acl);

        // add default permission for not found in db privileges. By default it should be the System access level.
        foreach ($allowedPermissions as $permission) {
            if (!$privilege->hasPermission($permission)) {
                $privilege->addPermission(new AclPermission($permission, AccessLevel::SYSTEM_LEVEL));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getPermissionMasks($permissions, AclExtensionInterface $extension, array $maskBuilders)
    {
        // check if there are no full field permissions and add missing to calculate correct masks.
        // This case can be if some field have no all the permissions. In this case we should grant access to the
        // absent permissions.
        $permissionNames = array_keys($maskBuilders);
        foreach ($permissionNames as $permissionName) {
            /** @var ArrayCollection $permissions */
            if (!$permissions->containsKey($permissionName)) {
                $permissions->add(new AclPermission($permissionName, AccessLevel::SYSTEM_LEVEL));
            }
        }

        return parent::getPermissionMasks($permissions, $extension, $maskBuilders);
    }
}
