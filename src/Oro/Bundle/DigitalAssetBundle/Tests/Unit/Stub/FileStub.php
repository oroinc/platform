<?php

namespace Oro\Bundle\DigitalAssetBundle\Tests\Unit\Stub;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;

class FileStub extends File
{
    /** @var DigitalAsset|null */
    protected $digitalAsset;

    public function getDigitalAsset(): ?DigitalAsset
    {
        return $this->digitalAsset;
    }

    public function setDigitalAsset(?DigitalAsset $digitalAsset): self
    {
        $this->digitalAsset = $digitalAsset;

        return $this;
    }
}
