<?php
declare(strict_types=1);

namespace Oro\Bundle\LocaleBundle\Action;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\TranslationBundle\Action\AbstractLanguageCondition;

/**
 * Checks if the specified language is set as the default language in the configuration.
 *
 * Usage:
 *
 *  conditions:
 *      '@is_default_language': "en_US"
 *
 *  conditions:
 *      '@is_default_language': $.language
 */
class IsDefaultLanguageCondition extends AbstractLanguageCondition
{
    private ConfigManager $configManager;

    public function __construct(ConfigManager $configManager, ManagerRegistry $doctrine)
    {
        $this->configManager = $configManager;
        parent::__construct($doctrine);
    }

    protected function isConditionAllowed($context): bool
    {
        $language = $this->getLanguage($context);
        if (null === $language) {
            return false;
        }

        $defaultLanguageCode = $this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::LANGUAGE)
        );

        return $language->getCode() === $defaultLanguageCode;
    }

    public function getName(): string
    {
        return 'is_default_language';
    }
}
