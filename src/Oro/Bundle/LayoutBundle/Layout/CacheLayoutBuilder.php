<?php

namespace Oro\Bundle\LayoutBundle\Layout;

use Oro\Bundle\LayoutBundle\Cache\RenderCache;
use Oro\Component\Layout\BlockFactoryInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\BlockViewCache;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataAccessor;
use Oro\Component\Layout\DeferredLayoutManipulatorInterface;
use Oro\Component\Layout\ExpressionLanguage\ExpressionProcessor;
use Oro\Component\Layout\LayoutBuilder;
use Oro\Component\Layout\LayoutRegistryInterface;
use Oro\Component\Layout\LayoutRendererRegistryInterface;
use Oro\Component\Layout\OptionValueBag;
use Oro\Component\Layout\RawLayoutBuilderInterface;

/**
 * Overrides LayoutBuilder to calculate cache metadata in advance and to remove children of cached blocks.
 */
class CacheLayoutBuilder extends LayoutBuilder
{
    /**
     * @var RenderCache
     */
    private $renderCache;

    public function __construct(
        LayoutRegistryInterface $registry,
        RawLayoutBuilderInterface $rawLayoutBuilder,
        DeferredLayoutManipulatorInterface $layoutManipulator,
        BlockFactoryInterface $blockFactory,
        LayoutRendererRegistryInterface $rendererRegistry,
        ExpressionProcessor $expressionProcessor,
        RenderCache $renderCache,
        BlockViewCache $blockViewCache = null
    ) {
        $this->renderCache = $renderCache;
        parent::__construct(
            $registry,
            $rawLayoutBuilder,
            $layoutManipulator,
            $blockFactory,
            $rendererRegistry,
            $expressionProcessor,
            $blockViewCache
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function processBlockViewData(
        BlockView $blockView,
        ContextInterface $context,
        DataAccessor $data,
        $deferred,
        $encoding
    ) {
        $cached = $this->isBlockViewCached($blockView, $context, $data, $deferred, $encoding);

        if ($deferred) {
            $this->expressionProcessor->processExpressions($blockView->vars, $context, $data, true, $encoding);
        }

        $this->buildValueBags($blockView);

        /** Removes child blocks in case parent block is hidden or cached */
        if (!$blockView->isVisible() || $cached) {
            foreach ($blockView->children as $key => $childView) {
                unset($blockView->children[$key]);
            }

            return;
        }

        foreach ($blockView->children as $key => $childView) {
            $this->processBlockViewData($childView, $context, $data, $deferred, $encoding);

            if (!$childView->isVisible()) {
                unset($blockView->children[$key]);
            }
        }
    }

    /**
     * @param BlockView        $blockView
     * @param ContextInterface $context
     * @param DataAccessor     $data
     * @param bool             $deferred
     * @param string           $encoding
     * @return bool
     */
    protected function isBlockViewCached(
        BlockView $blockView,
        ContextInterface $context,
        DataAccessor $data,
        $deferred,
        $encoding
    ): bool {
        $cached = false;
        if ($deferred && array_key_exists('cache', $blockView->vars) && false != $blockView->vars['cache']) {
            // Evaluate expressions in cache metadata if they are defined
            $values = ['cache' => $blockView->vars['cache']];
            $this->expressionProcessor->processExpressions($values, $context, $data, true, $encoding);
            $blockView->vars['cache'] = $values['cache'];

            $cached = $this->renderCache->isCached($blockView);
            if ($cached) {
                $blockView->vars['_cached'] = $cached;
            }
        }

        return $cached;
    }

    protected function buildValueBags(BlockView $view)
    {
        array_walk_recursive(
            $view->vars,
            static function (&$var) {
                if ($var instanceof OptionValueBag) {
                    $var = $var->buildValue();
                }
            }
        );
    }
}
