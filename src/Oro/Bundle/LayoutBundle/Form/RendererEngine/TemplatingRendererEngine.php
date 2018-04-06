<?php

namespace Oro\Bundle\LayoutBundle\Form\RendererEngine;

use Oro\Bundle\LayoutBundle\Form\RendererEngineTrait;
use Oro\Component\Layout\Form\RendererEngine\FormRendererEngineInterface;
use Symfony\Component\Form\Extension\Templating\TemplatingRendererEngine as BaseEngine;
use Symfony\Component\Templating\EngineInterface;

/**
 * Extends Templating Renderer Engine to add possibility for changing default themes after container was locked
 */
class TemplatingRendererEngine extends BaseEngine implements FormRendererEngineInterface
{
    use RendererEngineTrait;

    /**
     * @var EngineInterface
     */
    private $engine;

    /**
     * {@inheritdoc}
     */
    public function __construct(EngineInterface $engine, array $defaultThemes = [])
    {
        parent::__construct($engine, $defaultThemes);

        $this->engine = $engine;
    }

    /**
     * {@inheritdoc}
     */
    protected function loadResourceFromTheme($cacheKey, $blockName, $theme)
    {
        parent::loadResourceFromTheme($cacheKey, $blockName, $theme);

        if ($this->engine->exists($templateName = $theme.':'.$blockName.'.html.php')) {
            $this->resources[$cacheKey][$blockName] = $templateName;

            if (array_key_exists($cacheKey, $this->themes)) {
                for ($i = count($this->themes[$cacheKey]) - 1; $i >= 0; $i--) {
                    $templateName = $this->themes[$cacheKey][$i] . ':' . $blockName . '.html.php';
                    if ($this->engine->exists($templateName)) {
                        if (!array_key_exists($blockName, $this->resourcesHierarchy)) {
                            $this->resourcesHierarchy[$blockName] = [$templateName];
                        } else {
                            array_unshift($this->resourcesHierarchy[$blockName], $templateName);
                        }
                    }
                }
            }

            for ($i = count($this->defaultThemes) - 1; $i >= 0; $i--) {
                if ($this->engine->exists($templateName = $this->defaultThemes[$i].':'.$blockName.'.html.php')) {
                    if (!array_key_exists($blockName, $this->resourcesHierarchy)) {
                        $this->resourcesHierarchy[$blockName] = [$templateName];
                    } else {
                        array_unshift($this->resourcesHierarchy[$blockName], $templateName);
                    }
                }
            }

            return true;
        }

        return false;
    }
}
