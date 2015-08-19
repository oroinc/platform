<?php

namespace Oro\Bundle\NavigationBundle\Provider;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class TitleTranslator
{
    /** @var TranslatorInterface */
    protected $translator;

    /** @var ConfigManager */
    protected $userConfigManager;

    /**
     * @param TranslatorInterface $translator
     * @param ConfigManager       $userConfigManager
     */
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
            $transItems[$key] = $this->translator->trans($transItem, $params);
        }

        return implode($delimiter, $transItems);
    }
}
