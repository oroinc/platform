<?php

namespace Oro\Bundle\SecurityBundle\Acl\Domain;

use Symfony\Component\Security\Acl\Domain\Acl;
use Symfony\Component\Security\Acl\Model\AclInterface;
use Symfony\Component\Security\Acl\Model\EntryInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Acl\Model\PermissionGrantingStrategyInterface;

/**
 * An access control list (ACL) implementation that combines ACEs from a main ACL and a root ACL.
 */
class RootBasedAclWrapper implements AclInterface
{
    private Acl $acl;
    private RootAclWrapper $rootAcl;
    private ?PermissionGrantingStrategyInterface $permissionGrantingStrategy = null;
    private ?array $classAces = null;
    private array $classFieldAces = [];

    public function __construct(Acl $acl, RootAclWrapper $rootAcl)
    {
        $this->acl = $acl;
        $this->rootAcl = $rootAcl;
    }

    #[\Override]
    public function getClassAces(): array
    {
        if (null !== $this->classAces) {
            return $this->classAces;
        }

        $aces = $this->addRootAces($this->acl->getClassAces());
        $this->classAces = $aces;

        return $aces;
    }

    #[\Override]
    public function getClassFieldAces($field): array
    {
        if (isset($this->classFieldAces[$field])) {
            return $this->classFieldAces[$field];
        }

        $aces = $this->addRootAces($this->acl->getClassFieldAces($field), $field);
        $this->classFieldAces[$field] = $aces;

        return $aces;
    }

    #[\Override]
    public function getObjectAces(): array
    {
        return $this->acl->getObjectAces();
    }

    #[\Override]
    public function getObjectFieldAces($field): array
    {
        return $this->acl->getObjectFieldAces($field);
    }

    #[\Override]
    public function getObjectIdentity(): ObjectIdentityInterface
    {
        if (!$this->acl->getClassAces() && !$this->acl->getObjectAces()) {
            return $this->rootAcl->getObjectIdentity();
        }

        return $this->acl->getObjectIdentity();
    }

    public function getFieldObjectIdentity(string $fieldName): ObjectIdentityInterface
    {
        if ($this->acl->getClassFieldAces($fieldName) || $this->acl->getObjectFieldAces($fieldName)) {
            return $this->acl->getObjectIdentity();
        }

        return $this->getObjectIdentity();
    }

    #[\Override]
    public function getParentAcl(): ?AclInterface
    {
        return $this->acl->getParentAcl();
    }

    #[\Override]
    public function isEntriesInheriting(): bool
    {
        return $this->acl->isEntriesInheriting();
    }

    #[\Override]
    public function isFieldGranted($field, array $masks, array $securityIdentities, $administrativeMode = false): bool
    {
        return $this->getPermissionGrantingStrategy()
            ->isFieldGranted($this, $field, $masks, $securityIdentities, $administrativeMode);
    }

    #[\Override]
    public function isGranted(array $masks, array $securityIdentities, $administrativeMode = false): bool
    {
        return $this->getPermissionGrantingStrategy()
            ->isGranted($this, $masks, $securityIdentities, $administrativeMode);
    }

    #[\Override]
    public function isSidLoaded($securityIdentities): bool
    {
        return $this->acl->isSidLoaded($securityIdentities);
    }

    #[\Override]
    public function serialize()
    {
        throw new \LogicException('Not supported.');
    }

    #[\Override]
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
    private function addRootAces(array $aces, ?string $field = null): array
    {
        return $this->rootAcl->addRootAces(
            $this->getPermissionGrantingStrategy()->getContext()->getAclExtension(),
            $aces,
            $field
        );
    }
}
