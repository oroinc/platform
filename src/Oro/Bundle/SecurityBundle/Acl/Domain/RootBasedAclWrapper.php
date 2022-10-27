<?php

namespace Oro\Bundle\SecurityBundle\Acl\Domain;

use Symfony\Component\Security\Acl\Domain\Acl;
use Symfony\Component\Security\Acl\Model\AclInterface;
use Symfony\Component\Security\Acl\Model\EntryInterface;
use Symfony\Component\Security\Acl\Model\PermissionGrantingStrategyInterface;

/**
 * An access control list (ACL) implementation that combines ACEs from a main ACL and a root ACL.
 */
class RootBasedAclWrapper implements AclInterface
{
    /** @var Acl */
    private $acl;

    /** @var RootAclWrapper */
    private $rootAcl;

    /** @var PermissionGrantingStrategyInterface */
    private $permissionGrantingStrategy;

    /** @var array|null */
    private $classAces;

    /** @var array */
    private $classFieldAces = [];

    public function __construct(Acl $acl, RootAclWrapper $rootAcl)
    {
        $this->acl = $acl;
        $this->rootAcl = $rootAcl;
    }

    /**
     * {@inheritdoc}
     */
    public function getClassAces()
    {
        if (null !== $this->classAces) {
            return $this->classAces;
        }

        $aces = $this->addRootAces($this->acl->getClassAces());
        $this->classAces = $aces;

        return $aces;
    }

    /**
     * {@inheritdoc}
     */
    public function getClassFieldAces($field)
    {
        if (isset($this->classFieldAces[$field])) {
            return $this->classFieldAces[$field];
        }

        $aces = $this->addRootAces($this->acl->getClassFieldAces($field), $field);
        $this->classFieldAces[$field] = $aces;

        return $aces;
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectAces()
    {
        return $this->acl->getObjectAces();
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectFieldAces($field)
    {
        return $this->acl->getObjectFieldAces($field);
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectIdentity()
    {
        if (!$this->acl->getClassAces() && !$this->acl->getObjectAces()) {
            return $this->rootAcl->getObjectIdentity();
        }

        return $this->acl->getObjectIdentity();
    }

    /**
     * {@inheritdoc}
     */
    public function getParentAcl()
    {
        return $this->acl->getParentAcl();
    }

    /**
     * {@inheritdoc}
     */
    public function isEntriesInheriting()
    {
        return $this->acl->isEntriesInheriting();
    }

    /**
     * {@inheritdoc}
     */
    public function isFieldGranted($field, array $masks, array $securityIdentities, $administrativeMode = false)
    {
        return $this->getPermissionGrantingStrategy()
            ->isFieldGranted($this, $field, $masks, $securityIdentities, $administrativeMode);
    }

    /**
     * {@inheritdoc}
     */
    public function isGranted(array $masks, array $securityIdentities, $administrativeMode = false)
    {
        return $this->getPermissionGrantingStrategy()
            ->isGranted($this, $masks, $securityIdentities, $administrativeMode);
    }

    /**
     * {@inheritdoc}
     */
    public function isSidLoaded($securityIdentities)
    {
        return $this->acl->isSidLoaded($securityIdentities);
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        throw new \LogicException('Not supported.');
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        throw new \LogicException('Not supported.');
    }

    public function __serialize(): array
    {
        throw new \LogicException('Not supported.');
    }

    public function __unserialize(array $serialized): void
    {
        throw new \LogicException('Not supported.');
    }

    /**
     * @return PermissionGrantingStrategy
     */
    private function getPermissionGrantingStrategy()
    {
        if ($this->permissionGrantingStrategy === null) {
            // Unfortunately permissionGrantingStrategy property is private, so the only way
            // to get it is to use the reflection
            $r = new \ReflectionClass(\get_class($this->acl));
            $p = $r->getProperty('permissionGrantingStrategy');
            $p->setAccessible(true);
            $this->permissionGrantingStrategy = $p->getValue($this->acl);
        }

        return $this->permissionGrantingStrategy;
    }

    /**
     * @param EntryInterface[] $aces
     * @param string|null      $field
     *
     * @return EntryInterface[]
     */
    private function addRootAces(array $aces, $field = null)
    {
        return $this->rootAcl->addRootAces(
            $this->getPermissionGrantingStrategy()->getContext()->getAclExtension(),
            $aces,
            $field
        );
    }
}
