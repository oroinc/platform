<?php

namespace Oro\Bundle\TranslationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller as BaseController;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class ServiceController extends BaseController
{
    /**
     * @Route("/available-translations", name="oro_translation_available_translations")
     * @Template
     */
    public function availableTranslationsAction()
    {
        $tranlationService = $this->get('oro_translation.service_provider');

        return [];
    }
}
