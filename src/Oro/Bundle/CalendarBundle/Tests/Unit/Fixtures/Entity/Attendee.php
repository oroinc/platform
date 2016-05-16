<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity;

use Oro\Bundle\CalendarBundle\Entity\Attendee as BaseAttendee;

/**
 * Contains auto generated methods
 */
class Attendee extends BaseAttendee
{
    protected $status;
    protected $origin;

    public function __construct($id = null)
    {
        parent::__construct();
        $this->id = $id;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setOrigin($origin)
    {
        $this->origin = $origin;
    }

    public function getOrigin()
    {
        return $this->origin;
    }
}
