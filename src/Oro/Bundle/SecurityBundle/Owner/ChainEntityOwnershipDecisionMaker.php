<?php

namespace Oro\Bundle\SecurityBundle\Owner;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\SecurityBundle\Acl\Extension\AccessLevelOwnershipDecisionMakerInterface;
use Oro\Bundle\SecurityBundle\Exception\NotFoundSupportedOwnershipDecisionMakerException;

/**
 * Makes decisions on ownership of domain objects based on decision makers registered in chain.
 */
class ChainEntityOwnershipDecisionMaker implements AccessLevelOwnershipDecisionMakerInterface
{
    /**
     * @var ArrayCollection|AccessLevelOwnershipDecisionMakerInterface[]
     */
    protected $ownershipDecisionMakers;

    /**
     * @param AccessLevelOwnershipDecisionMakerInterface[] $ownershipDecisionMakers
     */
    public function __construct(array $ownershipDecisionMakers = [])
    {
        $this->ownershipDecisionMakers = new ArrayCollection($ownershipDecisionMakers);
    }

    /**
     * Adds all decision makers marked by tag: oro_security.owner.ownership_decision_maker
     */
    public function addOwnershipDecisionMaker(AccessLevelOwnershipDecisionMakerInterface $ownershipDecisionMakers)
    {
        if (!$this->ownershipDecisionMakers->contains($ownershipDecisionMakers)) {
            $this->ownershipDecisionMakers->add($ownershipDecisionMakers);
        }
    }

    #[\Override]
    public function supports()
    {
        foreach ($this->ownershipDecisionMakers as $ownershipDecisionMaker) {
            if ($ownershipDecisionMaker->supports()) {
                return true;
            }
        }

        return false;
    }

    #[\Override]
    public function isOrganization($domainObject)
    {
        return $this->getSupportedOwnershipDecisionMaker()->isOrganization($domainObject);
    }

    #[\Override]
    public function isBusinessUnit($domainObject)
    {
        return $this->getSupportedOwnershipDecisionMaker()->isBusinessUnit($domainObject);
    }

    #[\Override]
    public function isUser($domainObject)
    {
        return $this->getSupportedOwnershipDecisionMaker()->isUser($domainObject);
    }

    #[\Override]
    public function isAssociatedWithOrganization($user, $domainObject, $organization = null)
    {
        return $this->getSupportedOwnershipDecisionMaker()->isAssociatedWithOrganization(
            $user,
            $domainObject,
            $organization
        );
    }

    #[\Override]
    public function isAssociatedWithBusinessUnit($user, $domainObject, $deep = false, $organization = null)
    {
        return $this->getSupportedOwnershipDecisionMaker()->isAssociatedWithBusinessUnit(
            $user,
            $domainObject,
            $deep,
            $organization
        );
    }

    #[\Override]
    public function isAssociatedWithUser($user, $domainObject, $organization = null)
    {
        return $this->getSupportedOwnershipDecisionMaker()->isAssociatedWithUser(
            $user,
            $domainObject,
            $organization
        );
    }

    /**
     * @return AccessLevelOwnershipDecisionMakerInterface
     *
     * @throws NotFoundSupportedOwnershipDecisionMakerException
     */
    protected function getSupportedOwnershipDecisionMaker()
    {
        foreach ($this->ownershipDecisionMakers as $ownershipDecisionMaker) {
            if ($ownershipDecisionMaker->supports()) {
                return $ownershipDecisionMaker;
            }
        }

        throw new NotFoundSupportedOwnershipDecisionMakerException(
            'Not found supported ownership decision maker in chain'
        );
    }
}
