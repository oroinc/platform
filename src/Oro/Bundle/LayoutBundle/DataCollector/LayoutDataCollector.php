<?php

namespace Oro\Bundle\LayoutBundle\DataCollector;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LayoutBundle\Event\LayoutBuildAfterEvent;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\ContextAwareInterface;
use Oro\Component\Layout\ContextDataCollection;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\ContextItemInterface;
use Oro\Component\Layout\Extension\Theme\PathProvider\PathProviderInterface;
use Oro\Component\Layout\LayoutContext;
use Symfony\Component\ExpressionLanguage\ParsedExpression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * Data collector for layouts.
 */
class LayoutDataCollector extends DataCollector
{
    private DataCollectorLayoutNameProviderInterface $layoutNameProvider;

    private ConfigManager $configManager;

    private PathProviderInterface $pathProvider;

    private bool $isDebug;

    private array $dataByBlock = [];

    /** @var array<string,BlockView> */
    private array $rootBlockViews = [];

    private array $notAppliedActions = [];

    private array $excludedOptions = [
        'block',
        'blocks',
        'block_type',
        'attr',
    ];

    private ?bool $debugDeveloperToolbar = null;

    /** @var array<string,ContextInterface> */
    private array $contexts = [];

    public function __construct(
        DataCollectorLayoutNameProviderInterface $layoutNameProvider,
        ConfigManager $configManager,
        PathProviderInterface $pathProvider,
        string|int|bool|null $isDebug = false
    ) {
        $this->layoutNameProvider = $layoutNameProvider;
        $this->configManager = $configManager;
        $this->pathProvider = $pathProvider;
        $this->isDebug = (bool)$isDebug;

        $this->reset();
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'layout';
    }

    public function getData(): array
    {
        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Throwable $exception = null): void
    {
        foreach ($this->contexts as $contextHash => $context) {
            $this->collectContextItems($context);
            $this->collectContextData($context);

            $this->buildFinalBlockTree($contextHash);

            $notAppliedActions = $this->notAppliedActions[$contextHash] ?? [];
            $this->data[$contextHash]['not_applied_actions_count'] = count($notAppliedActions);
            $this->data[$contextHash]['not_applied_actions'] = $notAppliedActions;
            $this->data[$contextHash]['count'] = count($this->dataByBlock[$contextHash] ?? []);
            $this->data[$contextHash]['paths'] = $this->getPaths($context);
            $this->data[$contextHash]['name'] = $this->layoutNameProvider->getNameByContext($context);
        }
    }

    private function getPaths(ContextInterface $context): array
    {
        if ($this->pathProvider instanceof ContextAwareInterface) {
            $this->pathProvider->setContext($context);
        }

        return $this->pathProvider->getPaths([]);
    }

    /**
     * Collects view vars for {@see BlockView}s, saves root BlockView, checks if block is visible.
     */
    public function collectBlockView(BlockInterface $block, BlockView $view): void
    {
        if ($this->isDebug && $this->isDebugDeveloperToolbar()) {
            $contextHash = $block->getContext()->getHash();
            if (!isset($this->rootBlockViews[$contextHash])) {
                $this->rootBlockViews[$contextHash] = $view;
            }

            $this->dataByBlock[$contextHash][$block->getId()] = [
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
    private function prepareOptions(array $options): array
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

    private function prepareOptionValue(mixed $optionValue): string
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
    private function buildFinalBlockTree(string $contextHash): void
    {
        $this->data[$contextHash]['views'] = [];
        if (isset($this->rootBlockViews[$contextHash])) {
            $id = $this->rootBlockViews[$contextHash]->vars['id'];

            $this->data[$contextHash]['views'][$id] = $this->getDataByBlock($contextHash, $id);

            $this->recursiveBuildFinalBlockTree(
                $contextHash,
                $this->rootBlockViews[$contextHash],
                $this->data[$contextHash]['views'][$id]
            );
        }
    }

    /**
     * Add child BlockView-s with options and vars to parent BlockView recursively
     */
    private function recursiveBuildFinalBlockTree(string $contextHash, BlockView $blockView, &$output): void
    {
        $output['children'] = [];

        foreach ($blockView->children as $child) {
            if (array_key_exists('id', $child->vars)) {
                $id = $child->vars['id'];
                $output['children'][$id] = $this->getDataByBlock($contextHash, $id);

                $this->recursiveBuildFinalBlockTree($contextHash, $child, $output['children'][$id]);
            }
        }
    }

    private function getDataByBlock(string $contextHash, string $blockId): mixed
    {
        if (isset($this->dataByBlock[$contextHash])
            && array_key_exists($blockId, $this->dataByBlock[$contextHash])) {
            $data = $this->dataByBlock[$contextHash][$blockId];
        } else {
            $data = ['id' => $blockId, 'visible' => false];
        }

        return $data;
    }

    private function collectContextItems(ContextInterface $context): void
    {
        $contextHash = $context->getHash();
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

            $this->data[$contextHash]['context']['items'][$key] = is_string($value) ? $value : $this->cloneVar($value);
        }
    }

    private function collectContextData(ContextInterface $context): void
    {
        $contextHash = $context->getHash();
        $class = new \ReflectionClass(ContextDataCollection::class);
        $property = $class->getProperty('items');
        $property->setAccessible(true);

        foreach ($property->getValue($context->data()) as $key => $value) {
            if (is_object($value)) {
                $value = get_class($value);
            }
            $this->data[$contextHash]['context']['data'][$key] = $this->cloneVar($value);
        }
    }

    protected function isDebugDeveloperToolbar(): bool
    {
        if (null === $this->debugDeveloperToolbar) {
            $this->debugDeveloperToolbar = (bool)$this->configManager->get('oro_layout.debug_developer_toolbar');
        }

        return $this->debugDeveloperToolbar;
    }

    public function onBuildAfter(LayoutBuildAfterEvent $event): void
    {
        $context = $event->getLayout()->getContext();
        $contextHash = $context->getHash();
        $this->contexts[$contextHash] = $context;

        $notAppliedActions = $event->getLayoutBuilder()->getNotAppliedActions();
        foreach ($notAppliedActions as &$action) {
            if (array_key_exists('options', $action)) {
                $action['options'] = $this->prepareOptions($action['options']);
            }
        }
        unset($action);

        $this->notAppliedActions[$contextHash] = $notAppliedActions;
    }

    public function reset(): void
    {
        $this->contexts = [];
        $this->data = [];
    }
}
