<?php

namespace Oro\Bundle\DigitalAssetBundle\Tests\Unit\Stub\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class EntityWithMultiFile
{
    /** @var Collection */
    public $multiFileField;

    public function __construct()
    {
        $this->multiFileField = new ArrayCollection();
    }
}
