<?php

namespace Oro\Bundle\LayoutBundle\Cache;

use Oro\Bundle\LayoutBundle\Layout\LayoutManager;
use Oro\Component\Layout\LayoutContextStack;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Renderer for cache placeholders used for post-cache substitution.
 */
class PlaceholderRenderer implements ResetInterface
{
    private LayoutManager $layoutManager;
    
    private LayoutContextStack $layoutContextStack;
    
    private LoggerInterface $logger;
    
    private array $renderedPlaceholders = [];

    public function __construct(
        LayoutManager $layoutManager,
        LayoutContextStack $layoutContextStack,
        LoggerInterface $logger
    ) {
        $this->layoutManager = $layoutManager;
        $this->layoutContextStack = $layoutContextStack;
        $this->logger = $logger;
    }

    public function createPlaceholder(string $blockId, string $html): string
    {
        $this->renderedPlaceholders[$blockId] = $html;

        return $this->getPlaceholder($blockId);
    }

    private function getPlaceholder(string $blockId): string
    {
        return '<!-- PLACEHOLDER '.$blockId.' -->';
    }

    public function renderPlaceholders(string $html): string
    {
        $blockIds = $this->getPlaceholderBlockIds($html);

        if (!$blockIds) {
            return $html;
        }

        $placeholders = [];
        foreach ($blockIds as $blockId) {
            $blockHtml = $this->renderPlaceholderContent($blockId);
            $placeholder = $this->getPlaceholder($blockId);
            $placeholders[$placeholder] = $blockHtml;
        }
        $this->logger->debug('Rendered placeholders', ['ids' => $blockIds]);

        return strtr($html, $placeholders);
    }

    /**
     * @param string $html
     * @return string[]
     */
    private function getPlaceholderBlockIds(string $html): array
    {
        preg_match_all('/<\!--\ PLACEHOLDER\ ([a-z][a-z0-9\_\-\:]+)\ -->/i', $html, $matches);

        return $matches[1];
    }

    private function renderPlaceholderContent(string $blockId): string
    {
        if (isset($this->renderedPlaceholders[$blockId])) {
            $html = $this->renderedPlaceholders[$blockId];
            // handle nested placeholders
            return $this->renderPlaceholders($html);
        }

        $context = $this->layoutContextStack->getCurrentContext();
        if (!$context) {
            return '';
        }

        return $this->layoutManager->getLayout($context, $blockId)->render();
    }

    public function reset()
    {
        $this->renderedPlaceholders = [];
    }
}
