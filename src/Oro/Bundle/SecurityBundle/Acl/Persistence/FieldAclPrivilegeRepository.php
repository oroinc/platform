<?php

namespace Oro\Bundle\SecurityBundle\Acl\Persistence;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface as SID;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity as OID;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\SecurityBundle\Acl\Permission\MaskBuilder;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionInterface;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\SecurityBundle\Model\AclPrivilegeIdentity;
use Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadata;
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
     * @param string $className
     *
     * @return array
     */
    protected function getFieldList($className)
    {
        $fieldList = array_map(
            function ($fieldArray) {
                return $fieldArray['name'];
            },
            $this->fieldProvider->getFields($className, true)
        );

        return $fieldList;
    }

    /**
     * @param SID    $sid
     * @param string $className
     *
     * @return ArrayCollection|AclPrivilege[]
     */
    public function getFieldsPrivileges(SID $sid, $className)
    {
        $extensionKey = 'field';
        $extension = $this->manager->getExtensionSelector()->select(
            $extensionKey . ':' . $className
        );
        $entityClass = $this->getClassMetadata($className, $extension);

        $oids = [];
        $oids[] = $entityRootOid = $this->manager->getRootOid('entity');
        $oids[] = $fieldRootOid = new OID('entity', $className);

        $objectIdentity = new OID($extensionKey, $className);
        $oids[] = $objectIdentity;
        $acls = $this->findAcls($sid, $oids);

        // find ACL for the root object identity
        // root identify for field level ACL is corresponding class level entity ACL, or root entity OID
        $rootAcl = $this->findAclByOid($acls, $fieldRootOid);

        // check if there are any aces to fallback to
        $rootAces = $rootAcl ? $this->getFirstNotEmptyAce(
            $sid,
            $rootAcl,
            [
                [AclManager::OBJECT_ACE, null],
                [AclManager::CLASS_ACE, null],
            ]
        ) : [];

        // if no - use root entity identity (that is always exists)
        if (empty($rootAces)) {
            $rootAcl = $this->findAclByOid($acls, $entityRootOid);
        }

        $privileges = new ArrayCollection();
        $fields = $this->getFieldList($className);
        foreach ($fields as $fieldName) {
            $privilege = new AclPrivilege();
            $privilege->setIdentity(
                new AclPrivilegeIdentity(
                    sprintf('%s+%s:%s', $objectIdentity->getIdentifier(), $fieldName, $objectIdentity->getType()),
                    $fieldName
                )
            )
                ->setGroup($entityClass->getGroup())
                ->setExtensionKey($extensionKey);

            $this->addPermissions($sid, $privilege, $objectIdentity, $acls, $extension, $rootAcl, $fieldName);
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

            foreach ($this->manager->getAces($sid, $oid, $fieldName) as $ace) {
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
}
