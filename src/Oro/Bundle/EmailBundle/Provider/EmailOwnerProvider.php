<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage;

class EmailOwnerProvider
{
    /**
     * @var EmailOwnerProviderStorage
     */
    protected $emailOwnerStorage;

    /** @var Registry */
    protected $registry;

    /**
     * @param EmailOwnerProviderStorage $emailOwnerStorage
     * @param Registry $registry
     */
    public function __construct(EmailOwnerProviderStorage $emailOwnerStorage, Registry $registry)
    {
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
        foreach ($this->emailOwnerStorage->getProviders() as $provider) {
            if ($provider->getEmailOwnerClass() === ClassUtils::getClass($entity)) {
                $ownerColumnName = $this->emailOwnerStorage->getEmailOwnerFieldName($provider);
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
        foreach ($this->emailOwnerStorage->getProviders() as $provider) {
            if ($provider->getEmailOwnerClass() === ClassUtils::getClass($entity)) {
                return true;
            }
        }

        return false;
    }
}
