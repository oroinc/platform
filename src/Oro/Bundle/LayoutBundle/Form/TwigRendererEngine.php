<?php

namespace Oro\Bundle\LayoutBundle\Form;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Symfony\Component\Form\FormView;

class TwigRendererEngine extends BaseTwigRendererEngine
{
    /** @var ConfigManager */
    private $configManager;

    /** @var bool */
    private $profilerEnabled;

    /**
     * {@inheritdoc}
     */
    public function renderBlock(FormView $view, $resource, $blockName, array $variables = [])
    {
        $twigTemplate = current($resource);
        if ($this->isProfilerEnabled() && $twigTemplate instanceof \Twig_Template) {
            $variables['attr']['data-layout-debug-block-id'] = $variables['id'];
            $variables['attr']['data-layout-debug-block-template'] = $twigTemplate->getTemplateName();
        }

        return parent::renderBlock($view, $resource, $blockName, $variables);
    }

    /**
     * @param ConfigManager $configManager
     */
    public function setConfigManager(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @return bool
     */
    private function isProfilerEnabled()
    {
        if (null === $this->profilerEnabled) {
            $this->profilerEnabled = (bool)$this->configManager->get('oro_layout.debug_block_info');
        }

        return $this->profilerEnabled;
    }
}
