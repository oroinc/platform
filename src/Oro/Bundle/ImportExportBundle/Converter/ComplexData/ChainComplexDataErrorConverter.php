<?php

namespace Oro\Bundle\ImportExportBundle\Converter\ComplexData;

/**
 * Delegates data convertation to child converters.
 */
class ChainComplexDataErrorConverter implements ComplexDataErrorConverterInterface
{
    public function __construct(
        private readonly iterable $converters
    ) {
    }

    #[\Override]
    public function convertError(string $error, ?string $propertyPath): string
    {
        /** @var ComplexDataErrorConverterInterface $converter */
        foreach ($this->converters as $converter) {
            $error = $converter->convertError($error, $propertyPath);
        }

        return $error;
    }
}
