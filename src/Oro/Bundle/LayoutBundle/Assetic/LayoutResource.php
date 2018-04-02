<?php

namespace Oro\Bundle\LayoutBundle\Assetic;

use Assetic\Factory\Resource\ResourceInterface;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Oro\Component\PhpUtils\ArrayUtil;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

class LayoutResource implements ResourceInterface
{
    const RESOURCE_ALIAS = 'layout';

    /** @var ThemeManager */
    protected $themeManager;

    /** @var Filesystem */
    protected $filesystem;

    /** @var string */
    protected $outputDir;

    /** @var array */
    protected $mtimeOutputs = [];

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param ThemeManager $themeManager
     * @param Filesystem $filesystem
     * @param string $outputDir
     */
    public function __construct(
        ThemeManager $themeManager,
        Filesystem $filesystem,
        $outputDir
    ) {
        $this->themeManager = $themeManager;
        $this->filesystem = $filesystem;
        $this->outputDir = $outputDir;
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return LayoutResource
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isFresh($timestamp)
    {
        $maxLastModified = 0;

        foreach ($this->themeManager->getAllThemes() as $theme) {
            $assets = $this->collectThemeAssets($theme);
            foreach ($assets as $assetKey => $asset) {
                $maxLastModified = max($maxLastModified, $this->getLastModified($asset['inputs']));
            }
        }

        return $maxLastModified > $timestamp;
    }

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        return self::RESOURCE_ALIAS;
    }

    /**
     * @inheritdoc
     */
    public function getContent()
    {
        $formulae = [];
        $themes = $this->themeManager->getAllThemes();
        foreach ($themes as $theme) {
            $formulae += $this->collectThemeFormulae($theme);
        }
        return $formulae;
    }

    /**
     * @param Theme $theme
     * @return array
     */
    protected function collectThemeFormulae(Theme $theme)
    {
        $formulae = [];
        $assets = $this->collectThemeAssets($theme);
        foreach ($assets as $assetKey => $asset) {
            if (!isset($asset['output']) || empty($asset['inputs'])) {
                continue;
            }
            $name = self::RESOURCE_ALIAS . '_' . $theme->getName(). '_' . $assetKey;
            $asset = $this->prepareAssets($asset);
            $formulae[$name] = [
                $asset['inputs'],
                $asset['filters'],
                [
                    'output' => $asset['output'],
                    'name' => $name,
                ],
            ];
        }
        return $formulae;
    }

    /**
     * @param Theme $theme
     * @return array
     */
    protected function collectThemeAssets(Theme $theme)
    {
        $assets = $theme->getConfigByKey('assets', []);

        $parentTheme = $theme->getParentTheme();
        if ($parentTheme) {
            $parentTheme = $this->themeManager->getTheme($parentTheme);
            $assets = ArrayUtil::arrayMergeRecursiveDistinct($this->collectThemeAssets($parentTheme), $assets);
        }

        $this->overrideInputs($assets);

        return $assets;
    }

    /**
     * @param array $assets
     */
    protected function overrideInputs(array &$assets)
    {
        foreach ($assets as &$asset) {
            if (!isset($asset['inputs'])) {
                continue;
            }

            foreach ($asset['inputs'] as $key => $value) {
                if (!is_array($value)) {
                    continue;
                }

                foreach ($value as $form => $to) {
                    $keyToReplace = array_search($form, $asset['inputs']);

                    if ($to) {
                        $asset['inputs'][$keyToReplace] = $to;
                    } else {
                        unset($asset['inputs'][$keyToReplace]);
                    }
                }

                unset($asset['inputs'][$key]);
            }
        }
    }

    /**
     * @param array $asset
     * @return array
     */
    protected function prepareAssets($asset)
    {
        $inputs = $asset['inputs'];
        $inputsByExtension = [];
        foreach ($inputs as $input) {
            $inputsByExtension[pathinfo($input)['extension']][] = $input;
        }

        $inputs = [];

        // Merge .less files first, than -> .scss into cumulative .css for theme
        ksort($inputsByExtension);

        foreach ($inputsByExtension as $extension => $extensionInputs) {
            if ($extension === 'css' || count($extensionInputs) === 1) {
                $inputs = array_merge($inputs, $extensionInputs);
            } else {
                $inputs[] = $this->joinInputs($asset['output'], $extension, $extensionInputs);
            }
        }

        $asset['inputs'] = $inputs;
        return $asset;
    }

    /**
     * @param array $output
     * @param string $extension
     * @param array $inputs
     * @return string
     */
    protected function joinInputs($output, $extension, $inputs)
    {
        $settingsInputs = [];
        $variablesInputs = [];
        $restInputs = [];
        foreach ($inputs as $input) {
            if (strpos($input, '/settings/') !== false) {
                $settingsInputs[] = $input;
            } elseif (strpos($input, '/variables/') !== false) {
                $variablesInputs[] = $input;
            } else {
                $restInputs[] = $input;
            }
        }
        $inputs = array_merge($settingsInputs, $variablesInputs, $restInputs);

        $file = $this->outputDir . '/' . $output . '.'. $extension;

        $mtime = $this->getLastModified($inputs);
        if (!array_key_exists($file, $this->mtimeOutputs) || $this->mtimeOutputs[$file] < $mtime) {
            $inputsContent = '';
            foreach ($inputs as $input) {
                $inputsContent .= '@import "../' . $input . '"' . ";\n";
            }

            $this->filesystem->mkdir(dirname($file), 0777);
            if (false === @file_put_contents($file, $inputsContent)) {
                throw new \RuntimeException('Unable to write file ' . $file);
            }

            $this->mtimeOutputs[$file] = $mtime;
        }

        return $file;
    }

    /**
     * @param array  $inputs
     *
     * @return int|mixed
     */
    private function getLastModified(array $inputs)
    {
        $mtime = 0;
        foreach ($inputs as $input) {
            $file = realpath($this->outputDir) . '/' . $input;
            if (file_exists($file)) {
                $mtime = max($mtime, filemtime($file));
            } else {
                $this->logger->debug(sprintf('Could not find file %s, declared in assets.yml.', $input));
            }
        }

        return $mtime;
    }
}
