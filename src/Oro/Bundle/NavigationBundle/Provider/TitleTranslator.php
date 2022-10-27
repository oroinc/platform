<?php

namespace Oro\Bundle\NavigationBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Translates each part of the title template individually.
 */
class TitleTranslator
{
    /** @var TranslatorInterface */
    protected $translator;

    /** @var ConfigManager */
    protected $userConfigManager;

    public function __construct(TranslatorInterface $translator, ConfigManager $userConfigManager)
    {
        $this->translator        = $translator;
        $this->userConfigManager = $userConfigManager;
    }

    /**
     * Checks if the given template contains several parts and if so translate each part individually
     *
     * @param string $titleTemplate
     * @param array  $params
     *
     * @return string
     */
    public function trans($titleTemplate, array $params = [])
    {
        if (!$titleTemplate) {
            return $titleTemplate;
        }

        $delimiter  = ' ' . $this->userConfigManager->get('oro_navigation.title_delimiter') . ' ';
        $transItems = explode($delimiter, $titleTemplate);
        foreach ($transItems as $key => $transItem) {
            $transItems[$key] = $this->translator->trans((string) $transItem, $params);
        }

        return implode($delimiter, $transItems);
    }
}
