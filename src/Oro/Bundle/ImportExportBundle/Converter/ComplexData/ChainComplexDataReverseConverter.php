<?php

namespace Oro\Bundle\ImportExportBundle\Converter\ComplexData;

/**
 * Delegates data convertation to child converters.
 */
class ChainComplexDataReverseConverter implements ComplexDataReverseConverterInterface
{
    public function __construct(
        private readonly iterable $converters
    ) {
    }

    #[\Override]
    public function reverseConvert(array $data, object $sourceEntity): array
    {
        /** @var ComplexDataReverseConverterInterface $converter */
        foreach ($this->converters as $converter) {
            $data = $converter->reverseConvert($data, $sourceEntity);
        }

        return $data;
    }
}
