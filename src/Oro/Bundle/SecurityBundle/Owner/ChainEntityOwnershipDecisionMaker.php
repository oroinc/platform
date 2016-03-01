<?php

namespace Oro\Bundle\SecurityBundle\Owner;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\SecurityBundle\Acl\Extension\AccessLevelOwnershipDecisionMakerInterface;
use Oro\Bundle\SecurityBundle\Exception\NotFoundSupportedOwnershipDecisionMakerException;

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
    public function isGlobalLevelEntity($domainObject)
    {
        return $this->getSupportedOwnershipDecisionMaker()->isGlobalLevelEntity($domainObject);
    }

    /**
     * {@inheritDoc}
     */
    public function isLocalLevelEntity($domainObject)
    {
        return $this->getSupportedOwnershipDecisionMaker()->isLocalLevelEntity($domainObject);
    }

    /**
     * {@inheritDoc}
     */
    public function isBasicLevelEntity($domainObject)
    {
        return $this->getSupportedOwnershipDecisionMaker()->isBasicLevelEntity($domainObject);
    }

    /**
     * {@inheritDoc}
     */
    public function isAssociatedWithGlobalLevelEntity($user, $domainObject, $organization = null)
    {
        return $this->getSupportedOwnershipDecisionMaker()->isAssociatedWithGlobalLevelEntity(
            $user,
            $domainObject,
            $organization
        );
    }

    /**
     * {@inheritDoc}
     */
    public function isAssociatedWithLocalLevelEntity($user, $domainObject, $deep = false, $organization = null)
    {
        return $this->getSupportedOwnershipDecisionMaker()->isAssociatedWithLocalLevelEntity(
            $user,
            $domainObject,
            $deep,
            $organization
        );
    }

    /**
     * {@inheritDoc}
     */
    public function isAssociatedWithBasicLevelEntity($user, $domainObject, $organization = null)
    {
        return $this->getSupportedOwnershipDecisionMaker()->isAssociatedWithBasicLevelEntity(
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
