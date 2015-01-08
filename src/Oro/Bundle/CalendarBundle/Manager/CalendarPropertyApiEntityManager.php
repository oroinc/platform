<?php

namespace Oro\Bundle\CalendarBundle\Manager;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;

class CalendarPropertyApiEntityManager extends ApiEntityManager
{
    /** @var CalendarManager */
    protected $calendarManager;

    /**
     * @param string          $class
     * @param ObjectManager   $om
     * @param CalendarManager $calendarManager
     */
    public function __construct($class, ObjectManager $om, CalendarManager $calendarManager)
    {
        parent::__construct($class, $om);
        $this->calendarManager = $calendarManager;
    }

    /**
     * @return CalendarManager
     */
    public function getCalendarManager()
    {
        return $this->calendarManager;
    }
}
