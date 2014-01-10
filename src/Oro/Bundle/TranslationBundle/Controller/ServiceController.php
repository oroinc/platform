<?php

namespace Oro\Bundle\TranslationBundle\Controller;

use FOS\Rest\Util\Codes;

use Symfony\Component\Intl\Intl;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller as BaseController;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\TranslationBundle\Translation\TranslationStatusInterface;
use Oro\Bundle\TranslationBundle\Provider\TranslationStatisticProvider;

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
        $status = Codes::HTTP_OK;
        $data   = [];

        $projects     = $this->get('oro_translation.statistic_provider')->getInstalledPackages();
        $proxyAdapter = $this->get('oro_translation.oro_translation_adapter');
        $service      = $this->get('oro_translation.service_provider');
        $service->setAdapter($proxyAdapter);

        // TODO: consider if we need this info for proxy adapter
        $proxyAdapter->setApiKey('e906a809346a58caeb6e5355dcabd2dc');
        $proxyAdapter->setProjectId('test-orocrm-project');

        $path   = $this->container->getParameter('kernel.root_dir')
            . DIRECTORY_SEPARATOR . 'Resources'
            . DIRECTORY_SEPARATOR . 'language-pack'
            . DIRECTORY_SEPARATOR . 'OroCRM'
            . DIRECTORY_SEPARATOR . 'OroCRM.zip';

        try {
            $result = $service->download($path, $projects, $code);

            if ($result) {
                $cm          = $this->get('oro_config.global');
                $configValue = $cm->get(TranslationStatusInterface::CONFIG_KEY);
                $configValue = $configValue ? $configValue : [];

                $updatedConfigValue = array_merge(
                    $configValue,
                    [$code => TranslationStatusInterface::STATUS_DOWNLOADED]
                );
                $cm->set(TranslationStatusInterface::CONFIG_KEY, $updatedConfigValue);
                $cm->flush();
            } else {
                $data['message'] = $this->get('translator')->trans('oro.translation.download.error');
            }
        } catch (\Exception $e) {
            $status          = Codes::HTTP_INTERNAL_SERVER_ERROR;
            $data['message'] = $e->getMessage();
        }

        return JsonResponse::create($data, $status);
    }
}
