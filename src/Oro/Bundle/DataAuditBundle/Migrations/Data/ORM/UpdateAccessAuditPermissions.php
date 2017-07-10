<?php

namespace Oro\Bundle\DataAuditBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\SecurityBundle\Acl\Domain\PermissionGrantingStrategy;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionInterface;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\UserBundle\Entity\Role;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Exception\NotAllAclsFoundException;
use Symfony\Component\Security\Acl\Model\AclInterface;
use Symfony\Component\Security\Acl\Model\EntryInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

/**
 * Updates all roles which have old "oro_dataaudit_history" action ACL and sets new entity ACL with
 * "VIEW" permission and level "GLOBAL" (@see \Oro\Bundle\SecurityBundle\Acl\AccessLevel::GLOBAL_LEVEL).
 */
class UpdateAccessAuditPermissions extends AbstractFixture implements ContainerAwareInterface
{
    /**
     * @internal
     */
    const ACL_ACTION_AUDIT = 'action:oro_dataaudit_history';

    /**
     * @internal
     */
    const ACL_ENTITY_AUDIT = 'entity:Oro\Bundle\DataAuditBundle\Entity\AbstractAudit';

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        if (!$this->container->hasParameter('installed') || !$this->container->getParameter('installed')) {
            return;
        }

        $aclManager = $this->getAclManager();
        if (!$aclManager->isAclEnabled()) {
            return;
        }

        $roles = $this->loadRoles();
        $oldObjectIdentity = $this->createObjectIdentity(self::ACL_ACTION_AUDIT);
        $newObjectIdentity = $aclManager->getOid(self::ACL_ENTITY_AUDIT);

        foreach ($roles as $role) {
            $securityIdentity = $aclManager->getSid($role);

            if ($this->isGranted($securityIdentity, $oldObjectIdentity, 'EXECUTE')) {
                // Set permission "VIEW", level "GLOBAL".
                $permission = 'VIEW_GLOBAL';
            } else {
                // Otherwise - deny this permission.
                $permission = 'VIEW_NONE';
            }

            $this->setPermission($securityIdentity, $newObjectIdentity, $permission);
        }

        $this->deleteAclForObjectIdentity($oldObjectIdentity);

        $aclManager->flush();
    }

    /**
     * @return Role[]
     */
    private function loadRoles()
    {
        return $this->container
            ->get('oro_entity.doctrine_helper')
            ->getEntityRepository('OroUserBundle:Role')
            ->findAll();
    }

    /**
     * @param SecurityIdentityInterface $securityIdentity
     * @param ObjectIdentityInterface   $objectIdentity
     * @param string                    $permission
     */
    private function setPermission(
        SecurityIdentityInterface $securityIdentity,
        ObjectIdentityInterface $objectIdentity,
        $permission
    ) {
        $aclManager = $this->getAclManager();
        $extension = $aclManager->getExtensionSelector()->select($objectIdentity);
        $maskBuilders = $extension->getAllMaskBuilders();

        foreach ($maskBuilders as $maskBuilder) {
            if ($maskBuilder->hasMask('MASK_' . $permission)) {
                $maskBuilder->add($permission);

                $aclManager->setPermission($securityIdentity, $objectIdentity, $maskBuilder->get());
            }
        }
    }

    /**
     * @param SecurityIdentityInterface $securityIdentity
     * @param ObjectIdentityInterface   $objectIdentity
     * @param string                    $permission
     *
     * @return bool
     */
    private function isGranted(
        SecurityIdentityInterface $securityIdentity,
        ObjectIdentityInterface $objectIdentity,
        $permission
    ) {
        // We cannot use select() method to get needed ACL extension, as given Object Identity might not
        // exist (if ACL is not present in annotations or acls.yml), so it might not be in the list of
        // supported Object Identities of ACL extensions.
        $extension = $this
            ->getAclManager()
            ->getExtensionSelector()
            ->selectByExtensionKey($objectIdentity->getIdentifier());
        $requiredMask = $this->getMask($permission, $extension);

        /** @var EntryInterface[] $accessControlEntries */
        $accessControlEntries = $this->getAccessControlEntries($securityIdentity, $objectIdentity, $extension);
        foreach ($accessControlEntries as $accessControlEntry) {
            if ($accessControlEntry->getAcl()->getObjectIdentity()->getIdentifier() !== $extension->getExtensionKey()) {
                continue;
            }

            $accessControlEntryMask = $accessControlEntry->getMask();
            if ($extension->getServiceBits($requiredMask) !== $extension->getServiceBits($accessControlEntryMask)) {
                continue;
            }

            if ($this->isAccessControlEntryApplicable($requiredMask, $accessControlEntry, $extension)
                && $accessControlEntry->isGranting()
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return AclManager
     */
    private function getAclManager()
    {
        return $this->container->get('oro_security.acl.manager');
    }

    /**
     * Get bitmask for given permission.
     *
     * @param int|string            $permission
     * @param AclExtensionInterface $extension
     *
     * @return int
     */
    private function getMask($permission, AclExtensionInterface $extension)
    {
        $maskBuilder = $extension->getMaskBuilder($permission);
        $maskBuilder->add($permission);

        return $maskBuilder->get();
    }

    /**
     * Determines whether the AccessControlEntry is applicable to the given permission/security identity combination.
     *
     * @param int                   $requiredMask
     * @param EntryInterface        $accessControlEntry
     * @param AclExtensionInterface $extension
     *
     * @return bool
     *
     * @throws \RuntimeException
     */
    private function isAccessControlEntryApplicable(
        $requiredMask,
        EntryInterface $accessControlEntry,
        AclExtensionInterface $extension
    ) {
        $requiredMask = $extension->removeServiceBits($requiredMask);
        $accessControlEntryMask = $extension->removeServiceBits($accessControlEntry->getMask());
        $strategy = $accessControlEntry->getStrategy();

        switch ($strategy) {
            case PermissionGrantingStrategy::ALL:
                return $requiredMask === ($accessControlEntryMask & $requiredMask);
            case PermissionGrantingStrategy::ANY:
                return 0 !== ($accessControlEntryMask & $requiredMask);
            case PermissionGrantingStrategy::EQUAL:
                return $requiredMask === $accessControlEntryMask;
            default:
                throw new \RuntimeException(sprintf('The strategy "%s" is not supported.', $strategy));
        }
    }

    /**
     * @param SecurityIdentityInterface $securityIdentity
     * @param ObjectIdentityInterface   $objectIdentity
     * @param AclExtensionInterface     $extension
     *
     * @return EntryInterface[]
     */
    private function getAccessControlEntries(
        SecurityIdentityInterface $securityIdentity,
        ObjectIdentityInterface $objectIdentity,
        AclExtensionInterface $extension
    ) {
        $acl = $this->getAcl($securityIdentity, $objectIdentity);
        if ($acl === null) {
            return [];
        }

        $accessControlEntryType = AclManager::OBJECT_ACE;
        if ($objectIdentity->getIdentifier() === $extension->getExtensionKey()) {
            $accessControlEntryType = AclManager::CLASS_ACE;
        }

        return array_filter(
            $this->getAclManager()->getAceProvider()->getAces($acl, $accessControlEntryType, null),
            function ($accessControlEntry) use (&$securityIdentity) {
                /** @var EntryInterface $accessControlEntry */
                return $securityIdentity->equals($accessControlEntry->getSecurityIdentity());
            }
        );
    }

    /**
     * @param SecurityIdentityInterface $securityIdentity
     * @param ObjectIdentityInterface   $objectIdentity
     *
     * @return AclInterface
     */
    private function getAcl(SecurityIdentityInterface $securityIdentity, ObjectIdentityInterface $objectIdentity)
    {
        try {
            /** @var AclInterface $acl */
            return $this->getAclManager()->findAcls($securityIdentity, [$objectIdentity])->offsetGet($objectIdentity);
        } catch (NotAllAclsFoundException $exception) {
            // That's normal behavior if ACL was not found - it just means that such ACL is not present in DB,
            // because it was not used before.
            return null;
        }
    }

    /**
     * Creates object identity from given descriptor.
     *
     * We cannot use AclManager::getOid() and have to create it manually, because it fails
     * if given ACL is not declared in annotations or in acls.yml, even if the desired object
     * identity exists in DB.
     *
     * @param string $objectIdentityDescriptor
     *
     * @return ObjectIdentity
     */
    private function createObjectIdentity($objectIdentityDescriptor)
    {
        list($objectIdentifier, $type) = explode(':', $objectIdentityDescriptor);

        return new ObjectIdentity($objectIdentifier, $type);
    }

    /**
     * Deletes Access Control Entry (ACE), child ACEs, Object Identity, Object Identity Relations.
     *
     * In fact, it does not delete ACEs of "class" type, because of ACL manager implementation specialties, so
     * only Object Identities are deleted.
     *
     * @param ObjectIdentityInterface $objectIdentity
     */
    private function deleteAclForObjectIdentity(ObjectIdentityInterface $objectIdentity)
    {
        $this->getAclManager()->deleteAcl($objectIdentity);
    }
}
