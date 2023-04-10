<?php

namespace Oro\Bundle\ReportBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * Entity class that represents calendar_date table
 *
 * @ORM\Entity(repositoryClass="Oro\Bundle\ReportBundle\Entity\Repository\CalendarDateRepository")
 * @ORM\Table("oro_calendar_date", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="oro_calendar_date_date_unique_idx", columns={"date"})
 * })
 * @Config(mode="hidden")
 */
class CalendarDate
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="date")
     */
    protected $date;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param $date
     * @return $this
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }
}
