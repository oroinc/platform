<?php

namespace Oro\Bundle\LayoutBundle\Assetic;

use Assetic\Factory\Resource\ResourceInterface;

use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Oro\Component\PhpUtils\ArrayUtil;

class LayoutResource implements ResourceInterface
{
    const RESOURCE_ALIAS = 'layout';

    /** @var ThemeManager */
    protected $themeManager;

    /** @var string */
    protected $outputDir;

    /**
     * @param ThemeManager $themeManager
     * @param string $outputDir
     */
    public function __construct(ThemeManager $themeManager, $outputDir)
    {
        $this->themeManager = $themeManager;
        $this->outputDir = $outputDir;
    }

    /**
     * @inheritdoc
     */
    public function isFresh($timestamp)
    {
        return true;
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

        return $assets;
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
        $configInputs = [];
        $restInputs = [];
        foreach ($inputs as $input) {
            if (strpos($input, '/configs/') !== false) {
                $configInputs[] = $input;
            } else {
                $restInputs[] = $input;
            }
        }
        $inputs = array_merge($configInputs, $restInputs);

        $inputsContent = '';
        foreach ($inputs as $input) {
            $inputsContent .= '@import "../'.$input.'"'.";\n";
        }

        $file = realpath($this->outputDir) . '/' . $output . '.'. $extension;
        if (false === @file_put_contents($file, $inputsContent)) {
            throw new \RuntimeException('Unable to write file ' . $file);
        }

        return $file;
    }
}
