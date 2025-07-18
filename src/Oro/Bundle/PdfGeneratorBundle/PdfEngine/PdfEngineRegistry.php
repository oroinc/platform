<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfEngine;

use Oro\Bundle\PdfGeneratorBundle\Exception\PdfGeneratorException;
use Psr\Container\ContainerInterface;

/**
 * Registry of available PDF engines.
 */
class PdfEngineRegistry
{
    public function __construct(private ContainerInterface $pdfEngineLocator)
    {
    }

    /**
     * @throws PdfGeneratorException
     */
    public function getPdfEngine(string $engineName): PdfEngineInterface
    {
        try {
            return $this->pdfEngineLocator->get($engineName);
        } catch (\Throwable $throwable) {
            throw new PdfGeneratorException(
                sprintf('PDF engine "%s" is not found: %s', $engineName, $throwable->getMessage()),
                $throwable->getCode(),
                $throwable
            );
        }
    }
}
