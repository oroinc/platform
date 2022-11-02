<?php

namespace Oro\Bundle\EmailBundle\EventListener;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Oro\Bundle\EmailBundle\Entity\EmailAddress;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;

/**
 * Replaces EmailAddress target entity class in ORM metadata associations
 * with the class name of the EmailAddress entity proxy.
 */
class EmailAddressAssociationMetadataListener
{
    /** @var EmailAddressManager */
    private $emailAddressManager;

    public function __construct(EmailAddressManager $emailAddressManager)
    {
        $this->emailAddressManager = $emailAddressManager;
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $event): void
    {
        $emailAddressProxyClass = $this->emailAddressManager->getEmailAddressProxyClass();
        $metadata = $event->getClassMetadata();
        foreach ($metadata->associationMappings as $name => $mapping) {
            if (isset($mapping['targetEntity']) && EmailAddress::class === $mapping['targetEntity']) {
                $metadata->associationMappings[$name]['targetEntity'] = $emailAddressProxyClass;
            }
        }
    }
}
