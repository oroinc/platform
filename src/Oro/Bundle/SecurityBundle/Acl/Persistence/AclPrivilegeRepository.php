<?php

namespace Oro\Bundle\SecurityBundle\Acl\Persistence;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionInterface;
use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Oro\Bundle\SecurityBundle\Acl\Permission\MaskBuilder;
use Oro\Bundle\SecurityBundle\Metadata\ClassSecurityMetadata;
use Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadata;
use Oro\Bundle\SecurityBundle\Metadata\Label;
use Oro\Bundle\SecurityBundle\Model\AclPermission;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\SecurityBundle\Model\AclPrivilegeIdentity;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity as OID;
use Symfony\Component\Security\Acl\Exception\NotAllAclsFoundException;
use Symfony\Component\Security\Acl\Model\AclInterface;
use Symfony\Component\Security\Acl\Model\EntryInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface as SID;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The repository class that allows to manage Privilege models.
 * @see AclPrivilege
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class AclPrivilegeRepository
{
    public const ROOT_PRIVILEGE_NAME = '(default)';

    /** @var AclManager */
    protected $manager;

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(AclManager $manager, TranslatorInterface $translator)
    {
        $this->manager = $manager;
        $this->translator = $translator;
    }

    /**
     * Gets a list of all permission names supported by ACL extension which is responsible
     * to process domain objects of the given type(s).
     * In case when $extensionKeyOrKeys argument contains several keys this method returns
     * unique combination of all permission names supported by the requested ACL extensions.
     * For example if one ACL extension supports VIEW, CREATE and EDIT permissions
     * and another ACL extension supports VIEW and DELETE permissions,
     * the result will be: VIEW, CREATE, EDIT, DELETE
     *
     * @param string|string[] $extensionKeyOrKeys The ACL extension key(s)
     *
     * @return string[]
     */
    public function getPermissionNames($extensionKeyOrKeys)
    {
        if (is_string($extensionKeyOrKeys)) {
            return $this->manager->getExtensionSelector()->selectByExtensionKey($extensionKeyOrKeys)->getPermissions();
        }

        $result = [];
        foreach ($extensionKeyOrKeys as $extensionKey) {
            $extension = $this->manager->getExtensionSelector()->selectByExtensionKey($extensionKey);
            foreach ($extension->getPermissions() as $permission) {
                if (!in_array($permission, $result, true)) {
                    $result[] = $permission;
                }
            }
        }

        return $result;
    }

    /**
     * Gets all privileges associated with the given security identity.
     *
     * @param SID $sid
     * @param string|null $aclGroup
     *
     * @return ArrayCollection|AclPrivilege[]
     */
    public function getPrivileges(SID $sid, $aclGroup = null)
    {
        $privileges = new ArrayCollection();
        foreach ($this->manager->getAllExtensions() as $extension) {
            $extensionKey = $extension->getExtensionKey();
            // fill a list of object identities;
            // the root object identity is added to the top of the list (for performance reasons)
            /** @var ClassSecurityMetadata[] $classes */
            $classes = [];
            /** @var OID[] $oids */
            $oids = [];
            $extensionClasses = $extension->getClasses();
            foreach ($extensionClasses as $class) {
                $className = $class->getClassName();
                $oids[] = new OID($extensionKey, $className);
                $classes[$className] = $class;
            }

            $rootOid = $this->manager->getRootOid($extensionKey);
            array_unshift($oids, $rootOid);

            // load ACLs for all object identities
            $acls = $this->findAcls($sid, $oids);

            // find ACL for the root object identity
            $rootAcl = $this->findAclByOid($acls, $rootOid);
            foreach ($oids as $oid) {
                if ($oid->getType() === ObjectIdentityFactory::ROOT_IDENTITY_TYPE) {
                    continue;
                }

                $class = $classes[$oid->getType()];
                $name = $class->getLabel();
                if (!$name) {
                    $name = substr($class->getClassName(), strpos($class->getClassName(), '\\'));
                } elseif ($name instanceof Label) {
                    $name = $name->trans($this->translator);
                }
                $description = $class->getDescription();
                if ($description instanceof Label) {
                    $description = $description->trans($this->translator);
                }

                $privilege = new AclPrivilege();
                $privilege
                    ->setIdentity(new AclPrivilegeIdentity(
                        ObjectIdentityHelper::encodeIdentityString($oid->getIdentifier(), $oid->getType()),
                        $name
                    ))
                    ->setExtensionKey($extensionKey)
                    ->setDescription($description)
                    ->setGroup($class->getGroup())
                    ->setCategory($class->getCategory());

                $this->addPermissions($sid, $privilege, $oid, $acls, $extension, $rootAcl, $aclGroup);

                // add fields in case if class metadata has not empty fields array
                if ($class->getFields()) {
                    $privilege->setFields($this->getFieldsPrivileges($sid, $class, $extension));
                }
                $privileges->add($privilege);
            }
        }

        $this->sortPrivileges($privileges);

        return $privileges;
    }

    /**
     * Gets all privileges associated with the given security identity.
     *
     * @param SID $sid
     * @param null $aclGroup
     * @param bool $checkACLSupport
     * @return ArrayCollection|AclPrivilege[]
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getSupportedAclPrivileges(SID $sid, $aclGroup = null, bool $checkACLSupport = false)
    {
        $privileges = new ArrayCollection();
        foreach ($this->manager->getAllExtensions() as $extension) {
            $extensionKey = $extension->getExtensionKey();
            // fill a list of object identities;
            // the root object identity is added to the top of the list (for performance reasons)
            /** @var ClassSecurityMetadata[] $classes */
            $classes = [];
            /** @var OID[] $oids */
            $oids = [];
            $extensionClasses = $extension->getClasses();
            foreach ($extensionClasses as $class) {
                $className = $class->getClassName();
                $oid = new OID($extensionKey, $className);

                if ($checkACLSupport && !$extension->supports($oid->getType(), $oid->getIdentifier())) {
                    continue;
                }

                $oids[] = $oid;
                $classes[$className] = $class;
            }

            $rootOid = $this->manager->getRootOid($extensionKey);
            array_unshift($oids, $rootOid);

            // load ACLs for all object identities
            $acls = $this->findAcls($sid, $oids);

            // find ACL for the root object identity
            $rootAcl = $this->findAclByOid($acls, $rootOid);
            foreach ($oids as $oid) {
                if ($oid->getType() === ObjectIdentityFactory::ROOT_IDENTITY_TYPE) {
                    continue;
                }

                $class = $classes[$oid->getType()];
                $name = $class->getLabel();
                if (!$name) {
                    $name = substr($class->getClassName(), strpos($class->getClassName(), '\\'));
                } elseif ($name instanceof Label) {
                    $name = $name->trans($this->translator);
                }
                $description = $class->getDescription();
                if ($description instanceof Label) {
                    $description = $description->trans($this->translator);
                }

                $privilege = new AclPrivilege();
                $privilege
                    ->setIdentity(new AclPrivilegeIdentity(
                        ObjectIdentityHelper::encodeIdentityString($oid->getIdentifier(), $oid->getType()),
                        $name
                    ))
                    ->setExtensionKey($extensionKey)
                    ->setDescription($description)
                    ->setGroup($class->getGroup())
                    ->setCategory($class->getCategory());

                $this->addPermissions($sid, $privilege, $oid, $acls, $extension, $rootAcl, $aclGroup);

                // add fields in case if class metadata has not empty fields array
                if ($class->getFields()) {
                    $privilege->setFields($this->getFieldsPrivileges($sid, $class, $extension));
                }
                $privileges->add($privilege);
            }
        }

        $this->sortPrivileges($privileges);

        return $privileges;
    }

    /**
     * Associates privileges with the given security identity.
     *
     * @param SID                            $sid
     * @param ArrayCollection|AclPrivilege[] $privileges
     *
     * @throws \RuntimeException
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function savePrivileges(SID $sid, ArrayCollection $privileges)
    {
        /**
         * @var array $rootKeys [<ExtensionKey> => <a key in $privilege collection>]
         */
        $rootKeys = [];
        // find all root privileges
        foreach ($privileges as $key => $privilege) {
            $identity = $privilege->getIdentity()->getId();
            if (strpos($identity, ObjectIdentityFactory::ROOT_IDENTITY_TYPE)) {
                $extensionKey = ObjectIdentityHelper::getExtensionKeyFromIdentityString($identity);
                $rootKeys[$extensionKey] = $key;
            }
        }

        /**
         * @var array $context
         *
         * key = ExtensionKey
         * value = array
         *      'extension' => extension
         *      'maskBuilders' => array
         *              key = permission name
         *              value = MaskBuilder (the same instance for all permissions supported by the builder)
         *      'rootMasks' => array of integer
         */
        // init the context
        $context = [];
        $this->initSaveContext($context, $rootKeys, $sid, $privileges);

        // set permissions for all root objects and remove all root privileges from $privileges collection
        foreach ($context as $extensionKey => $contextItem) {
            /** @var AclExtensionInterface $extension */
            $extension = $contextItem['extension'];
            if (isset($rootKeys[$extensionKey])) {
                $privilegeKey = $rootKeys[$extensionKey];
                $privilege = $privileges[$privilegeKey];
                unset($privileges[$privilegeKey]);
                $identity = $privilege->getIdentity()->getId();
                $oid = $extension->getObjectIdentity($identity);
            } else {
                $oid = $this->manager->getRootOid($extensionKey);
            }
            $rootMasks = $context[$extensionKey]['rootMasks'];
            foreach ($rootMasks as $mask) {
                $this->manager->setPermission($sid, $oid, $mask);
            }
        }

        // set permissions for other objects
        foreach ($privileges as $privilege) {
            $identity = $privilege->getIdentity()->getId();
            $extensionKey = $this->getExtensionKeyByIdentity($identity);

            /** @var AclExtensionInterface $extension */
            $extension = $context[$extensionKey]['extension'];
            $oid = $extension->getObjectIdentity($identity);
            $maskBuilders = $context[$extensionKey]['maskBuilders'];
            $masks = $this->getPermissionMasks($privilege->getPermissions(), $extension, $maskBuilders);
            $rootMasks = $context[$extensionKey]['rootMasks'];
            foreach ($this->manager->getAces($sid, $oid) as $ace) {
                if (!$ace->isGranting()) {
                    // denying ACE is not supported
                    continue;
                }
                $mask = $this->updateExistingPermissions($sid, $oid, $ace->getMask(), $masks, $rootMasks, $extension);
                // as we have already processed $mask, remove it from $masks collection
                if ($mask !== false) {
                    $this->removeMask($masks, $mask);
                }
            }
            // check if we have new masks so far, and process them if any
            foreach ($masks as $mask) {
                $rootMask = $this->findSimilarMask($rootMasks, $mask, $extension);
                if ($rootMask === false || $mask !== $extension->adaptRootMask($rootMask, $oid)) {
                    $this->manager->setPermission($sid, $oid, $mask);
                }
            }

            if ($privilege->getFields()) {
                $this->saveFieldPrivileges($sid, $oid, $privilege->getFields());
            }
        }

        $this->manager->flush();
    }

    /**
     * @param SID                   $sid
     * @param ClassSecurityMetadata $classMetadata
     * @param AclExtensionInterface $extension
     *
     * @return ArrayCollection
     */
    protected function getFieldsPrivileges(
        SID $sid,
        ClassSecurityMetadata $classMetadata,
        AclExtensionInterface $extension
    ) {
        $privileges = [];
        $className = $classMetadata->getClassName();
        $extensionKey = $extension->getExtensionKey();
        $objectIdentity = new OID($extensionKey, $className);
        $acls = $this->findAcls($sid, [$objectIdentity]);

        foreach ($classMetadata->getFields() as $fieldMetadata) {
            if ($fieldMetadata->isHidden()) {
                continue;
            }

            $name = $fieldMetadata->getLabel();
            if ($name instanceof Label) {
                $name = $name->trans($this->translator);
            }
            $description = $fieldMetadata->getDescription();
            if ($description instanceof Label) {
                $description = $description->trans($this->translator);
            }

            $privilege = new AclPrivilege();
            $privilege
                ->setIdentity(new AclPrivilegeIdentity(
                    ObjectIdentityHelper::encodeIdentityString(
                        $extensionKey,
                        ObjectIdentityHelper::encodeEntityFieldInfo($className, $fieldMetadata->getFieldName())
                    ),
                    $name
                ))
                ->setDescription($description);

            $this->addFieldPermissions(
                $sid,
                $privilege,
                $objectIdentity,
                $acls,
                $extension->getFieldExtension(),
                $fieldMetadata->getFieldName()
            );
            $privileges[] = $privilege;
        }

        // sort entity fields alphabetically by translated labels
        if ($classMetadata instanceof EntitySecurityMetadata) {
            uasort($privileges, function (AclPrivilege $a, AclPrivilege $b) {
                return strcmp($a->getIdentity()->getName(), $b->getIdentity()->getName());
            });
        }

        return new ArrayCollection($privileges);
    }

    protected function saveFieldPrivileges(SID $sid, OID $oid, ArrayCollection $privileges)
    {
        /** @var AclPrivilege $privilege */
        foreach ($privileges as $privilege) {
            $identity = $privilege->getIdentity()->getId();
            $extensionKey = $this->getExtensionKeyByIdentity($identity);
            $extension = $this->manager->getExtensionSelector()
                ->selectByExtensionKey($extensionKey)
                ->getFieldExtension();

            /** @var MaskBuilder[] $maskBuilders */
            $maskBuilders = [];
            $this->prepareMaskBuilders($maskBuilders, $extension);

            // compile masks
            $masks = $this->getPermissionMasks($privilege->getPermissions(), $extension, $maskBuilders);
            $fieldName = ObjectIdentityHelper::decodeEntityFieldInfo($privilege->getIdentity()->getId())[1];

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

        // add default permission for not found in db privileges. By default it should be the maximum access level.
        foreach ($allowedPermissions as $permission) {
            if (!$privilege->hasPermission($permission)) {
                $accessLevels = $extension->getAccessLevelNames($privilege->getIdentity()->getId(), $permission);
                $maxAccessLevel = max(array_keys($accessLevels));
                $privilege->addPermission(new AclPermission($permission, $maxAccessLevel));
            }
        }
    }

    protected function getFieldPermissionMasks($permissions, AclExtensionInterface $extension, array $maskBuilders)
    {
        // check if there are no full field permissions and add missing to calculate correct masks.
        // This case can be if some field does not have all the permissions. In this case we should grant access to the
        // absent permissions.
        $permissionNames = array_keys($maskBuilders);
        foreach ($permissionNames as $permissionName) {
            /** @var ArrayCollection $permissions */
            if (!$permissions->containsKey($permissionName)) {
                $permissions->add(new AclPermission($permissionName, AccessLevel::SYSTEM_LEVEL));
            }
        }

        return $this->getPermissionMasks($permissions, $extension, $maskBuilders);
    }

    /**
     * @param string $identity
     *
     * @return string
     */
    protected function getExtensionKeyByIdentity($identity)
    {
        return substr($identity, 0, strpos($identity, ':'));
    }

    /**
     * Prepares the context is used in savePrivileges method
     *
     * @param array                          $context
     * @param array                          $rootKeys
     * @param SID                            $sid
     * @param ArrayCollection|AclPrivilege[] $privileges
     */
    protected function initSaveContext(array &$context, array $rootKeys, SID $sid, ArrayCollection $privileges)
    {
        foreach ($this->manager->getAllExtensions() as $extension) {
            $extensionKey = $extension->getExtensionKey();
            /** @var MaskBuilder[] $maskBuilders */
            $maskBuilders = [];
            $this->prepareMaskBuilders($maskBuilders, $extension);
            $context[$extensionKey] = [
                'extension'    => $extension,
                'maskBuilders' => $maskBuilders
            ];
            if (isset($rootKeys[$extensionKey])) {
                $privilege = $privileges[$rootKeys[$extensionKey]];
                $rootMasks = $this->getPermissionMasks($privilege->getPermissions(), $extension, $maskBuilders);
            } else {
                $rootMasks = [];
                $oid = $this->manager->getRootOid($extension->getExtensionKey());
                foreach ($this->manager->getAces($sid, $oid) as $ace) {
                    if (!$ace->isGranting()) {
                        // denying ACE is not supported
                        continue;
                    }
                    $rootMasks[] = $ace->getMask();
                }
                // add missing masks
                foreach ($extension->getAllMaskBuilders() as $maskBuilder) {
                    $emptyMask = $maskBuilder->get();
                    $maskAlreadyExist = false;
                    foreach ($rootMasks as $rootMask) {
                        if ($extension->getServiceBits($emptyMask) === $extension->getServiceBits($rootMask)) {
                            $maskAlreadyExist = true;
                            break;
                        }
                    }
                    if (!$maskAlreadyExist) {
                        $rootMasks[] = $emptyMask;
                    }
                }
            }
            $context[$extensionKey]['rootMasks'] = $rootMasks;
        }
    }

    /**
     * Fills an associative array is used to find correct mask builder by the a permission name
     *
     * @param MaskBuilder[]         $maskBuilders [output]
     * @param AclExtensionInterface $extension
     */
    protected function prepareMaskBuilders(array &$maskBuilders, AclExtensionInterface $extension)
    {
        foreach ($extension->getPermissions() as $permission) {
            $maskBuilder = $extension->getMaskBuilder($permission);
            foreach ($maskBuilders as $mb) {
                if ($mb->get() === $maskBuilder->get()) {
                    $maskBuilder = $mb;
                    break;
                }
            }
            $maskBuilders[$permission] = $maskBuilder;
        }
    }

    /**
     * Makes necessary modifications for existing ACE
     *
     * @param SID                   $sid
     * @param OID                   $oid
     * @param int                   $existingMask
     * @param int[]                 $masks [input/output]
     * @param int[]                 $rootMasks
     * @param AclExtensionInterface $extension
     *
     * @return bool|int The mask if it was processed, otherwise, false
     */
    protected function updateExistingPermissions(
        SID $sid,
        OID $oid,
        $existingMask,
        $masks,
        $rootMasks,
        AclExtensionInterface $extension
    ) {
        $mask = $this->findSimilarMask($masks, $existingMask, $extension);
        $rootMask = $this->findSimilarMask($rootMasks, $existingMask, $extension);
        if ($mask === false && $rootMask === false) {
            // keep existing ACE as is, because both $mask and $rootMask were not found
        } elseif ($rootMask === false) {
            // if $rootMask was not found, just update existing ACE using $mask
            $this->manager->setPermission($sid, $oid, $mask);
        } elseif ($mask === false) {
            // if $mask was not found, use $rootMask to check
            // whether existing ACE need to be removed or keep as is
            if ($existingMask === $extension->adaptRootMask($rootMask, $oid)) {
                // remove existing ACE because it provides the same permissions as the root ACE
                $this->manager->deletePermission($sid, $oid, $existingMask);
            }
        } else {
            // both $mask and $rootMask were found
            if ($mask === $extension->adaptRootMask($rootMask, $oid)) {
                // remove existing ACE, if $mask provides the same permissions as $rootMask
                $this->manager->deletePermission($sid, $oid, $existingMask);
            } else {
                // update existing ACE using $mask, if permissions provide by $mask and $rootMask are different
                $this->manager->setPermission($sid, $oid, $mask);
            }
        }

        return $mask;
    }

    /**
     * Finds a mask from $masks array with the same "service bits" as in $needleMask
     *
     * @param int[]                 $masks
     * @param int                   $needleMask
     * @param AclExtensionInterface $extension
     *
     * @return int|bool The found mask, or false if a mask was not found in $masks
     */
    protected function findSimilarMask(array $masks, $needleMask, AclExtensionInterface $extension)
    {
        foreach ($masks as $mask) {
            if ($extension->getServiceBits($needleMask) === $extension->getServiceBits($mask)) {
                return $mask;
            }
        }

        return false;
    }

    /**
     * Removes $needleMask mask from $masks array
     *
     * @param int[] $masks [input/output]
     * @param int   $needleMask
     */
    protected function removeMask(array &$masks, $needleMask)
    {
        $keyToRemove = null;
        foreach ($masks as $key => $mask) {
            if ($mask === $needleMask) {
                $keyToRemove = $key;
                break;
            }
        }
        if ($keyToRemove !== null) {
            unset($masks[$keyToRemove]);
        }
    }

    /**
     * Gets a list of masks from permissions given in $permissions argument
     *
     * @param ArrayCollection|AclPermission[] $permissions
     * @param AclExtensionInterface           $extension
     * @param MaskBuilder[]                   $maskBuilders
     *
     * @return int[]
     */
    protected function getPermissionMasks($permissions, AclExtensionInterface $extension, array $maskBuilders)
    {
        $masks = [];

        foreach ($maskBuilders as $maskBuilder) {
            $maskBuilder->reset();
        }

        foreach ($permissions as $permission) {
            $maskBuilder = $maskBuilders[$permission->getName()];
            $accessLevelName = AccessLevel::getAccessLevelName($permission->getAccessLevel());
            if ($accessLevelName !== null) {
                $permissionName = $permission->getName() . '_' . $accessLevelName;
                // check if a mask builder supports access levels
                if (!$maskBuilder->hasMaskForPermission($permissionName)) {
                    // remove access level name from the mask name if a mask builder do not support access levels
                    $permissionName = $permission->getName();
                }
                $maskBuilder->add($maskBuilder->getMaskForPermission($permissionName));
            }
            $masks[$extension->getServiceBits($maskBuilder->get())] = $maskBuilder->get();
        }

        return array_values($masks);
    }

    /**
     * Gets ACLs for given object identities
     *
     * @param SID   $sid
     * @param OID[] $oids
     *
     * @return \SplObjectStorage
     */
    protected function findAcls(SID $sid, array $oids)
    {
        try {
            return $this->manager->findAcls($sid, $oids);
        } catch (NotAllAclsFoundException $partial) {
            return $partial->getPartialResult();
        }
    }

    /**
     * Sorts the given privileges by name in alphabetical order.
     * The root privilege is moved at the top of the list.
     */
    protected function sortPrivileges(ArrayCollection $privileges)
    {
        $data = [];
        /** @var AclPrivilege $privilege */
        foreach ($privileges->getIterator() as $privilege) {
            $isRoot = str_contains($privilege->getIdentity()->getId(), ObjectIdentityFactory::ROOT_IDENTITY_TYPE);
            $label = !$isRoot
                ? $privilege->getIdentity()->getName()
                : null;

            $data[] = [$privilege, $isRoot, $label];
        }
        uasort(
            $data,
            function ($a, $b) {
                if ($a[1]) {
                    return -1;
                }
                if ($b[1]) {
                    return 1;
                }

                return strcmp($a[2], $b[2]);
            }
        );

        $privileges->clear();
        foreach ($data as $item) {
            $privileges->add($item[0]);
        }
    }

    /**
     * Gets ACL associated with the given object identity from the collections specified in $acls argument.
     *
     * @param \SplObjectStorage $acls
     * @param OID               $oid
     *
     * @return AclInterface|null
     */
    protected function findAclByOid(\SplObjectStorage $acls, ObjectIdentity $oid)
    {
        $result = null;
        foreach ($acls as $aclOid) {
            if ($oid->equals($aclOid)) {
                $result = $acls->offsetGet($aclOid);
                break;
            }
        }

        return $result;
    }

    /**
     * Adds permissions to the given $privilege.
     *
     * @param SID $sid
     * @param AclPrivilege $privilege
     * @param OID $oid
     * @param \SplObjectStorage $acls
     * @param AclExtensionInterface $extension
     * @param AclInterface|null $rootAcl
     * @param string|null $aclGroup
     */
    protected function addPermissions(
        SID $sid,
        AclPrivilege $privilege,
        OID $oid,
        \SplObjectStorage $acls,
        AclExtensionInterface $extension,
        ?AclInterface $rootAcl = null,
        $aclGroup = null
    ) {
        $allowedPermissions = $extension->getAllowedPermissions($oid, null, $aclGroup);
        $acl = $this->findAclByOid($acls, $oid);
        $this->addAclPermissions($sid, null, $privilege, $allowedPermissions, $extension, $rootAcl, $acl);

        // add default permission for not found in db privileges
        foreach ($allowedPermissions as $permission) {
            if (!$privilege->hasPermission($permission)) {
                $privilege->addPermission(new AclPermission($permission, AccessLevel::NONE_LEVEL));
            }
        }
    }

    /**
     * Adds permissions to the given $privilege based on the given ACL.
     * The $permissions argument is used to filter privileges for the given permissions only.
     *
     * @param SID                   $sid
     * @param string|null           $field The name of a field.
     *                                     Set to null to work with class-based and object-based ACEs
     *                                     Set to not null to work with class-field-based and object-field-based ACEs
     * @param AclPrivilege          $privilege
     * @param string[]              $permissions
     * @param AclExtensionInterface $extension
     * @param AclInterface|null $rootAcl
     * @param AclInterface|null $acl
     */
    protected function addAclPermissions(
        SID $sid,
        $field,
        AclPrivilege $privilege,
        array $permissions,
        AclExtensionInterface $extension,
        ?AclInterface $rootAcl = null,
        ?AclInterface $acl = null
    ) {
        if ($acl !== null) {
            // check object ACEs
            $this->addAcesPermissions(
                $privilege,
                $permissions,
                $this->getAces($sid, $acl, AclManager::OBJECT_ACE, $field),
                $extension
            );
            // check class ACEs if object ACEs were not contains all requested privileges
            if ($privilege->getPermissionCount() < count($permissions)) {
                $this->addAcesPermissions(
                    $privilege,
                    $permissions,
                    $this->getAces($sid, $acl, AclManager::CLASS_ACE, $field),
                    $extension
                );
            }
            // check parent ACEs if object and class ACEs were not contains all requested privileges
            if ($rootAcl && $privilege->getPermissionCount() < count($permissions) && $acl->isEntriesInheriting()) {
                $parentAcl = $acl->getParentAcl();
                if ($parentAcl !== null) {
                    $this->addAclPermissions($sid, $field, $privilege, $permissions, $extension, $rootAcl, $parentAcl);
                }
            }
        }
        // if so far not all requested privileges are found get them from the root ACL
        if ($rootAcl && $privilege->getPermissionCount() < count($permissions)) {
            $this->addAcesPermissions(
                $privilege,
                $permissions,
                $this->getAces($sid, $rootAcl, AclManager::OBJECT_ACE, $field),
                $extension,
                true
            );
        }
    }

    /**
     * Gets all ACEs associated with given ACL and the given security identity
     *
     * @param SID          $sid
     * @param AclInterface $acl
     * @param string       $type  The ACE type. Can be one of AclManager::*_ACE constants
     * @param string|null  $field The name of a field.
     *                            Set to null for class-based or object-based ACE
     *                            Set to not null class-field-based or object-field-based ACE
     *
     * @return EntryInterface[]
     */
    protected function getAces(SID $sid, AclInterface $acl, $type, $field = null)
    {
        return array_filter(
            $this->manager->getAceProvider()->getAces($acl, $type, $field),
            function ($ace) use (&$sid) {
                /** @var EntryInterface $ace */

                return $sid->equals($ace->getSecurityIdentity());
            }
        );
    }

    /**
     * Adds permissions to the given $privilege based on the given ACEs.
     * The $permissions argument is used to filter privileges for the given permissions only.
     *
     * @param AclPrivilege          $privilege
     * @param string[]              $permissions
     * @param EntryInterface[]      $aces
     * @param AclExtensionInterface $extension
     * @param bool                  $itIsRootAcl
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function addAcesPermissions(
        AclPrivilege $privilege,
        array $permissions,
        array $aces,
        AclExtensionInterface $extension,
        $itIsRootAcl = false
    ) {
        if (empty($aces)) {
            return;
        }
        foreach ($aces as $ace) {
            if (!$ace->isGranting()) {
                // denying ACE is not supported
                continue;
            }
            $mask = $ace->getMask();
            if ($itIsRootAcl) {
                $mask = $extension->adaptRootMask($mask, $privilege->getIdentity()->getId());
            }
            if ($extension->removeServiceBits($mask) === 0) {
                $supportedPermissions = array_intersect($permissions, $extension->getPermissions($mask));

                foreach ($supportedPermissions as $permission) {
                    if (!$privilege->hasPermission($permission)) {
                        $privilege->addPermission(new AclPermission($permission, AccessLevel::NONE_LEVEL));
                    }
                }
            } else {
                foreach ($extension->getPermissions($mask) as $permission) {
                    if (!$privilege->hasPermission($permission) && in_array($permission, $permissions, true)) {
                        $privilege->addPermission($this->getAclPermission($extension, $permission, $mask, $privilege));
                    }
                }
            }
        }
    }

    /**
     * Return AclPermission object for given permission, ACL mask and ACL privilege
     *
     * @param AclExtensionInterface $extension
     * @param string                $permission
     * @param string                $mask
     * @param AclPrivilege          $privilege
     *
     * @return AclPermission
     */
    protected function getAclPermission(AclExtensionInterface $extension, $permission, $mask, AclPrivilege $privilege)
    {
        return new AclPermission(
            $permission,
            $extension->getAccessLevel(
                $mask,
                $permission,
                $privilege->getIdentity()->getId()
            )
        );
    }
}
