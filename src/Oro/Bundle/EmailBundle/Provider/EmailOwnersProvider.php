<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderInterface;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage;
use Oro\Component\PhpUtils\ArrayUtil;

/**
 * Provides information about email owners.
 */
class EmailOwnersProvider
{
    private EmailOwnerProviderStorage $emailOwnerStorage;
    private ActivityListChainProvider $activityListChainProvider;
    private ManagerRegistry $doctrine;

    public function __construct(
        ActivityListChainProvider $activityListChainProvider,
        EmailOwnerProviderStorage $emailOwnerStorage,
        ManagerRegistry $doctrine
    ) {
        $this->activityListChainProvider = $activityListChainProvider;
        $this->emailOwnerStorage = $emailOwnerStorage;
        $this->doctrine = $doctrine;
    }

    /**
     * Get email entities from owner entity
     *
     * @param object $entity
     * @return array
     */
    public function getEmailsByOwnerEntity($entity)
    {
        $ownerColumnName = $this->getOwnerColumnName($entity);
        if ($ownerColumnName === null) {
            return [];
        }

        return $this->doctrine->getRepository(Email::class)
            ->getEmailsByOwnerEntity($entity, $ownerColumnName);
    }

    /**
     * Get QB email entities from owner entity
     *
     * @param object $entity
     *
     * @return array
     */
    public function getQBEmailsByOwnerEntity($entity)
    {
        $ownerColumnName = $this->getOwnerColumnName($entity);
        if ($ownerColumnName === null) {
            return [];
        }

        return $this->doctrine->getRepository(Email::class)
            ->createEmailsByOwnerEntityQbs($entity, $ownerColumnName);
    }

    /**
     * @param object $entity
     * @return bool
     */
    public function hasEmailsByOwnerEntity($entity)
    {
        $ownerColumnName = $this->getOwnerColumnName($entity);
        if (!$ownerColumnName) {
            return false;
        }

        return $this->doctrine->getRepository(Email::class)
            ->hasEmailsByOwnerEntity($entity, $ownerColumnName);
    }

    /**
     * @param object $entity
     * @return bool
     */
    public function supportOwnerProvider($entity)
    {
        return (bool) $this->findEmailOwnerProvider($entity);
    }

    /**
     * @return string[]
     */
    public function getSupportedEmailOwnerClassNames()
    {
        $providers = $this->emailOwnerStorage->getProviders();

        $classes = [];
        foreach ($providers as $provider) {
            $classes[] = $provider->getEmailOwnerClass();
        }

        return $classes;
    }

    /**
     * @param object|string $entityOrClass
     *
     * @return string|null
     */
    public function getOwnerColumnName($entityOrClass)
    {
        $provider = $this->findEmailOwnerProvider($entityOrClass);

        return $provider
            ? $this->emailOwnerStorage->getEmailOwnerFieldName($provider)
            : null;
    }

    /**
     * @param object|string $entityOrClass
     *
     * @return EmailOwnerProviderInterface|null
     */
    private function findEmailOwnerProvider($entityOrClass)
    {
        $entityClass = is_object($entityOrClass) ? ClassUtils::getClass($entityOrClass) : $entityOrClass;

        return ArrayUtil::find(
            function (EmailOwnerProviderInterface $provider) use ($entityOrClass, $entityClass) {
                return $provider->getEmailOwnerClass() === $entityClass
                    && $this->activityListChainProvider->isSupportedTargetEntity($entityOrClass);
            },
            $this->emailOwnerStorage->getProviders()
        );
    }
}
