<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfOptions;

use Oro\Bundle\PdfGeneratorBundle\Exception\PdfOptionsException;

/**
 * Stores a collection of options that control PDF generation process in a PDF engine.
 */
interface PdfOptionsInterface extends \ArrayAccess
{
    /**
     * @return string|null PDF options preset, allows to differentiate PDF options.
     */
    public function getPreset(): ?string;

    /**
     * Resolves the collected options.
     * Makes the PDF options object immutable.
     *
     * @throws PdfOptionsException
     */
    public function resolve(): self;

    /**
     * @return bool True if the PDF options object is resolved and immutable.
     */
    public function isResolved(): bool;

    /**
     * @param string $option Name of the option to check.
     *
     * @return bool True if option is known.
     */
    public function isDefined(string $option): bool;

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array;
}
