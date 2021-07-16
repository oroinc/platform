<?php

namespace Oro\Bundle\LayoutBundle\DataCollector;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LayoutBundle\Layout\LayoutContextHolder;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\ContextDataCollection;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\ContextItemInterface;
use Oro\Component\Layout\LayoutContext;
use Symfony\Component\ExpressionLanguage\ParsedExpression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * Collector of layouts
 */
class LayoutDataCollector extends DataCollector
{
    const NAME = 'layout';

    /** @var LayoutContextHolder */
    private $contextHolder;

    /** @var ConfigManager */
    private $configManager;

    /** @var bool */
    private $isDebug;

    /** @var array */
    private $dataByBlock = [];

    /** @var BlockView */
    private $rootBlockView;

    /** @var array */
    private $notAppliedActions = [];

    /** @var array */
    private $excludedOptions = [
        'block',
        'blocks',
        'block_type',
        'attr',
    ];

    /** @var bool */
    private $debugDeveloperToolbar;

    /**
     * @param LayoutContextHolder $contextHolder
     * @param ConfigManager       $configManager
     * @param bool                $isDebug
     */
    public function __construct(LayoutContextHolder $contextHolder, ConfigManager $configManager, $isDebug = false)
    {
        $this->contextHolder = $contextHolder;
        $this->configManager = $configManager;
        $this->isDebug = $isDebug;

        $this->reset();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $context = $this->contextHolder->getContext();
        if ($context) {
            $this->collectContextItems($context);
            $this->collectContextData($context);
        }

        $this->buildFinalBlockTree();

        $this->data['count'] = count($this->dataByBlock);
        $this->data['not_applied_actions_count'] = count($this->notAppliedActions);
        $this->data['not_applied_actions'] = $this->notAppliedActions;
    }

    /**
     * Collect view vars for BlockView-s, save root BlockView, check if block is visible
     */
    public function collectBlockView(BlockInterface $block, BlockView $view)
    {
        if ($this->isDebug && $this->isDebugDeveloperToolbar()) {
            if (!$this->rootBlockView) {
                $this->rootBlockView = $view;
            }

            $this->dataByBlock[$block->getId()] = [
                'id' => $block->getId(),
                'type' => $block->getTypeName(),
                'visible' => $view->vars['visible'],
                'view_vars' => $this->prepareOptions($view->vars),
                'block_prefixes' => $view->vars['block_prefixes'],
            ];
        }
    }

    /**
     * Prepare options for twig rendering
     *
     * @param array $options
     *
     * @return array
     */
    private function prepareOptions(array $options)
    {
        $result = [];
        foreach ($options as $key => $value) {
            if (in_array($key, $this->excludedOptions, true)) {
                continue;
            }

            $result[$key] = $this->prepareOptionValue($value);
        }

        return $result;
    }

    /**
     * @param $optionValue
     *
     * @return string
     */
    private function prepareOptionValue($optionValue)
    {
        if ($optionValue instanceof ParsedExpression) {
            return $optionValue;
        }
        if (is_string($optionValue)) {
            return $optionValue;
        }

        return $this->cloneVar($optionValue);
    }

    /**
     * Build final block tree with options and vars
     */
    private function buildFinalBlockTree()
    {
        if ($this->rootBlockView) {
            $id = $this->rootBlockView->vars['id'];

            $this->data['views'][$id] = $this->getDataByBlock($id);

            $this->recursiveBuildFinalBlockTree($this->rootBlockView, $this->data['views'][$id]);
        }
    }

    /**
     * Add child BlockView-s with options and vars to parent BlockView recursively
     */
    private function recursiveBuildFinalBlockTree(BlockView $blockView, &$output)
    {
        $output['children'] = [];

        foreach ($blockView->children as $child) {
            if (array_key_exists('id', $child->vars)) {
                $id = $child->vars['id'];
                $output['children'][$id] = $this->getDataByBlock($id);

                $this->recursiveBuildFinalBlockTree($child, $output['children'][$id]);
            }
        }
    }

    /**
     * @param $blockId
     *
     * @return array|mixed
     */
    private function getDataByBlock($blockId)
    {
        if (array_key_exists($blockId, $this->dataByBlock)) {
            $data = $this->dataByBlock[$blockId];
        } else {
            $data = ['id' => $blockId, 'visible' => false];
        }

        return $data;
    }

    private function collectContextItems(ContextInterface $context)
    {
        $class = new \ReflectionClass(LayoutContext::class);
        $property = $class->getProperty('items');
        $property->setAccessible(true);

        $items = $property->getValue($context);
        foreach ($items as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            } elseif ($value instanceof ContextItemInterface) {
                $className = (new \ReflectionClass($value))->getShortName();
                $value = sprintf('(%s) %s::%s', gettype($value), $className, $value->toString());
            }

            $this->data['context']['items'][$key] = is_string($value) ? $value : $this->cloneVar($value);
        }
    }

    private function collectContextData(ContextInterface $context)
    {
        $class = new \ReflectionClass(ContextDataCollection::class);
        $property = $class->getProperty('items');
        $property->setAccessible(true);

        foreach ($property->getValue($context->data()) as $key => $value) {
            if (is_object($value)) {
                $value = get_class($value);
            }
            $this->data['context']['data'][$key] = $this->cloneVar($value);
        }
    }

    /**
     * @return bool
     */
    protected function isDebugDeveloperToolbar()
    {
        if (null === $this->debugDeveloperToolbar) {
            $this->debugDeveloperToolbar = (bool)$this->configManager->get('oro_layout.debug_developer_toolbar');
        }

        return $this->debugDeveloperToolbar;
    }

    /**
     * @param array $notAppliedActions
     * @return $this
     */
    public function setNotAppliedActions(array $notAppliedActions)
    {
        foreach ($notAppliedActions as &$action) {
            if (array_key_exists('options', $action)) {
                $action['options'] = $this->prepareOptions($action['options']);
            }
        }
        unset($action);

        $this->notAppliedActions = $notAppliedActions;

        return $this;
    }

    public function reset()
    {
        $this->data = [
            'context' => [
                'items' => [],
                'data' => [],
            ],
            'views' => [],
        ];
    }
}
