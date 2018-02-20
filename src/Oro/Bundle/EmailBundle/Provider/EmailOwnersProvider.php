<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderInterface;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage;
use Oro\Component\PhpUtils\ArrayUtil;

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
        $ownerColumnName = $this->getOwnerColumnName($entity);
        if ($ownerColumnName === null) {
            return [];
        }

        return $this
            ->registry
            ->getRepository('OroEmailBundle:Email')
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

        return $this
            ->registry
            ->getRepository('OroEmailBundle:Email')
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

        return $this
            ->registry
            ->getRepository('OroEmailBundle:Email')
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
        if ($provider = $this->findEmailOwnerProvider($entityOrClass)) {
            return $this->emailOwnerStorage->getEmailOwnerFieldName($provider);
        }

        return null;
    }

    /**
     * @param object|string $entityOrClass
     *
     * @return EmailOwnerProviderInterface|null
     */
    protected function findEmailOwnerProvider($entityOrClass)
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
