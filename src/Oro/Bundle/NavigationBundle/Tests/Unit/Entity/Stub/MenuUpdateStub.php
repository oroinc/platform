<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub;

use Oro\Bundle\NavigationBundle\Entity\AbstractMenuUpdate;

class MenuUpdateStub extends AbstractMenuUpdate
{
    /** @var array */
    protected $extras = [];

    /**
     * {@inheritdoc}
     */
    public function getExtras()
    {
        return $this->extras;
    }

    /**
     * @param array $extras
     *
     * @return MenuUpdateStub
     */
    public function setExtras(array $extras)
    {
        $this->extras = $extras;

        return $this;
    }
}
