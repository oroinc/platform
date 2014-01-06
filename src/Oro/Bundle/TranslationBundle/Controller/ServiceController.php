<?php

namespace Oro\Bundle\TranslationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller as BaseController;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\TranslationBundle\Translation\TranslationStatusInterface;

class ServiceController extends BaseController
{
    /**
     * @Route("/available-translations", name="oro_translation_available_translations")
     * @Template
     */
    public function availableTranslationsAction()
    {
        $statisticProvider = $this->get('oro_translation.statistic_provider');
        $configValues      = $this->get('oro_config.global')->get(TranslationStatusInterface::CONFIG_KEY);

        return [
            'statistic' => $statisticProvider->get(),
            'config'    => (array)$configValues
        ];
    }
}
