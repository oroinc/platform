<?php

namespace Oro\Bundle\ReportBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\ReportBundle\Entity\Repository\CalendarDateRepository;

/**
 * Entity class that represents calendar_date table
 */
#[ORM\Entity(repositoryClass: CalendarDateRepository::class)]
#[ORM\Table('oro_calendar_date')]
#[ORM\UniqueConstraint(name: 'oro_calendar_date_date_unique_idx', columns: ['date'])]
#[Config(mode: 'hidden')]
class CalendarDate
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    protected ?\DateTimeInterface $date = null;

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
