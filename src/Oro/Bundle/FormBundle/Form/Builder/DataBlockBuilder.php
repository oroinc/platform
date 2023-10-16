<?php

namespace Oro\Bundle\FormBundle\Form\Builder;

use Oro\Bundle\FormBundle\Config\BlockConfig;
use Oro\Bundle\FormBundle\Config\FormConfig;
use Oro\Bundle\FormBundle\Config\SubBlockConfig;
use Symfony\Component\Form\FormView;
use Twig\Environment;

/**
 * Builds blocks configuration for the given FormView instance.
 */
class DataBlockBuilder
{
    protected FormConfig $formConfig;

    public function __construct(
        protected Environment $twig,
        protected $parentContext,
        protected string $formVariableName
    ) {
    }

    public function build(FormView $form): FormConfig
    {
        $this->formConfig = new FormConfig();
        $this->doBuild($form);

        return $this->formConfig;
    }

    public function doBuild(FormView $form): void
    {
        $this->addBlocks($form);

        if ($form->isRendered()) {
            // Child blocks are already rendered so there is no sense in going through them.
            return;
        }

        foreach ($form->children as $name => $child) {
            if ($child->isRendered()) {
                continue;
            }

            if (isset($child->vars['block']) || isset($child->vars['subblock'])) {
                $block = null;
                if ($this->formConfig->hasBlock($child->vars['block'])) {
                    $block = $this->formConfig->getBlock($child->vars['block']);
                }

                if (!$block) {
                    $block = $this->addBlock($child->vars['block']);
                }

                $subBlock = $this->getSubBlock($name, $child, $block);

                $html = $this->twig->render('@OroForm/Form/data_block_item.html.twig', [
                    'parentContext' => $this->parentContext,
                    'formFieldPath' => $this->formVariableName . $this->getFullPath($child)
                ]);

                $subBlock->setData(array_merge($subBlock->getData(), [$child->vars['name'] => $html]));
            }

            $this->doBuild($child);
        }
    }

    private function addBlocks(FormView $form): void
    {
        if (isset($form->vars['block_config'])) {
            foreach ($form->vars['block_config'] as $code => $blockConfig) {
                $this->addBlock($code, $blockConfig);
            }
        }
    }

    private function getFullPath(FormView $formView): string
    {
        $formPath = '';
        $tmpFormView = $formView;
        while ($tmpFormView->parent) {
            $formPath = sprintf('.children.%s', $tmpFormView->vars['name']) . $formPath;
            $tmpFormView = $tmpFormView->parent;
        }

        return $formPath;
    }

    /**
     * @param string      $name
     * @param FormView    $form
     * @param BlockConfig $block
     *
     * @return mixed|null|SubBlockConfig
     */
    protected function getSubBlock(string $name, FormView $form, BlockConfig $block)
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

    protected function addBlock(string $code, array $config = []): BlockConfig
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

    protected function addSubBlock(BlockConfig $block, string $code, array $config = []): SubBlockConfig
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
        if ($this->hasValue($config, 'description_style')) {
            $subBlock->setDescriptionStyle($config['description_style']);
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

    protected function hasValue(array $config, string $key): bool
    {
        return isset($config[$key]) || array_key_exists($key, $config);
    }
}
