<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Metadata\Factory\Fixture;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class NotConfigurableEntity
{
    /**
     * @var int
     * @ORM\Column(type="integer")
     * @ORM\Id
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @var string
     */
    private $label;
}
