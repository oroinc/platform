<?php

namespace Oro\Bundle\SecurityBundle\Provider;

/**
 * Provide info required by the Permissions-Policy header.
 */
class PermissionsPolicyHeaderProvider
{
    private bool $enabled;
    private array $directives;

    public function __construct(
        bool $enabled,
        array $directives
    ) {
        $this->enabled = $enabled;
        $this->directives = $directives;
    }

    public function setDirective(string $directive, array $value): void
    {
        $this->directives[$directive] = $value;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getDirectivesValue(): string
    {
        $result = [];
        foreach ($this->directives as $directive => $value) {
            $result[] = sprintf('%s=%s', $directive, $this->normalizeValue($value));
        }

        return implode(', ', $result);
    }

    private function normalizeValue(array $value): string
    {
        $value = array_unique($value);

        if (['allow_all'] === $value) {
            return '*';
        }

        if (['deny'] === $value) {
            return '()';
        }

        $value = array_map(fn ($val) => $val === 'allow_self' ? 'self' : sprintf('"%s"', $val), $value);

        return '(' . implode(' ', $value) . ')';
    }
}
