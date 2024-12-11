<?php

namespace Oro\Bundle\SecurityBundle\Authentication\Passport\Badge;

use Symfony\Component\Security\Http\Authenticator\Passport\Badge\BadgeInterface;

/**
 * Adds automatic CAPTCHA checking capabilities to this authenticator.
 */
class CaptchaBadge implements BadgeInterface
{
    private bool $resolved = false;

    public function __construct(
        private ?string $token
    ) {
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    /**
     * @internal
     */
    public function markResolved(): void
    {
        $this->resolved = true;
    }

    #[\Override]
    public function isResolved(): bool
    {
        return $this->resolved;
    }
}
