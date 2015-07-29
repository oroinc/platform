<?php

namespace Oro\Bundle\DataAuditBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

trait DateTimeFieldType
{
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="old_date", type="date", nullable=true)
     */
    protected $oldDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="old_time", type="time", nullable=true)
     */
    protected $oldTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="old_datetime", type="datetime", nullable=true)
     */
    protected $oldDatetime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="old_datetimetz", type="datetimetz", nullable=true)
     */
    protected $oldDatetimetz;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="new_date", type="date", nullable=true)
     */
    protected $newDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="new_time", type="time", nullable=true)
     */
    protected $newTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="new_datetime", type="datetime", nullable=true)
     */
    protected $newDatetime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="new_datetimetz", type="datetimetz", nullable=true)
     */
    protected $newDatetimetz;
}
