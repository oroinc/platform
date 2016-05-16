<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormView;

use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Form\Type\CalendarEventAttendeesType;
use Oro\Bundle\UserBundle\Entity\User;

class CalendarEventAttendeesTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var CalendarEventAttendeesType */
    protected $type;

    public function setUp()
    {
        $transformer = $this
            ->getMockBuilder('Oro\Bundle\CalendarBundle\Form\DataTransformer\UsersToAttendeesTransformer')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $this->type = new CalendarEventAttendeesType($transformer);
    }

    public function testParent()
    {
        $this->assertEquals('oro_user_multiselect', $this->type->getParent());
    }

    public function testName()
    {
        $this->assertEquals('oro_calendar_event_attendees', $this->type->getName());
    }

    public function testBuildForm()
    {
        $builder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        $builder->expects($this->once())
            ->method('addModelTransformer')
            ->with($this->isInstanceOf('Oro\Bundle\CalendarBundle\Form\DataTransformer\UsersToAttendeesTransformer'));

        $this->type->buildForm($builder, []);
    }

    public function testBuildViewWithoutData()
    {
        $converter = $this->getMock('Oro\Bundle\FormBundle\Autocomplete\ConverterInterface');
        $view = new FormView();
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())
            ->method('getData')
            ->will($this->returnValue([]));

        $this->type->buildView($view, $form, ['converter' => $converter]);

        $this->assertEmpty($view->vars['attr']);
    }

    /**
     * @dataProvider dataProvider
     *
     * @param Attendee $attendee
     * @param array    $result
     */
    public function testBuildViewWithData($attendee, $result)
    {
        $converter = $this->getMock('Oro\Bundle\FormBundle\Autocomplete\ConverterInterface');

        $converter->expects($this->any())
            ->method('convertItem')
            ->will($this->returnCallback(function (User $user) {
                return [
                    'fullName' => $user->getEmail(), 'email' => $user->getEmail(),
                ];
            }));

        $view = new FormView();
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())
            ->method('getData')
            ->will($this->returnValue([$attendee]));

        $this->type->buildView($view, $form, ['converter' => $converter, 'disable_user_removal' => false]);

        $this->assertEquals(
            json_encode([$result]),
            $view->vars['attr']['data-selected-data']
        );
    }

    /**
     * @return array
     */
    public function dataProvider()
    {
        $user1 = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')
            ->disableOriginalConstructor()
            ->setMethods(['getEmail'])
            ->getMock();

        $user2 = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')
            ->disableOriginalConstructor()
            ->setMethods(['getEmail'])
            ->getMock();

        $calendarEvent1 = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\CalendarEvent')
            ->disableOriginalConstructor()
            ->setMethods(['getRelatedAttendee'])
            ->getMock();

        $calendarEvent2 = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\CalendarEvent')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $attendee1 = new Attendee(1);
        $attendee1->setEmail('name@example.com');
        $attendee1->setUser($user1);
        $attendee1->setCalendarEvent($calendarEvent1);

        $calendarEvent1->expects($this->any())
            ->method('getRelatedAttendee')
            ->will($this->returnValue($attendee1));

        $user1->expects($this->any())
            ->method('getEmail')
            ->will($this->returnValue('name@example.com'));

        $attendee2 = new Attendee(2);
        $attendee2->setEmail('user@example.com');
        $attendee2->setCalendarEvent($calendarEvent2);

        $attendee3 = new Attendee(3);
        $attendee3->setEmail('unlocked@example.com');
        $attendee3->setCalendarEvent($calendarEvent1);
        $attendee3->setUser($user2);

        $user2->expects($this->any())
            ->method('getEmail')
            ->will($this->returnValue('unlocked@example.com'));

        return [
            'have user and event owner' =>[
                'attendee' => $attendee1,
                'result' => ['fullName' => 'name@example.com', 'email' => 'name@example.com', 'locked' => true]
            ],
            'does not have user' =>[
                'attendee' => $attendee2,
                'result' => ['fullName' => 'user@example.com', 'email' => 'user@example.com', 'locked' => true]
            ],
            'have user and child event' =>[
                'attendee' => $attendee3,
                'result' => ['fullName' => 'unlocked@example.com', 'email' => 'unlocked@example.com']
            ],
        ];
    }
}
