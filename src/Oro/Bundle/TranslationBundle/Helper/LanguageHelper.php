<?php

namespace Oro\Bundle\TranslationBundle\Helper;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\TranslationBundle\Entity\Language;

class LanguageHelper
{
    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param Language $language
     */
    public function updateSystemConfiguration(Language $language)
    {
        $languages = (array)$this->configManager->get($this->getConfigurationName(), true);

        if ($language->isEnabled()) {
            if (!in_array($language->getCode(), $languages, true)) {
                $languages[] = $language->getCode();
            }
        } else {
            if (false !== ($index = array_search($language->getCode(), $languages, true))) {
                unset($languages[$index]);
            }
        }

        $this->configManager->set($this->getConfigurationName(), $languages);
        $this->configManager->flush();
    }

    /**
     * @return string
     */
    protected function getConfigurationName()
    {
        return Configuration::getConfigKeyByName(Configuration::LANGUAGES);
    }
}
