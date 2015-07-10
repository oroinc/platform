<?php

namespace Oro\Bundle\EmailBundle\Entity\Provider;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EmailBundle\Entity\EmailAddressOwnerInterface;

/**
 * Email owner provider chain
 */
class EmailAddressOwnerProvider
{
    /**
     * @var EmailAddressOwnerProviderStorage
     */
    private $emailOwnerProviderStorage;

    /**
     * Constructor
     *
     * @param EmailAddressOwnerProviderStorage $emailOwnerProviderStorage
     */
    public function __construct(EmailAddressOwnerProviderStorage $emailOwnerProviderStorage)
    {
        $this->emailOwnerProviderStorage = $emailOwnerProviderStorage;
    }

    /**
     * Find an entity object which is an owner of the given email address
     *
     * @param \Doctrine\ORM\EntityManager $em
     * @param string $email
     *
*@return EmailAddressOwnerInterface
     */
    public function findEmailOwner(EntityManager $em, $email)
    {
        $emailOwner = null;
        foreach ($this->emailOwnerProviderStorage->getProviders() as $provider) {
            $emailOwner = $provider->findEmailOwner($em, $email);
            if ($emailOwner !== null) {
                break;
            }
        }

        return $emailOwner;
    }
}
