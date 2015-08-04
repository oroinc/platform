<?php

namespace Oro\Bundle\SecurityBundle\Owner;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Security\Acl\Model\EntryInterface;

use Oro\Bundle\SecurityBundle\Acl\Extension\AccessLevelOwnershipDecisionMakerInterface;
use Oro\Bundle\SecurityBundle\Exception\NotFoundSupportedOwnershipDecisionMakerException;
use Oro\Bundle\SecurityBundle\Model\AceAwareModelInterface;

class ChainEntityOwnershipDecisionMaker implements AccessLevelOwnershipDecisionMakerInterface, AceAwareModelInterface
{
    /**
     * @var EntryInterface
     */
    protected $ace;

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
     * {@inheritDoc}
     */
    public function isShared()
    {
        return $this->getSupportedOwnershipDecisionMaker()->isShared();
    }

    /**
     * {@inheritDoc}
     */
    public function resetShared()
    {
        $this->getSupportedOwnershipDecisionMaker()->resetShared();
    }

    /**
     * {@inheritdoc}
     */
    public function setAce(EntryInterface $ace)
    {
        $this->ace = $ace;

        $decisionMaker = $this->getSupportedOwnershipDecisionMaker();
        if ($decisionMaker instanceof AceAwareModelInterface) {
            $decisionMaker->setAce($ace);
        }
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
