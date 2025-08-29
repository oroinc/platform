<?php

namespace Oro\Bundle\UserBundle\EventListener;

use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

/**
 * Removes 'password' column from users grid if 'user_login_password' feature is disabled.
 */
class UserPasswordGridListener
{
    public function __construct(
        private FeatureChecker $featureChecker
    ) {
    }

    public function onBuildAfter(BuildAfter $event): void
    {
        if ($this->featureChecker->isFeatureEnabled('user_login_password')) {
            return;
        }

        $config = $event->getDatagrid()->getConfig();
        $config->removeColumn('auth_status');
    }
}
