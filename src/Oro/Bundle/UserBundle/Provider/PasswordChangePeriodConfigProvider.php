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

    const PASSWORD_EXPIRY_ENABLED_KEY     = 'oro_user.password_change_period_enabled';
    const PASSWORD_EXPIRY_PERIOD_KEY      = 'oro_user.password_change_period';
    const PASSWORD_EXPIRY_PERIOD_UNIT_KEY = 'oro_user.password_change_period_unit';

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @return bool
     */
    public function isPasswordChangePeriodEnabled()
    {
        return (bool) $this->configManager->get(self::PASSWORD_EXPIRY_ENABLED_KEY);
    }

    /**
     * @return \DateTime|null
     */
    public function getPasswordExpiryDateFromNow()
    {
        if (!$this->isPasswordChangePeriodEnabled()) {
            return null;
        }

        $periodValue = $this->configManager->get(self::PASSWORD_EXPIRY_PERIOD_KEY);
        $periodUnit  = $this->configManager->get(self::PASSWORD_EXPIRY_PERIOD_UNIT_KEY);
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
