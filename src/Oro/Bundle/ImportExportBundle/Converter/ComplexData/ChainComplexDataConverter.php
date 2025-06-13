<?php

namespace Oro\Bundle\ImportExportBundle\Converter\ComplexData;

/**
 * Delegates data convertation to child converters.
 */
class ChainComplexDataConverter implements ComplexDataConverterInterface
{
    public function __construct(
        private readonly iterable $converters
    ) {
    }

    #[\Override]
    public function convert(array $item, mixed $sourceData): array
    {
        /** @var ComplexDataConverterInterface $converter */
        foreach ($this->converters as $converter) {
            $item = $converter->convert($item, $sourceData);
        }

        return $item;
    }
}
