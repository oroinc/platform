<?php

namespace Oro\Bundle\ConfigBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPage;

/**
 * Class Configuration
 *
 * @package Oro\Bundle\ConfigBundle\Tests\Selenium\Pages
 * @method UserEmailSettings openUserEmailSettings(string $bundlePath)
 * {@inheritdoc}
 */
class UserEmailSettings extends EmailSettings
{
    const URL = 'config/user/profile/platform/email_configuration';
}
