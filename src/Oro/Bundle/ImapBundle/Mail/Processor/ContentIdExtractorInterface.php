<?php

namespace Oro\Bundle\ImapBundle\Mail\Processor;

use Zend\Mail\Storage\Part\PartInterface;

interface ContentIdExtractorInterface
{
    /**
     * @param PartInterface $multipart
     *
     * @return array
     */
    public function extract(PartInterface $multipart);
}
