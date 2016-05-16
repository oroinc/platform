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

    public function testBuildViewWithData()
    {

        $converter = $this->getMock('Oro\Bundle\FormBundle\Autocomplete\ConverterInterface');
        $converter->expects($this->once())
            ->method('convertItem')
            ->will($this->returnCallback(function (User $user) {
                return ['fullName' => $user->getEmail(), 'email' => $user->getEmail()];
            }));

        $view = new FormView();

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())
            ->method('getData')
            ->will($this->returnValue([
                (new Attendee())
                    ->setEmail('name@example.com')
            ]));

        $this->type->buildView($view, $form, ['converter' => $converter, 'disable_user_removal' => false]);

        $this->assertEquals(
            json_encode([['fullName' => 'name@example.com', 'email' => 'name@example.com']]),
            $view->vars['attr']['data-selected-data']
        );
    }
}
