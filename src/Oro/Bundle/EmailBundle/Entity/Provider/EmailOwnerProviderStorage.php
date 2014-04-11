<?php

namespace Oro\Bundle\EmailBundle\Entity\Provider;

/**
 * A storage of email owner providers
 */
class EmailOwnerProviderStorage
{
    /**
     * @var EmailOwnerProviderInterface[]
     */
    private $emailOwnerProviders = array();

    /**
     * Add email owner provider
     *
     * @param EmailOwnerProviderInterface $provider
     */
    public function addProvider(EmailOwnerProviderInterface $provider)
    {
        $this->emailOwnerProviders[] = $provider;
    }

    /**
     * Get all email owner providers
     *
     * @return EmailOwnerProviderInterface[]
     */
    public function getProviders()
    {
        return $this->emailOwnerProviders;
    }

    /**
     * Gets field name for email owner for the given provider
     *
     * @param EmailOwnerProviderInterface $provider
     * @return string
     */
    public function getEmailOwnerFieldName(EmailOwnerProviderInterface $provider)
    {
        $key = 0;
        foreach ($this->emailOwnerProviders as $p) {
            $key++;
            if ($p === $provider) {
                break;
            }
        }

        return sprintf('owner%d', $key);
    }

    /**
     * Gets column name for email owner for the given provider
     *
     * @param EmailOwnerProviderInterface $provider
     * @return string
     */
    public function getEmailOwnerColumnName(EmailOwnerProviderInterface $provider)
    {
        $emailOwnerClass = $provider->getEmailOwnerClass();
        $prefix = strtolower(substr($emailOwnerClass, 0, strpos($emailOwnerClass, '\\')));
        if ($prefix === 'oro' || $prefix === 'orocrm') {
            // do not use prefix if email's owner is a part of BAP and CRM
            $prefix = '';
        } else {
            $prefix .= '_';
        }
        $suffix = strtolower(substr($emailOwnerClass, strrpos($emailOwnerClass, '\\') + 1));

        return sprintf('owner_%s%s_id', $prefix, $suffix);
    }
}
