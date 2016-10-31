<?php

namespace Oro\Bundle\UserBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class PasswordChangePeriodConfigProvider
{
    /** @var ConfigManager */
    protected $configManager;

    const DAYS   = 'days';
    const WEEKS  = 'weeks';
    const MONTHS = 'months';

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    public function isPasswordChangePeriodEnabled()
    {
        return $this->configManager->get('oro_user.password_change_period_enabled');
    }

    /**
     * @return \DateTime|null
     */
    public function getPasswordExpiryDateFromNow()
    {
        if (!$this->isPasswordChangePeriodEnabled()) {
            return null;
        }

        $periodValue = $this->configManager->get('oro_user.password_change_period');
        $periodUnit  = $this->configManager->get('oro_user.password_change_period_unit');
        $interval = 'P';

        switch ($periodUnit) {
            case self::DAYS:
                $interval .= $periodValue . 'D';
                break;
            case self::WEEKS:
                $interval .= $periodValue*7 . 'D';
                break;
            case self::MONTHS:
                $interval .= $periodValue . 'M';
                break;
            default:
                throw new \InvalidArgumentException('Incorrect configuration value for password change period unit.');
        }

        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        return $now->add(new \DateInterval($interval));
    }
}
