<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Manager;

use Oro\Bundle\CalendarBundle\Form\DataTransformer\UsersToAttendeesTransformer;
use Oro\Bundle\CalendarBundle\Manager\AttendeeManager;
use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\Attendee;
use Oro\Bundle\FormBundle\Autocomplete\ConverterInterface;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;

class AttendeeManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var AttendeeManager */
    protected $attendeeManager;

    /** @var ConverterInterface */
    protected $usersConverter;

    /** @var UsersToAttendeesTransformer */
    protected $usersToAttendeesTransformer;

    /** @var SecurityFacade */
    protected $securityFacade;

    public function setUp()
    {
        $this->usersConverter = $this->getMock('Oro\Bundle\FormBundle\Autocomplete\ConverterInterface');

        $this->usersToAttendeesTransformer = $this
            ->getMockBuilder('Oro\Bundle\CalendarBundle\Form\DataTransformer\UsersToAttendeesTransformer')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityFacade->expects($this->any())
            ->method('isGranted')
            ->will($this->returnValue(true));

        $doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->attendeeManager = new AttendeeManager(
            $this->usersConverter,
            $this->usersToAttendeesTransformer,
            $this->securityFacade,
            $doctrineHelper
        );
    }
}
