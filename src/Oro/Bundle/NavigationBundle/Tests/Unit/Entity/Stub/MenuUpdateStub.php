<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateTrait;

class MenuUpdateStub implements MenuUpdateInterface
{
    use MenuUpdateTrait;

    /** @var array */
    protected $extras = [];

    /**
     * MenuUpdateStub constructor.
     */
    public function __construct()
    {
        $this->titles = new ArrayCollection();
    }

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
