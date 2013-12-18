<?php

namespace Oro\Bundle\ReportBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * @ORM\Entity
 * @ORM\Table(name="oro_report_type")
 * @Config(
 *  defaultValues={
 *      "entity"={"label"="Report Type", "plural_label"="Report Types"}
 *  }
 * )
 */
class ReportType
{
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=32)
     * @ORM\Id
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=255, unique=true)
     */
    protected $label;

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
    public function __toString()
    {
        return (string) $this->label;
    }
}
