<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Metadata\Factory\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;

#[ORM\Entity]
#[Config]
class ConfigurableEntityWithEmptyConfig
{
    /**
     * @var int
     */
    #[ConfigField]
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    private $id;

    /**
     * @var string
     */
    #[ConfigField]
    #[ORM\Column(type: 'string', length: 255)]
    private $name;

    /**
     * @var string
     */
    private $label;
}
