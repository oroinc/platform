<?php

namespace Oro\Bundle\CalendarBundle\Migrations\DataFixtures\ORM\v1_0;

use Oro\Bundle\EmailBundle\Migrations\DataFixtures\ORM\v1_0\AbstractEmailFixture;

class LoadEmailTemplates extends AbstractEmailFixture
{
    /**
     * Return path to email templates
     *
     * @return string
     */
    public function getEmailsDir()
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'data/emails';
    }
}
