<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage;

class EmailOwnersProvider
{
    /** @var EmailOwnerProviderStorage */
    protected $emailOwnerStorage;

    /** @var ActivityListChainProvider */
    protected $activityListChainProvider;

    /** @var Registry */
    protected $registry;

    /**
     * @param ActivityListChainProvider $activityListChainProvider
     * @param EmailOwnerProviderStorage $emailOwnerStorage
     * @param Registry $registry
     */
    public function __construct(
        ActivityListChainProvider $activityListChainProvider,
        EmailOwnerProviderStorage $emailOwnerStorage,
        Registry $registry
    ) {
        $this->activityListChainProvider = $activityListChainProvider;
        $this->emailOwnerStorage = $emailOwnerStorage;
        $this->registry = $registry;
    }

    /**
     * Get email entities from owner entity
     *
     * @param object $entity
     * @return array
     */
    public function getEmailsByOwnerEntity($entity)
    {
        $ownerColumnName = null;
        $entityClass     = ClassUtils::getClass($entity);
        foreach ($this->emailOwnerStorage->getProviders() as $provider) {
            if ($provider->getEmailOwnerClass() === $entityClass
                && $this->activityListChainProvider->isSupportedTargetEntity($entity)
            ) {
                $ownerColumnName = $this->emailOwnerStorage->getEmailOwnerFieldName($provider);
                break;
            }
        }

        if ($ownerColumnName === null) {
            return [];
        }

        return $this
            ->registry
            ->getRepository('OroEmailBundle:Email')
            ->getEmailsByOwnerEntity($entity, $ownerColumnName);
    }

    /**
     * @param object $entity
     * @return bool
     */
    public function supportOwnerProvider($entity)
    {
        $entityClass = ClassUtils::getClass($entity);
        foreach ($this->emailOwnerStorage->getProviders() as $provider) {
            if ($provider->getEmailOwnerClass() === $entityClass
                && $this->activityListChainProvider->isSupportedTargetEntity($entity)
            ) {
                return true;
            }
        }

        return false;
    }
}
