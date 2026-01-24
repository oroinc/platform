<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EntityBundle\Model\EntityAlias;
use Oro\Bundle\EntityBundle\Provider\EntityAliasProviderInterface;

/**
 * Provides entity aliases for email-related entities.
 *
 * Supplies singular and plural aliases for the email address proxy entity,
 * enabling proper entity identification in API and search contexts.
 */
class EmailEntityAliasProvider implements EntityAliasProviderInterface
{
    /** @var string */
    protected $emailAddressProxyClass;

    public function __construct(EmailAddressManager $emailAddressManager)
    {
        $this->emailAddressProxyClass = $emailAddressManager->getEmailAddressProxyClass();
    }

    #[\Override]
    public function getEntityAlias($entityClass)
    {
        if ($entityClass === $this->emailAddressProxyClass) {
            return new EntityAlias('emailaddress', 'emailaddresses');
        }

        return null;
    }
}
