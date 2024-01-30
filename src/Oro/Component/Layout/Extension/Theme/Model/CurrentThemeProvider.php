<?php

namespace Oro\Component\Layout\Extension\Theme\Model;

use Oro\Component\Layout\Exception\NotRequestContextRuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Resolve current theme id from request object. Can be emulated in non-request context
 */
class CurrentThemeProvider
{
    private ?Request $currentRequest = null;
    private ?string $emulatedCurrentThemeId = null;

    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function setCurrentRequest(?Request $currentRequest): void
    {
        $this->currentRequest = $currentRequest;
    }

    public function getCurrentRequest(): ?Request
    {
        return $this->currentRequest;
    }

    public function getCurrentThemeId(): ?string
    {
        if ($this->emulatedCurrentThemeId) {
            return $this->emulatedCurrentThemeId;
        }
        if ($this->currentRequest) {
            return $this->currentRequest->attributes->get('_theme');
        }

        if (!$this->requestStack->getMainRequest()) {
            throw new NotRequestContextRuntimeException(
                'Request context is required to get the current theme, none found.'
            );
        }

        $themeId = $this->requestStack->getMainRequest()?->attributes->get('_theme');

        return $themeId ?? $this->requestStack->getCurrentRequest()?->attributes->get('_theme');
    }

    public function emulateThemeId(string $themeId): void
    {
        $this->emulatedCurrentThemeId = $themeId;
    }

    public function disableEmulation(): void
    {
        $this->emulatedCurrentThemeId = null;
    }
}
