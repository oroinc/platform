<?php
declare(strict_types=1);

namespace Oro\Bundle\LocaleBundle\Action;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\TranslationBundle\Action\AbstractLanguageResultAction;
use Oro\Component\ConfigExpression\ContextAccessor;

/**
 * Checks if the specified language is set as the default language in the configuration.
 *
 * Sets the result (true or false) to the context attribute specified in the "result" parameter.
 *
 * Usage:
 *
 *  '@check_if_default_language':
 *      language: "en_US"
 *      result: $.isDefaultLanguage
 *
 *  '@check_if_default_language':
 *      language: $.language
 *      result: $.isDefaultLanguage
 */
class CheckIfDefaultLanguageAction extends AbstractLanguageResultAction
{
    private ConfigManager $configManager;

    public function __construct(ContextAccessor $actionContextAccessor, ConfigManager $configManager)
    {
        parent::__construct($actionContextAccessor);
        $this->configManager = $configManager;
    }

    protected function executeAction($context): void
    {
        $result = null;
        try {
            $defaultLanguageCode = $this->configManager->get(
                Configuration::getConfigKeyByName(Configuration::LANGUAGE)
            );
            $result = $this->getLanguageCode($context) === $defaultLanguageCode;
        } catch (\Throwable $e) {
            $result = false;
        }

        $this->contextAccessor->setValue($context, $this->resultPropertyPath, $result);
    }
}
