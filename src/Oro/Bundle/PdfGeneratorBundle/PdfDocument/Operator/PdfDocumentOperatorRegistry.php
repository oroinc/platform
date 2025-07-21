<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\PdfDocument\Operator;

use Psr\Container\ContainerInterface;

/**
 * Contains PDF document operators differentiated by entity class and PDF generation mode.
 */
class PdfDocumentOperatorRegistry
{
    public const string DEFAULT = 'default';

    public function __construct(
        private readonly ContainerInterface $pdfDocumentOperatorLocator
    ) {
    }

    /**
     * Retrieves the PDF document operator for the specified entity class and PDF generation mode.
     * Falls back to the default operator if no specific operator is found.
     *
     * @param string $entityClass The entity class for which to retrieve the PDF document operator.
     * @param string $pdfGenerationMode The PDF document generation mode, {@see PdfDocumentGenerationMode}.
     *
     * @throws \LogicException
     */
    public function getOperator(string $entityClass, string $pdfGenerationMode): PdfDocumentOperatorInterface
    {
        $pdfDocumentOperatorByModeLocator = $this->pdfDocumentOperatorLocator->has($entityClass)
            ? $this->pdfDocumentOperatorLocator->get($entityClass)
            : $this->pdfDocumentOperatorLocator->get(self::DEFAULT);

        if (!$pdfDocumentOperatorByModeLocator->has($pdfGenerationMode)) {
            throw new \LogicException(
                sprintf(
                    'No PDF document operator found for entity class "%s" and PDF generation mode "%s".',
                    $entityClass,
                    $pdfGenerationMode
                )
            );
        }

        return $pdfDocumentOperatorByModeLocator->get($pdfGenerationMode);
    }

    /**
     * Checks if a PDF document operator exists for the specified entity class and PDF generation mode.
     *
     * @param string $entityClass The entity class to check.
     * @param string $pdfGenerationMode The PDF document generation mode, {@see PdfDocumentGenerationMode}.
     *
     * @return bool True if the operator exists, false otherwise.
     */
    public function hasOperator(string $entityClass, string $pdfGenerationMode): bool
    {
        if (!$this->pdfDocumentOperatorLocator->has($entityClass)) {
            return false;
        }

        $pdfDocumentOperatorByModeLocator = $this->pdfDocumentOperatorLocator->get($entityClass);

        return $pdfDocumentOperatorByModeLocator->has($pdfGenerationMode);
    }
}
