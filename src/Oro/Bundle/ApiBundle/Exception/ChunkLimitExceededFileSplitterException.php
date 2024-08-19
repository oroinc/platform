<?php

namespace Oro\Bundle\ApiBundle\Exception;

/**
 * This exception is thrown if the limit for the maximum number of chunks for a split operation exceeded.
 */
class ChunkLimitExceededFileSplitterException extends \RuntimeException
{
    private ?string $sectionName;

    public function __construct(?string $sectionName)
    {
        parent::__construct(
            $sectionName
                ? sprintf('The limit for the maximum number of chunks exceeded for the section "%s".', $sectionName)
                : 'The limit for the maximum number of chunks exceeded.'
        );
        $this->sectionName = $sectionName;
    }

    public function getSectionName(): ?string
    {
        return $this->sectionName;
    }
}
