<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Content\Stub;

use Doctrine\Common\Collections\ArrayCollection;

class PersistentCollectionStub extends ArrayCollection
{
    /**
     * Emulate PersistentCollection behavior
     *
     * @return array
     */
    public function unwrap()
    {
        return $this->toArray();
    }
}
