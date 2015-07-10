<?php

namespace Oro\Bundle\EmailBundle\Entity\Provider;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EmailBundle\Entity\EmailAddressOwnerInterface;

/**
 * Defines an interface of an email owner provider
 */
interface EmailAddressOwnerProviderInterface
{
    /**
     * Get full name of email owner class
     *
     * @return string
     */
    public function getEmailOwnerClass();

    /**
     * Find an entity object which is an owner of the given email address
     *
     * @param \Doctrine\ORM\EntityManager $em
     * @param string $email
     *
*@return EmailAddressOwnerInterface
     */
    public function findEmailOwner(EntityManager $em, $email);
}
