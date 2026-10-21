<?php

declare(strict_types=1);

namespace Oro\Bundle\ConfigBundle\Formatter;

use Oro\Bundle\ConfigBundle\Config\ConfigBag;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Formats a value of a system configuration setting as a display text,
 * using the definition of the setting from the system configuration.
 */
class SystemConfigValueFormatter
{
    public function __construct(
        private readonly ConfigBag $configBag,
        private readonly TranslatorInterface $translator
    ) {
    }

    public function format(string $key, mixed $value): string
    {
        $definition = $this->configBag->getFieldsRoot($key);
        if (false === $definition || null === $value || '' === $value || [] === $value) {
            return $this->translator->trans('N/A');
        }

        if (\is_array($value)) {
            $items = [];
            foreach ($value as $item) {
                $items[] = $this->format($key, $item);
            }

            return implode(', ', $items);
        }

        return $this->formatScalarValue($definition, $value);
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function formatScalarValue(array $definition, mixed $value): string
    {
        if (!\is_scalar($value)) {
            return $this->translator->trans('N/A');
        }

        if ('boolean' === ($definition['data_type'] ?? null)) {
            return $this->translator->trans($value ? 'Yes' : 'No');
        }

        $choiceLabel = array_search($value, $definition['options']['choices'] ?? [], true);
        if (false !== $choiceLabel) {
            return $this->translator->trans((string)$choiceLabel);
        }

        return (string)$value;
    }
}
