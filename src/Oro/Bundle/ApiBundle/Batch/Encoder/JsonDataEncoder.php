<?php

namespace Oro\Bundle\ApiBundle\Batch\Encoder;

use Oro\Bundle\ApiBundle\Batch\JsonUtil;

/**
 * Encodes a list of items into a JSON string.
 */
class JsonDataEncoder implements DataEncoderInterface
{
    private ?string $headerSectionName = null;

    /**
     * {@inheritDoc}
     */
    public function encodeItems(array $items): string
    {
        $resultItems = [];
        if ($items) {
            $headerSectionData = null;
            if (null !== $this->headerSectionName) {
                $firstItem = reset($items);
                if (\array_key_exists($this->headerSectionName, $firstItem)) {
                    $headerSectionData = [$this->headerSectionName => $firstItem[$this->headerSectionName]];
                }
            }

            foreach ($items as $firstLevelSections) {
                if (null !== $headerSectionData) {
                    unset($firstLevelSections[$this->headerSectionName]);
                }
                foreach ($firstLevelSections as $sectionName => $item) {
                    $resultItems[$sectionName][] = $item;
                }
            }
            if (null !== $headerSectionData) {
                $resultItems = array_merge($headerSectionData, $resultItems);
            }
        }

        return JsonUtil::encode($resultItems);
    }

    /**
     * {@inheritdoc}
     */
    public function setHeaderSectionName(?string $name): void
    {
        $this->headerSectionName = $name;
    }
}
