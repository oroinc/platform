<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\EventListener\Stub;

use Doctrine\Common\Collections\ArrayCollection;

class ParentEntity
{
    protected $children;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }
}
