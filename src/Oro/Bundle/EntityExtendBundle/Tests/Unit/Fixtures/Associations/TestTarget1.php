<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\Associations;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="test_target1")
 */
class TestTarget1
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $firstName;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $lastName;

    /**
     * @ORM\Column(type="integer")
     */
    protected $age;
}
