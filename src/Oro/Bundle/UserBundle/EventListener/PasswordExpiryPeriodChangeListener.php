<?php

namespace Oro\Bundle\UserBundle\EventListener;

use Doctrine\DBAL\Types\Type;
use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\UserBundle\DependencyInjection\OroUserExtension;
use Oro\Bundle\UserBundle\Provider\PasswordChangePeriodConfigProvider;

class PasswordExpiryPeriodChangeListener
{
    const SETTING_VALUE   = 'password_change_period';
    const SETTING_UNIT    = 'password_change_period_unit';
    const SETTING_ENABLED = 'password_change_period_enabled';

    /** @var Registry */
    protected $registry;

    /** @var PasswordChangePeriodConfigProvider */
    protected $provider;

    /**
     * @param Registry $registry
     */
    public function __construct(Registry $registry, PasswordChangePeriodConfigProvider $provider)
    {
        $this->registry = $registry;
        $this->provider = $provider;
    }

    public function onConfigUpdate(ConfigUpdateEvent $event)
    {
        $settingsUnitKey    = $this->getSettingsUnitKey();
        $settingsValueKey   = $this->getSettingsValueKey();
        $settingsEnabledKey = $this->getSettingsEnabledKey();

        if ($event->isChanged($settingsEnabledKey)) {
            $this->resetPasswordExpiryDates();
        } else if (!$this->provider->isPasswordChangePeriodEnabled()) {
            return;
        }

        if ($event->isChanged($settingsUnitKey) || $event->isChanged($settingsValueKey)) {
            $this->resetPasswordExpiryDates();
        }
    }

    protected function resetPasswordExpiryDates()
    {
        $newExpiryDate = $this->provider->getPasswordExpiryDateFromNow();

        $qb = $this->registry->getEntityManager()->createQueryBuilder();
        $qb
            ->update('OroUserBundle:User', 'u')
            ->set('u.passwordExpiresAt', ':expiryDate')
            ->setParameter('expiryDate', $newExpiryDate, Type::DATETIME);

        $qb->getQuery()->execute();
    }

    /**
     * @return string
     */
    protected function getSettingsEnabledKey()
    {
        return implode(ConfigManager::SECTION_MODEL_SEPARATOR, [OroUserExtension::ALIAS, self::SETTING_ENABLED]);
    }

    /**
     * @return string
     */
    protected function getSettingsUnitKey()
    {
        return implode(ConfigManager::SECTION_MODEL_SEPARATOR, [OroUserExtension::ALIAS, self::SETTING_UNIT]);
    }

    /**
     * @return string
     */
    protected function getSettingsValueKey()
    {
        return implode(ConfigManager::SECTION_MODEL_SEPARATOR, [OroUserExtension::ALIAS, self::SETTING_VALUE]);
    }
}
