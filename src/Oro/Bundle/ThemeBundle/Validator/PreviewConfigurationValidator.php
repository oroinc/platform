<?php

namespace Oro\Bundle\ThemeBundle\Validator;

use Oro\Component\Config\CumulativeResourceInfo;
use Oro\Component\Config\CumulativeResourceManager;

/**
 * Theme configuration validator service.
 *
 * Service validates the validity of files theme.yml.
 * */
class PreviewConfigurationValidator implements ConfigurationValidatorInterface
{
    private const SUPPORTS_EXTENSIONS = ['png', 'jpg'];

    /**
     * {@inheritDoc}
     */
    public function supports(CumulativeResourceInfo $resource): bool
    {
        $configuration = $resource->data['configuration'] ?? [];

        return !empty($configuration);
    }

    /**
     * {@inheritDoc}
     */
    public function validate(iterable $resources): iterable
    {
        $messages = [];
        $manager = CumulativeResourceManager::getInstance();

        foreach ($resources as $resource) {
            $bundleDir = $manager->getBundleDir($resource->bundleClass);
            $bundlePublicDir = $bundleDir . '/Resources/public';
            $configuration = $resource->data['configuration'] ?? [];

            foreach ($configuration['sections'] ?? [] as $sKey => $section) {
                foreach ($section['options'] ?? [] as $oKey => $option) {
                    foreach ($option['previews'] ?? [] as $pKey => $preview) {
                        if (!$this->isImageValid($preview, $bundlePublicDir)) {
                            $configKey = implode(
                                '.',
                                [
                                    'configuration',
                                    'sections',
                                    $sKey,
                                    $oKey,
                                    $pKey
                                ]
                            );

                            $messages[] = sprintf(
                                '%s in %s. The preview file %s does not exist, ' .
                                'or the extension is not supported, supported extensions are [%s]',
                                $configKey,
                                $resource->bundleClass,
                                $preview,
                                implode(', ', self::SUPPORTS_EXTENSIONS)
                            );
                        }
                    }
                }
            }
        }

        return $messages;
    }

    private function isImageValid(string $preview, string $bundlePublicDir): bool
    {
        $publicPos = mb_strpos($preview, '/', mb_strlen('bundles/'));
        if ($publicPos !== false) {
            $bundlePath = $bundlePublicDir . mb_substr($preview, $publicPos);
            $fileInfo = new \SplFileInfo($bundlePath);

            if (file_exists($bundlePath)
                && in_array($fileInfo->getExtension(), self::SUPPORTS_EXTENSIONS)
            ) {
                return true;
            }
        }

        return false;
    }
}
