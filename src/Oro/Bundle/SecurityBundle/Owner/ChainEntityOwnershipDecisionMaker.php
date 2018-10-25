<?php

namespace Oro\Bundle\SecurityBundle\Owner;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\SecurityBundle\Acl\Extension\AccessLevelOwnershipDecisionMakerInterface;
use Oro\Bundle\SecurityBundle\Exception\NotFoundSupportedOwnershipDecisionMakerException;

/**
 * Chain on entity ownership decision makers
 */
class ChainEntityOwnershipDecisionMaker implements AccessLevelOwnershipDecisionMakerInterface
{
    /**
     * @var ArrayCollection|AccessLevelOwnershipDecisionMakerInterface[]
     */
    protected $ownershipDecisionMakers;

    /**
     * @var AccessLevelOwnershipDecisionMakerInterface
     */
    protected $ownershipDecisionMaker;
    
    /**
     * @param AccessLevelOwnershipDecisionMakerInterface[] $ownershipDecisionMakers
     */
    public function __construct(array $ownershipDecisionMakers = [])
    {
        $this->ownershipDecisionMakers = new ArrayCollection($ownershipDecisionMakers);
    }
    
    /**
     * Adds all decision makers marked by tag: oro_security.owner.ownership_decision_maker
     *
     * @param AccessLevelOwnershipDecisionMakerInterface $ownershipDecisionMakers
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
        if ($this->ownershipDecisionMaker) {
            return $this->ownershipDecisionMaker;
        }

        foreach ($this->ownershipDecisionMakers as $ownershipDecisionMaker) {
            if ($ownershipDecisionMaker->supports()) {
                $this->ownershipDecisionMaker = $ownershipDecisionMaker;

                return $this->ownershipDecisionMaker;
            }
        }

        throw new NotFoundSupportedOwnershipDecisionMakerException(
            'Not found supported ownership decision maker in chain'
        );
    }
}
