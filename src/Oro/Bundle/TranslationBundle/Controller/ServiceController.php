<?php

namespace Oro\Bundle\TranslationBundle\Controller;

use FOS\Rest\Util\Codes;

use Symfony\Component\Intl\Intl;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller as BaseController;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\LocaleBundle\Form\Type\LanguageType;
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
        $cm                = $this->get('oro_config.global');

        $stats        = $statisticProvider->get();
        $defaultValue = $cm->get(LanguageType::CONFIG_KEY, true);

        // @TODO find better solution
        if ($defaultValue == 'en') {
            $defaultValue = \Locale::composeLocale(['language' => $defaultValue, 'region' => 'US']);
        }
        $configValues  = $cm->get(TranslationStatusInterface::CONFIG_KEY);
        $localeChoices = Intl::getLocaleBundle()->getLocaleNames();

        return [
            'statistic'       => $stats,
            'defaultLanguage' => $defaultValue,
            'config'          => (array)$configValues,
            'locale'          => $localeChoices
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
        $status = Codes::HTTP_OK;
        $data   = [];

        $projects     = $this->get('oro_translation.statistic_provider')->getInstalledPackages();
        $proxyAdapter = $this->get('oro_translation.oro_translation_adapter');
        $service      = $this->get('oro_translation.service_provider');
        $service->setAdapter($proxyAdapter);


        $path = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $path = $path . ltrim(uniqid('download_'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $code . '.zip';


        try {
            $installed = $service->download($path, $projects, $code);

            if ($installed) {
                $this->setLanguageInstalled($code);
            } else {
                $data['message'] = $this->get('translator')->trans('oro.translation.download.error');
            }
        } catch (\Exception $e) {
            $status          = Codes::HTTP_INTERNAL_SERVER_ERROR;
            $data['message'] = $e->getMessage();
        }

        return JsonResponse::create($data, $status);
    }

    /**
     * Performs config modification for given language
     *
     * @param string $code
     */
    protected function setLanguageInstalled($code)
    {
        $statisticProvider = $this->get('oro_translation.statistic_provider');
        $stats             = $statisticProvider->get();
        $cm                = $this->get('oro_config.global');
        $configValue       = $cm->get(TranslationStatusInterface::CONFIG_KEY);
        $configValue       = $configValue ? $configValue : [];

        $updatedConfigValue = array_merge(
            $configValue,
            [$code => TranslationStatusInterface::STATUS_DOWNLOADED]
        );
        $cm->set(TranslationStatusInterface::CONFIG_KEY, $updatedConfigValue);

        $configMetaValue = $cm->get(TranslationStatusInterface::META_CONFIG_KEY);
        $configMetaValue = $configMetaValue ? $configMetaValue : [];

        $stats = array_filter(
            $stats,
            function ($langInfo) use ($code) {
                return $langInfo['code'] === $code;
            }
        );
        $lang  = array_pop($stats);

        $configMetaValue[$code]                  = isset($configMetaValue[$code]) ? $configMetaValue[$code] : [];
        $configMetaValue[$code]['lastBuildDate'] = $lang['lastBuildDate'];

        $cm->set(TranslationStatusInterface::META_CONFIG_KEY, $configMetaValue);
        $cm->flush();

        // clear statistic cache
        $statisticProvider->clear();
    }
}
