<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\Associations;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="test_phone")
 */
class TestPhone
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
    protected $phone;

    /**
     * @ORM\ManyToOne(targetEntity="TestOwner1", inversedBy="phones")
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $owner;
}
