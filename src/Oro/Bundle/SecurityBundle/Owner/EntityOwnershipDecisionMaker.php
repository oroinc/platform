<?php

namespace Oro\Bundle\SecurityBundle\Owner;

use Oro\Bundle\SecurityBundle\Acl\Extension\OwnershipDecisionMakerInterface;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;

class EntityOwnershipDecisionMaker extends AbstractEntityOwnershipDecisionMaker implements
    OwnershipDecisionMakerInterface
{
    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @param SecurityFacade $securityFacade
     *
     * @return $this
     */
    public function setSecurityFacade(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
        return $this;
    }

    /**
     * {@inheritdoc}
     * @deprecated since 1.8 Please use isGlobalLevelEntity() instead
     */
    public function isOrganization($domainObject)
    {
        return $this->isGlobalLevelEntity($domainObject);
    }

    /**
     * {@inheritdoc}
     * @deprecated since 1.8 Please use isLocalLevelEntity() instead
     */
    public function isBusinessUnit($domainObject)
    {
        return $this->isLocalLevelEntity($domainObject);
    }

    /**
     * {@inheritdoc}
     * @deprecated since 1.8 Please use isBasicLevelEntity() instead
     */
    public function isUser($domainObject)
    {
        return $this->isBasicLevelEntity($domainObject);
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @deprecated since 1.8 Please use isAssociatedWithGlobalLevelEntity() instead
     */
    public function isAssociatedWithOrganization($user, $domainObject, $organization = null)
    {
        return $this->isAssociatedWithGlobalLevelEntity($user, $domainObject, $organization);
    }

    /**
     * {@inheritdoc}
     * @deprecated since 1.8 Please use isAssociatedWithDeepLevelEntity() instead
     */
    public function isAssociatedWithBusinessUnit($user, $domainObject, $deep = false, $organization = null)
    {
        return $this->isAssociatedWithLocalLevelEntity($user, $domainObject, $deep, $organization);
    }

    /**
     * {@inheritdoc}
     * @deprecated since 1.8 Please use isAssociatedWithBasicLevelEntity() instead
     */
    public function isAssociatedWithUser($user, $domainObject, $organization = null)
    {
        return $this->isAssociatedWithBasicLevelEntity($user, $domainObject, $organization);
    }

    /**
     * {@inheritdoc}
     */
    public function supports()
    {
        return $this->securityFacade && $this->securityFacade->getLoggedUser() instanceof User;
    }
}
