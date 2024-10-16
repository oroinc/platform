<?php

namespace Oro\Bundle\ThemeBundle\Validator;

use Oro\Component\Config\CumulativeResourceManager;

/**
 * Validates that preview images exist and have accepted file extension.
 */
class PreviewConfigurationValidator implements ConfigurationValidatorInterface
{
    private const PUBLIC_PATH_PREFIX = 'bundles/';

    private array $bundlePublicDirs = [];

    public function __construct(
        private array $supportedExtensions
    ) {
    }

    #[\Override]
    public function validate(array $config): array
    {
        $messages = [];
        foreach ($config as $themeConfig) {
            $sections = $themeConfig['configuration']['sections'] ?? [];
            foreach ($sections as $sectionName => $section) {
                $options = $section['options'] ?? [];
                foreach ($options as $optionName => $option) {
                    $previews = $option['previews'] ?? [];
                    foreach ($previews as $pathName => $path) {
                        if ($this->isImageValid($path)) {
                            continue;
                        }
                        $messages[] = sprintf(
                            '%s. The preview file %s does not exist'
                            . ' or the extension is not supported. Supported extensions are [%s].',
                            implode(
                                '.',
                                ['configuration.sections', $sectionName, 'options', $optionName, 'previews', $pathName]
                            ),
                            $path,
                            implode(', ', $this->supportedExtensions)
                        );
                    }
                }
            }
        }

        return $messages;
    }

    private function isImageValid(string $path): bool
    {
        if (!str_starts_with($path, self::PUBLIC_PATH_PREFIX)) {
            return false;
        }

        $publicPathPrefixLength = \strlen(self::PUBLIC_PATH_PREFIX);
        $bundleNameEndPos = strpos($path, '/', $publicPathPrefixLength);
        if (false === $bundleNameEndPos) {
            return false;
        }

        $bundleName = substr($path, $publicPathPrefixLength, $bundleNameEndPos - $publicPathPrefixLength);
        $bundlePublicDir = $this->getBundlePublicDir($bundleName);
        if (null === $bundlePublicDir) {
            return false;
        }

        $bundlePath = $bundlePublicDir . substr($path, $bundleNameEndPos);

        return
            file_exists($bundlePath)
            && \in_array((new \SplFileInfo($bundlePath))->getExtension(), $this->supportedExtensions, true);
    }

    private function getBundlePublicDir(string $bundleName): ?string
    {
        $bundleName = strtolower($bundleName);
        if (!\array_key_exists($bundleName, $this->bundlePublicDirs)) {
            $bundleClass = null;
            $bundleFullName = $bundleName . 'bundle';
            $manager = CumulativeResourceManager::getInstance();
            foreach ($manager->getBundles() as $name => $class) {
                if (strtolower($name) === $bundleFullName) {
                    $bundleClass = $class;
                    break;
                }
            }
            $this->bundlePublicDirs[$bundleName] = null !== $bundleClass
                ? $manager->getBundleDir($bundleClass) . '/Resources/public'
                : null;
        }

        return $this->bundlePublicDirs[$bundleName];
    }
}
