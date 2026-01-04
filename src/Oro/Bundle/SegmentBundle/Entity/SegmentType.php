<?php

namespace Oro\Bundle\SegmentBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
* Entity that represents Segment Type
*
*/
#[ORM\Entity]
#[ORM\Table(name: 'oro_segment_type')]
class SegmentType
{
    public const TYPE_DYNAMIC = 'dynamic';
    public const TYPE_STATIC  = 'static';

    #[ORM\Column(name: 'name', type: Types::STRING, length: 32)]
    #[ORM\Id]
    protected ?string $name = null;

    #[ORM\Column(name: 'label', type: Types::STRING, length: 255, unique: true)]
    protected ?string $label = null;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Get type name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get a label which may be used to localize segment type
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set a label which may be used to localize segment type
     *
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        return (string)$this->label;
    }
}
