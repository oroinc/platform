<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfOptions;

use Oro\Bundle\PdfGeneratorBundle\Exception\PdfOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Stores a collection of options that control PDF generation process in a PDF engine.
 * Resolves the collected options via {@see OptionsResolver}.
 * A resolved object becomes immutable.
 */
class PdfOptions implements PdfOptionsInterface
{
    private bool $resolved = false;

    /**
     * @param array<string,mixed> $options
     * @param string|null $preset PDF options preset, allows to differentiate PDF options.
     * @param OptionsResolver $optionsResolver
     */
    public function __construct(
        private array $options,
        private OptionsResolver $optionsResolver,
        private ?string $preset = null
    ) {
    }

    #[\Override]
    public function getPreset(): ?string
    {
        return $this->preset;
    }

    /**
     * {@inheritdoc}
     *
     * Resolves the collected options via {@see OptionsResolver}.
     *
     * @throws PdfOptionsException
     */
    #[\Override]
    public function resolve(): self
    {
        try {
            $this->options = $this->optionsResolver->resolve($this->options);
        } catch (\Throwable $throwable) {
            throw new PdfOptionsException(
                'Failed to resolve PDF options: ' . $throwable->getMessage(),
                $throwable->getCode(),
                $throwable,
                $this
            );
        }

        $this->resolved = true;

        return $this;
    }

    #[\Override]
    public function isResolved(): bool
    {
        return $this->resolved;
    }

    #[\Override]
    public function isDefined(string $option): bool
    {
        return $this->optionsResolver->isDefined($option);
    }

    #[\Override]
    public function toArray(): array
    {
        return $this->options;
    }

    #[\Override]
    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->options);
    }

    #[\Override]
    public function offsetGet(mixed $offset): mixed
    {
        if (!$this->optionsResolver->isDefined($offset)) {
            throw new PdfOptionsException(
                sprintf(
                    'Option %s is not defined. Defined options are: %s',
                    $offset,
                    implode(', ', $this->optionsResolver->getDefinedOptions())
                ),
                0,
                null,
                $this
            );
        }

        return $this->options[$offset] ?? null;
    }

    #[\Override]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($this->resolved) {
            throw new PdfOptionsException('PDF options are already resolved and cannot be changed', 0, null, $this);
        }

        if (!$this->optionsResolver->isDefined($offset)) {
            throw new PdfOptionsException(
                sprintf(
                    'Option %s is not defined. Defined options are: %s',
                    $offset,
                    implode(', ', $this->optionsResolver->getDefinedOptions())
                )
            );
        }

        $this->options[$offset] = $value;
    }

    #[\Override]
    public function offsetUnset(mixed $offset): void
    {
        if ($this->resolved) {
            throw new PdfOptionsException('PDF options are already resolved and cannot be changed', 0, null, $this);
        }

        unset($this->options[$offset]);
    }
}
