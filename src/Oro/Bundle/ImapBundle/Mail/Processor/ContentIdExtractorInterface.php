<?php

namespace Oro\Bundle\ImapBundle\Mail\Processor;

use Zend\Mail\Storage\Part\PartInterface;

interface ContentIdExtractorInterface
{
    public function extract(PartInterface $multipart);
}
