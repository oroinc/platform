<?php

namespace Oro\Bundle\DigitalAssetBundle\Tests\Unit\Stub;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;

class DigitalAssetStub extends DigitalAsset
{
    /** @var Collection */
    public $childFiles;

    /**
     * @return Collection
     */
    public function getChildFiles()
    {
        return $this->childFiles;
    }
}
