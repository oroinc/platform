<?php

namespace Oro\Bundle\DataAuditBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
* DateTimeFieldType trait
*
*/
trait DateTimeFieldType
{
    #[ORM\Column(name: 'old_date', type: Types::DATE_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $oldDate = null;

    #[ORM\Column(name: 'old_time', type: Types::TIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $oldTime = null;

    #[ORM\Column(name: 'old_datetime', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $oldDatetime = null;

    /**
     * @var \DateTimeInterface
     */
    #[ORM\Column(name: 'old_datetimetz', type: Types::DATETIMETZ_MUTABLE, nullable: true)]
    protected $oldDatetimetz;

    #[ORM\Column(name: 'new_date', type: Types::DATE_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $newDate = null;

    #[ORM\Column(name: 'new_time', type: Types::TIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $newTime = null;

    #[ORM\Column(name: 'new_datetime', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $newDatetime = null;

    /**
     * @var \DateTimeInterface
     */
    #[ORM\Column(name: 'new_datetimetz', type: Types::DATETIMETZ_MUTABLE, nullable: true)]
    protected $newDatetimetz;
}
