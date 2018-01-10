<?php

namespace Oro\Bundle\EmailBundle\Migrations\Data\ORM;

/**
 * Loads email templates from emails directory to the system.
 */
class LoadEmailTemplates extends AbstractEmailFixture
{
    /**
     * {@inheritdoc}
     */
    public function getEmailsDir()
    {
        return $this->container
            ->get('kernel')
            ->locateResource('@OroEmailBundle/Migrations/Data/ORM/emails');
    }
}
