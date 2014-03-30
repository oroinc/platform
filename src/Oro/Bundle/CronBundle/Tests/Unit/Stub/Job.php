<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Stub;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Job
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string")
     */
    protected $id;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $closedAt;

    /**
     * @ORM\Column(type="string")
     */
    protected $state;


    public function __construct()
    {
    }
}
