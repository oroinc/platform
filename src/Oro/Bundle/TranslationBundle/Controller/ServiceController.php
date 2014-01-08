<?php

namespace Oro\Bundle\TranslationBundle\Controller;

use FOS\Rest\Util\Codes;

use Symfony\Component\Intl\Intl;
use Symfony\Component\HttpFoundation\JsonResponse;
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
        $localeChoices     = Intl::getLocaleBundle()->getLocaleNames('en');
        $configValues      = $this->get('oro_config.global')->get(TranslationStatusInterface::CONFIG_KEY);

        return [
            'statistic' => $statisticProvider->get(),
            'config'    => (array)$configValues,
            'locale'    => $localeChoices
        ];
    }

    /**
     * @Route(
     *      "/download/{code}",
     *      name="oro_translation_download",
     *      defaults={"code" = null}
     * )
     */
    public function downloadAction($code)
    {
        // @TODO perform some actions, set config value if succeed

        return JsonResponse::create(null, Codes::HTTP_OK);
    }
}
