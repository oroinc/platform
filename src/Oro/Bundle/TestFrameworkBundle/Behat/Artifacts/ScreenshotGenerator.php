<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Artifacts;

use Behat\Mink\Mink;

/**
 * Generates screenshots for Behat tests
 */
class ScreenshotGenerator
{
    /**
     * @param Mink $mink
     * @param ArtifactsHandlerInterface[] $artifactsHandlers
     */
    public function __construct(
        private Mink $mink,
        private array $artifactsHandlers
    ) {
    }

    public function capture(): array
    {
        $urls = [];
        foreach ($this->artifactsHandlers as $artifactsHandler) {
            $urls[] = $artifactsHandler->save($this->mink->getSession()->getScreenshot());
        }

        return $urls;
    }
}
