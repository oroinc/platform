<?php

namespace Oro\Bundle\ApiBundle\Batch\Encoder;

/**
 * The interface for classes that are responsible to encode a list of items into a string.
 */
interface DataEncoderInterface
{
    /**
     * Encodes the given items into a string.
     */
    public function encodeItems(array $items): string;

    /**
     * Sets the name of a header section.
     */
    public function setHeaderSectionName(?string $name): void;
}
