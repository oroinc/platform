<?php

namespace Oro\Bundle\AttachmentBundle\Tools\Imagine\Binary\Factory;

use Liip\ImagineBundle\Binary\BinaryInterface;

interface ImagineBinaryByFileContentFactoryInterface
{
    /**
     * @param string $content Binary string
     *
     * @return BinaryInterface
     */
    public function createImagineBinary(string $content): BinaryInterface;
}
