<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormView;

use Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Form\Type\CalendarEventAttendeesType;
use Oro\Bundle\CalendarBundle\Manager\AttendeeManager;

class CalendarEventAttendeesTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var CalendarEventAttendeesType */
    protected $type;

    /** @var AttendeeManager */
    protected $attendeeManager;

    public function setUp()
    {
        $transformer = $this
            ->getMockBuilder('Oro\Bundle\CalendarBundle\Form\DataTransformer\UsersToAttendeesTransformer')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $this->attendeeManager = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Manager\AttendeeManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->type = new CalendarEventAttendeesType($transformer, $this->attendeeManager);
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

    public function testBuildViewWithData()
    {
        $attendee = new Attendee();

        $result = [
            [
                'id' => 1,
                'firstName' => 'user',
                'email' => 'user@example.com',
            ]
        ];

        $view = new FormView();
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())
            ->method('getData')
            ->will($this->returnValue([$attendee]));

        $this->attendeeManager
            ->expects($this->once())
            ->method('attendeesToAutocompleteData')
            ->will($this->returnValue($result));

        $this->type->buildView($view, $form, []);

        $this->assertEquals(
            json_encode($result),
            $view->vars['attr']['data-selected-data']
        );
    }
}
