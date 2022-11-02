<?php

namespace Oro\Bundle\ImapBundle\Mail\Processor;

use Laminas\Mail\Storage\Part\PartInterface;

/**
 * Interface for an extractor of replacement parts based on content id header from multipart message.
 */
interface ContentIdExtractorInterface
{
    /**
     * @param PartInterface $multipart
     *
     * @return array
     */
    public function extract(PartInterface $multipart);
}
