<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Fixture;

class TestClassForPropertyAccessor
{
    private $privateProp;
    protected $protectedProp;
    public $publicProp;

    public function setPrivateProp($privateProp)
    {
        $this->privateProp = $privateProp;
    }

    public function getPrivateProp()
    {
        return $this->privateProp;
    }

    public function setProtectedProp($protectedProp)
    {
        $this->protectedProp = $protectedProp;
    }

    public function getProtectedProp()
    {
        return $this->protectedProp;
    }
}
