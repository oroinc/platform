<?php

namespace Oro\Bundle\ReportBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
* Entity that represents Report Type
*
*/
#[ORM\Entity]
#[ORM\Table(name: 'oro_report_type')]
class ReportType
{
    const TYPE_TABLE = 'TABLE';

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
     * Get a label which may be used to localize report type
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set a label which may be used to localize report type
     *
     * @param string $label
     * @return ReportType
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        return (string) $this->label;
    }
}
