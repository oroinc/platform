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

    /**
     * {@inheritDoc}
     */
    public function supports()
    {
        foreach ($this->ownershipDecisionMakers as $ownershipDecisionMaker) {
            if ($ownershipDecisionMaker->supports()) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function isOrganization($domainObject)
    {
        return $this->getSupportedOwnershipDecisionMaker()->isOrganization($domainObject);
    }

    /**
     * {@inheritDoc}
     */
    public function isBusinessUnit($domainObject)
    {
        return $this->getSupportedOwnershipDecisionMaker()->isBusinessUnit($domainObject);
    }

    /**
     * {@inheritDoc}
     */
    public function isUser($domainObject)
    {
        return $this->getSupportedOwnershipDecisionMaker()->isUser($domainObject);
    }

    /**
     * {@inheritDoc}
     */
    public function isAssociatedWithOrganization($user, $domainObject, $organization = null)
    {
        return $this->getSupportedOwnershipDecisionMaker()->isAssociatedWithOrganization(
            $user,
            $domainObject,
            $organization
        );
    }

    /**
     * {@inheritDoc}
     */
    public function isAssociatedWithBusinessUnit($user, $domainObject, $deep = false, $organization = null)
    {
        return $this->getSupportedOwnershipDecisionMaker()->isAssociatedWithBusinessUnit(
            $user,
            $domainObject,
            $deep,
            $organization
        );
    }

    /**
     * {@inheritDoc}
     */
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
