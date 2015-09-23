<?php

namespace Oro\Bundle\EmailBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageFilteredGrid;

/**
 * Class Emails
 *
 * @package Oro\Bundle\EmailBundle\Bundle\Pages
 * @method Email openEmails(string $bundlePath)
 * @method Email open(array $filter)
 * @method Email add()
 * {@inheritdoc}
 */
class Emails extends AbstractPageFilteredGrid
{
    const NEW_ENTITY_BUTTON = "//a[@title='Compose']";
    const URL = 'email/user-emails';

    public function entityNew()
    {
        return new Email($this->test);
    }

    public function entityView()
    {
        return new Email($this->test);
    }
}
