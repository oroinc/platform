<?php

namespace Oro\Bundle\LayoutBundle\Twig;

use Oro\Bundle\LayoutBundle\Cache\Metadata\LayoutCacheMetadata;
use Oro\Bundle\LayoutBundle\Cache\PlaceholderRenderer;
use Oro\Bundle\LayoutBundle\Cache\RenderCache;
use Oro\Bundle\LayoutBundle\Form\TwigRendererEngineInterface;
use Oro\Bundle\LayoutBundle\Form\TwigRendererInterface;
use Oro\Component\Layout\Renderer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\Form\FormView;
use Twig\Environment;

/**
 * Heavily inspired by TwigRenderer class.
 * Layout blocks caching is provided.
 *
 * @see \Symfony\Bridge\Twig\Form\TwigRenderer
 */
class TwigRenderer extends Renderer implements TwigRendererInterface
{
    /**
     * @var array
     */
    private $blockHierarchy = [];

    /**
     * Used to determine when we need to render a placeholder.
     *
     * @var int
     */
    private $cachedBlockNestingLevel = 0;

    /**
     * @var TwigRendererEngineInterface
     */
    protected $engine;

    /**
     * @var RenderCache
     */
    private $renderCache;

    /**
     * @var PlaceholderRenderer
     */
    private $placeholderRenderer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        TwigRendererEngineInterface $engine,
        RenderCache $renderCache,
        PlaceholderRenderer $placeholderRenderer,
        LoggerInterface $logger
    ) {
        $this->renderCache = $renderCache;
        $this->placeholderRenderer = $placeholderRenderer;
        $this->logger = $logger;

        parent::__construct($engine);
    }

    /**
     * {@inheritdoc}
     */
    public function setEnvironment(Environment $environment)
    {
        $this->engine->setEnvironment($environment);

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
        $metadata = $this->renderCache->getMetadata($view);
        $blockId = $view->vars['id'];

        $isCacheable = $metadata && $this->renderCache->isEnabled();
        if (!$isCacheable) {
            return parent::searchAndRenderBlock($view, $blockNameSuffix, $variables, $renderParentBlock);
        }

        if (!isset($this->blockHierarchy[$blockId]) || $this->blockHierarchy[$blockId] === 0) {
            // INITIAL CALL
            $this->blockHierarchy[$blockId] = 0;
            $this->cachedBlockNestingLevel++;
            $item = $this->renderCache->getItem($view);

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
