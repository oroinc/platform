<?php

namespace Oro\Bundle\LayoutBundle\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LayoutBundle\Layout\LayoutContextHolder;

use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\ContextDataCollection;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\ContextItemInterface;
use Oro\Component\Layout\LayoutContext;

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
    private $dataByBlock;

    /** @var BlockView */
    private $rootBlockView;

    /** @var array */
    private $excludedOptions = [
        'block',
        'blocks',
        'block_type',
        'attr'
    ];

    /**
     * @param LayoutContextHolder $contextHolder
     * @param ConfigManager $configManager
     * @param bool $isDebug
     */
    public function __construct(LayoutContextHolder $contextHolder, ConfigManager $configManager, $isDebug = false)
    {
        $this->contextHolder = $contextHolder;
        $this->configManager = $configManager;
        $this->isDebug = $isDebug;

        $this->data = [
            'context' => [
                'items' => [],
                'data' => []
            ],
            'views' => []
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
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
    }

    /**
     * {@inheritdoc}
     */
    public function collectBuildBlockOptions($blockId, $blockType, array $options)
    {
        if ($this->isDebug && $this->configManager->get('oro_layout.debug_developer_toolbar')) {
            $this->dataByBlock[$blockId] = [
                'id' => $blockId,
                'type' => $blockType,
                'build_block_options' => $this->prepareOptions($options)
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function collectBuildViewOptions(BlockInterface $block, $blockTypeClass, array $options)
    {
        if ($this->isDebug && $this->configManager->get('oro_layout.debug_developer_toolbar')) {
            $this->dataByBlock[$block->getId()]['type_class'] = $blockTypeClass;
            $this->dataByBlock[$block->getId()]['build_view_options'] = $this->prepareOptions($options);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function collectFinishViewOptions(BlockInterface $block, array $options)
    {
        if ($this->isDebug && $this->configManager->get('oro_layout.debug_developer_toolbar')) {
            $this->dataByBlock[$block->getId()]['finish_view_options'] = $this->prepareOptions($options);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function collectBlockTree(BlockInterface $block, BlockView $view)
    {
        if ($this->isDebug && $this->configManager->get('oro_layout.debug_developer_toolbar')) {
            if (!$this->rootBlockView) {
                $this->rootBlockView = $view;
            }

            $this->dataByBlock[$block->getId()]['visible'] = $view->vars['visible'];
            $this->dataByBlock[$block->getId()]['view_vars'] = $this->prepareOptions($view->vars);
        }
    }

    /**
     * @param array $options
     *
     * @return array
     */
    private function prepareOptions(array $options)
    {
        $result = [];
        foreach ($options as $key => $value) {
            if (in_array($key, $this->excludedOptions)) {
                continue;
            }

            if (is_object($value)) {
                $result[$key] = get_class($value);
            } elseif (is_array($value)) {
                $result[$key] = json_encode($value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    private function buildFinalBlockTree()
    {
        if ($this->rootBlockView) {
            $id = $this->rootBlockView->vars['id'];

            $this->data['views'][$id] = $this->getDataByBlock($id);

            $this->recursiveBuildFinalBlockTree($this->rootBlockView, $this->data['views'][$id]);
        }
    }

    /**
     * @param BlockView $blockView
     * @param $output
     */
    private function recursiveBuildFinalBlockTree(BlockView $blockView, &$output)
    {
        $output['children'] = [];

        foreach ($blockView->children as $child) {
            $id = $child->vars['id'];
            $output['children'][$id] = $this->getDataByBlock($id);

            $this->recursiveBuildFinalBlockTree($child, $output['children'][$id]);
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

    /**
     * @param ContextInterface $context
     */
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
                $value = $value->toString();
            }

            $this->data['context']['items'][$key] = $value;
        }
    }

    /**
     * @param ContextInterface $context
     */
    private function collectContextData(ContextInterface $context)
    {
        $class = new \ReflectionClass(ContextDataCollection::class);
        $property = $class->getProperty('items');
        $property->setAccessible(true);

        foreach ($property->getValue($context->data()) as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            } elseif (is_object($value)) {
                $value = get_class($value);
            }

            $this->data['context']['data'][$key] = $value;
        }
    }
}
