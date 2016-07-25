<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class TestClass
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\Column(type="text")
     */
    protected $existing;
    
    public function getExisting()
    {
        return $this->existing;
    }

    public function setExisting($existing)
    {
        $this->existing = $existing;
    }
}
