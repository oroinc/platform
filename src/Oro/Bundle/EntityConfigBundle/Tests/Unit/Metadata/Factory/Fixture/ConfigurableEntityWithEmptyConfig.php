<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Metadata\Factory\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

/**
 * @ORM\Entity
 * @Config
 */
class ConfigurableEntityWithEmptyConfig
{
    /**
     * @var int
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ConfigField
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     * @ConfigField
     */
    private $name;

    /**
     * @var string
     */
    private $label;
}
