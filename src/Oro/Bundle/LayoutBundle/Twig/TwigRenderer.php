<?php

namespace Oro\Bundle\LayoutBundle\Twig;

use Oro\Bundle\LayoutBundle\Cache\Metadata\LayoutCacheMetadata;
use Oro\Bundle\LayoutBundle\Cache\PlaceholderRenderer;
use Oro\Bundle\LayoutBundle\Cache\RenderCache;
use Oro\Bundle\LayoutBundle\Form\TwigRendererEngineInterface;
use Oro\Bundle\LayoutBundle\Form\TwigRendererInterface;
use Oro\Component\Layout\LayoutContextStack;
use Oro\Component\Layout\Renderer;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\Form\FormView;
use Twig\Environment;

/**
 * Heavily inspired by TwigRenderer class.
 * Layout blocks caching is provided.
 *
 * @see \Symfony\Component\Form\FormRenderer
 */
class TwigRenderer extends Renderer implements TwigRendererInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Stores TWIG renderer engine in a clear state.
     */
    private TwigRendererEngineInterface $twigRendererEngine;

    private LayoutContextStack $layoutContextStack;

    private RenderCache $renderCache;

    private PlaceholderRenderer $placeholderRenderer;

    private Environment $environment;

    protected array $blockHierarchy = [];

    /**
     * Used to determine when we need to render a placeholder.
     */
    protected int $cachedBlockNestingLevel = 0;

    protected array $blockHierarchyByEnv = [];

    protected array $blockNameHierarchyMapByEnv = [];

    protected array $hierarchyLevelMapByEnv = [];

    protected array $variableStackByEnv = [];

    protected array $engineByEnv = [];

    public function __construct(
        TwigRendererEngineInterface $engine,
        LayoutContextStack $layoutContextStack,
        RenderCache $renderCache,
        PlaceholderRenderer $placeholderRenderer,
        Environment $environment
    ) {
        $this->twigRendererEngine = $engine;
        $this->layoutContextStack = $layoutContextStack;
        $this->renderCache = $renderCache;
        $this->placeholderRenderer = $placeholderRenderer;
        $this->environment = $environment;

        $this->logger = new NullLogger();

        parent::__construct(clone $engine);

        $this->setEnvironment($environment);
    }

    /**
     * {@inheritdoc}
     *
     * Switches from the locally cached data and TWIG renderer engine associated with current environment to the
     * corresponding data and TWIG renderer engine of the new $environment.
     */
    public function setEnvironment(Environment $environment)
    {
        // Stores current local cache and TWIG renderer engine.
        $currentEnv = spl_object_hash($this->environment);
        $this->blockHierarchyByEnv[$currentEnv] = $this->blockHierarchy;
        $this->blockNameHierarchyMapByEnv[$currentEnv] = $this->blockNameHierarchyMap;
        $this->hierarchyLevelMapByEnv[$currentEnv] = $this->hierarchyLevelMap;
        $this->variableStackByEnv[$currentEnv] = $this->variableStack;
        $this->engineByEnv[$currentEnv] = $this->engine;

        // Switches to the new local cache and TWIG renderer engine.
        $newEnv = spl_object_hash($environment);
        $this->blockHierarchy = $this->blockHierarchyByEnv[$newEnv] ?? [];
        $this->blockNameHierarchyMap = $this->blockNameHierarchyMapByEnv[$newEnv] ?? [];
        $this->hierarchyLevelMap = $this->hierarchyLevelMapByEnv[$newEnv] ?? [];
        $this->variableStack = $this->variableStackByEnv[$newEnv] ?? [];
        $this->engine = $this->engineByEnv[$newEnv] ?? clone $this->twigRendererEngine;
        $this->engine->setEnvironment($environment);

        $this->environment = $environment;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function searchAndRenderBlock(
        FormView $view,
        $blockNameSuffix,
        array $variables = [],
        $renderParentBlock = false
    ) {
        $context = $this->layoutContextStack->getCurrentContext();
        $metadata = $context ? $this->renderCache->getMetadata($view, $context) : null;
        $blockId = $view->vars['id'];

        $isCacheable = $metadata && $this->renderCache->isEnabled();
        if (!$isCacheable) {
            return parent::searchAndRenderBlock($view, $blockNameSuffix, $variables, $renderParentBlock);
        }

        if (!isset($this->blockHierarchy[$blockId]) || $this->blockHierarchy[$blockId] === 0) {
            // INITIAL CALL
            $this->blockHierarchy[$blockId] = 0;
            $this->cachedBlockNestingLevel++;
            $item = $this->renderCache->getItem($view, $context);

            if ($item->isHit()) {
                $html = $item->get();
                $this->logger->debug('Loaded HTML from cache for block "{id}"', ['id' => $blockId]);

                return $this->handlePlaceholders($blockId, $html);
            }
        }
        $this->blockHierarchy[$blockId]++;

        $html = parent::searchAndRenderBlock($view, $blockNameSuffix, $variables, $renderParentBlock);

        if (isset($this->blockHierarchy[$blockId])) {
            $this->blockHierarchy[$blockId]--;
        }
        if ($this->blockHierarchy[$blockId] === 0) {
            // INITIAL CALL
            unset($this->blockHierarchy[$blockId]);
            $this->saveCacheItem($item, $html, $metadata);

            $html = $this->handlePlaceholders($blockId, $html);
        }

        return $html;
    }

    private function saveCacheItem(CacheItem $item, string $html, LayoutCacheMetadata $metadata): void
    {
        if (0 !== $metadata->getMaxAge()) {
            $item->set($html);
            if (null !== $metadata->getMaxAge()) {
                $item->expiresAfter($metadata->getMaxAge());
            }

            if (!empty($metadata->getTags())) {
                $item->tag($metadata->getTags());
            }

            $this->renderCache->save($item);
        }
    }

    private function handlePlaceholders(string $blockId, string $html): string
    {
        $this->cachedBlockNestingLevel--;

        // Create a placeholder only if it's a nested cached block
        if ($this->cachedBlockNestingLevel > 0) {
            $this->logger->debug(
                'Created a placeholder for block "{id}"',
                ['id' => $blockId, 'cachedBlockNestingLevel' => $this->cachedBlockNestingLevel]
            );

            return $this->placeholderRenderer->createPlaceholder($blockId, $html);
        }

        $this->logger->debug('Rendered placeholders in block "{id}"', ['id' => $blockId]);

        return $this->placeholderRenderer->renderPlaceholders($html);
    }
}
