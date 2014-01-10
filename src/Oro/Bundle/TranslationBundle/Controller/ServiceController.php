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
     * Set up composer environment for package manager that needed for statistic
     */
    protected function setUpEnvironment()
    {
        $kernelRootDir = $this->container->getParameter('kernel.root_dir');
        putenv(sprintf('COMPOSER_HOME=%s/cache/composer', $kernelRootDir));
        chdir(realpath($kernelRootDir . '/../'));
    }

    /**
     * @Route("/available-translations", name="oro_translation_available_translations")
     * @Template
     */
    public function availableTranslationsAction()
    {
        $this->setUpEnvironment();
        $statisticProvider = $this->get('oro_translation.statistic_provider');
        $cm                = $this->get('oro_config.global');

        $stats            = $statisticProvider->get();
        $defaultValue     = $cm->get(LanguageType::CONFIG_KEY, true);
        $configValues     = $cm->get(TranslationStatusInterface::CONFIG_KEY);
        $configMetaValues = $cm->get(TranslationStatusInterface::META_CONFIG_KEY);
        $localeChoices    = Intl::getLocaleBundle()->getLocaleNames('en');

        return [
            'statistic'       => $stats,
            'defaultLanguage' => $defaultValue,
            'config'          => (array)$configValues,
            'metaConfig'      => (array)$configMetaValues,
            'locale'          => $localeChoices
        ];
    }

    /**
     * @Route(
     *      "/download/{code}/{translationStatus}",
     *      name="oro_translation_download",
     *      defaults={"code" = null, "translationStatus" = 0}
     * )
     */
    public function downloadAction($code, $translationStatus)
    {
        $this->setUpEnvironment();
        $status = Codes::HTTP_OK;
        $data   = [];

        $projects     = $this->get('oro_translation.statistic_provider')->getInstalledPackages();
        $proxyAdapter = $this->get('oro_translation.oro_translation_adapter');
        $service      = $this->get('oro_translation.service_provider');
        $service->setAdapter($proxyAdapter);

        $path   = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('download_') .
            DIRECTORY_SEPARATOR . $code . '.zip';

        try {
            $result = $service->download($path, $projects, $code);

            if ($result) {
                $this->setLanguageInstalled($code, $translationStatus);
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
     * @param int    $translationStatus
     */
    protected function setLanguageInstalled($code, $translationStatus)
    {
        $cm          = $this->get('oro_config.global');
        $configValue = $cm->get(TranslationStatusInterface::CONFIG_KEY);
        $configValue = $configValue ? $configValue : [];

        $updatedConfigValue = array_merge(
            $configValue,
            [$code => TranslationStatusInterface::STATUS_DOWNLOADED]
        );
        $cm->set(TranslationStatusInterface::CONFIG_KEY, $updatedConfigValue);

        $configMetaValue = $cm->get(TranslationStatusInterface::META_CONFIG_KEY);
        $configMetaValue = $configMetaValue ? $configMetaValue : [];

        $configMetaValue[$code]                      = isset($configMetaValue[$code]) ? $configMetaValue[$code] : [];
        $configMetaValue[$code]['translationStatus'] = $translationStatus;

        $cm->set(TranslationStatusInterface::META_CONFIG_KEY, $configMetaValue);
        $cm->flush();
    }
}
