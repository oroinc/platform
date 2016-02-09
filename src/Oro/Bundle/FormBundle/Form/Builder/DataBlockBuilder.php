<?php

namespace Oro\Bundle\FormBundle\Form\Builder;

use Symfony\Component\Form\FormView;

use Oro\Bundle\FormBundle\Config\BlockConfig;
use Oro\Bundle\FormBundle\Config\FormConfig;
use Oro\Bundle\FormBundle\Config\SubBlockConfig;

class DataBlockBuilder
{
    /** @var TemplateRendererInterface */
    protected $templateRenderer;

    /** @var string */
    protected $formVariableName;

    /** @var FormConfig */
    protected $formConfig;

    /**
     * @param TemplateRendererInterface $templateRenderer
     * @param string                    $formVariableName
     */
    public function __construct(TemplateRendererInterface $templateRenderer, $formVariableName)
    {
        $this->templateRenderer = $templateRenderer;
        $this->formVariableName = $formVariableName;
    }

    /**
     * @param FormView $form
     *
     * @return FormConfig
     */
    public function build(FormView $form)
    {
        $this->formConfig = new FormConfig();
        $this->doBuild($form);

        return $this->formConfig;
    }

    /**
     * @param FormView $form
     */
    public function doBuild(FormView $form)
    {
        if (isset($form->vars['block_config'])) {
            foreach ($form->vars['block_config'] as $code => $blockConfig) {
                $this->addBlock($code, $blockConfig);
            }
        }

        foreach ($form->children as $name => $child) {
            if (isset($child->vars['block']) || isset($child->vars['subblock'])) {
                $block = null;
                if ($this->formConfig->hasBlock($child->vars['block'])) {
                    $block = $this->formConfig->getBlock($child->vars['block']);
                }

                if (!$block) {
                    $block = $this->addBlock($child->vars['block']);
                }

                $subBlock = $this->getSubBlock($name, $child, $block);

                $tmpChild = $child;
                $formPath = '';

                while ($tmpChild->parent) {
                    $formPath = sprintf('.children[\'%s\']', $tmpChild->vars['name']) . $formPath;
                    $tmpChild = $tmpChild->parent;
                }

                $subBlock->addData(
                    $this->templateRenderer->render(
                        '{{ form_row(' . $this->formVariableName . $formPath . ') }}'
                    )
                );
            }

            $this->doBuild($child);
        }
    }

    /**
     * @param string      $name
     * @param FormView    $form
     * @param BlockConfig $block
     *
     * @return mixed|null|SubBlockConfig
     */
    protected function getSubBlock($name, FormView $form, BlockConfig $block)
    {
        $subBlock = null;
        if (!isset($form->vars['subblock'])) {
            $subBlocks = $block->getSubBlocks();
            $subBlock  = reset($subBlocks);
        } elseif ($block->hasSubBlock($form->vars['subblock'])) {
            $subBlock = $block->getSubBlock($form->vars['subblock']);
        }

        if (!$subBlock) {
            $code     = isset($form->vars['subblock'])
                ? $form->vars['subblock']
                : $name . '__subblock';
            $subBlock = $this->addSubBlock($block, $code);
        }

        return $subBlock;
    }

    /**
     * @param string $code
     * @param array  $config
     *
     * @return BlockConfig
     */
    protected function addBlock($code, $config = [])
    {
        if ($this->formConfig->hasBlock($code)) {
            $block = $this->formConfig->getBlock($code);
        } else {
            $block = new BlockConfig($code);
        }

        if (!empty($config['title'])) {
            $block->setTitle($config['title']);
        } else {
            $title = $block->getTitle();
            if (empty($title)) {
                $block->setTitle(ucfirst($code));
            }
        }
        if ($this->hasValue($config, 'description')) {
            $block->setDescription($config['description']);
        }
        if ($this->hasValue($config, 'class')) {
            $block->setClass($config['class']);
        }
        if ($this->hasValue($config, 'priority')) {
            $block->setPriority($config['priority']);
        }
        if (!empty($config['subblocks'])) {
            foreach ($config['subblocks'] as $subBlockCode => $subBlockConfig) {
                $this->addSubBlock($block, $subBlockCode, (array)$subBlockConfig);
            }
        }

        $this->formConfig->addBlock($block);

        return $block;
    }

    /**
     * @param BlockConfig $block
     * @param string      $code
     * @param array       $config
     *
     * @return SubBlockConfig
     */
    protected function addSubBlock(BlockConfig $block, $code, $config = [])
    {
        if ($block->hasSubBlock($code)) {
            $subBlock = $block->getSubBlock($code);
        } else {
            $subBlock = new SubBlockConfig($code);
        }

        if ($this->hasValue($config, 'title')) {
            $subBlock->setTitle($config['title']);
        }
        if ($this->hasValue($config, 'description')) {
            $subBlock->setDescription($config['description']);
        }
        if ($this->hasValue($config, 'tooltip')) {
            $subBlock->setTooltip($config['tooltip']);
        }
        if ($this->hasValue($config, 'priority')) {
            $subBlock->setPriority($config['priority']);
        }
        if ($this->hasValue($config, 'useSpan')) {
            $subBlock->setUseSpan($config['useSpan']);
        }

        $block->addSubBlock($subBlock);

        return $subBlock;
    }

    /**
     * @param array  $config
     * @param string $key
     *
     * @return bool
     */
    protected function hasValue($config, $key)
    {
        return isset($config[$key]) || array_key_exists($key, $config);
    }
}
