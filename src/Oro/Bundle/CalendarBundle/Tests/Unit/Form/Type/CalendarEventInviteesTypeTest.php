<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Symfony\Component\Form\FormView;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Form\Type\CalendarEventInviteesType;
use Oro\Bundle\UserBundle\Entity\User;

class CalendarEventInviteesTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $transformer;

    /**
     * @var CalendarEventInviteesType
     */
    protected $type;

    protected function setUp()
    {
        $this->transformer =
            $this->getMockBuilder('Oro\Bundle\CalendarBundle\Form\DataTransformer\EventsToUsersTransformer')
                ->disableOriginalConstructor()
                ->getMock();

        $this->type = new CalendarEventInviteesType($this->transformer);
    }

    public function testBuildForm()
    {
        $builder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        $builder->expects($this->once())
            ->method('addModelTransformer')
            ->with($this->transformer);

        $this->type->buildForm($builder, []);
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(['autocomplete_alias' => 'organization_users']);

        $this->type->setDefaultOptions($resolver);
    }

    public function testBuildView()
    {
        $firstUser = new User();
        $firstUser->setUsername('1');
        $secondUser = new User();
        $secondUser->setUsername('2');

        $firstCalendar = new Calendar();
        $firstCalendar->setOwner($firstUser);
        $secondCalendar = new Calendar();
        $secondCalendar->setOwner($secondUser);

        $firstEvent = new CalendarEvent();
        $firstEvent->setCalendar($firstCalendar);
        $secondEvent = new CalendarEvent();
        $secondEvent->setCalendar($secondCalendar);

        $formData = [$firstEvent, $secondEvent];
        $transformedData = [$firstUser, $secondUser];

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($formData));

        $this->transformer->expects($this->once())
            ->method('transform')
            ->with($formData)
            ->will($this->returnValue($transformedData));

        $converter = $this->getMock('Oro\Bundle\FormBundle\Autocomplete\ConverterInterface');
        $converter->expects($this->any())
            ->method('convertItem')
            ->will($this->returnCallback([$this, 'convertEvent']));

        $formView = new FormView();
        $expectedSelectedData = json_encode([$this->convertEvent($firstUser), $this->convertEvent($secondUser)]);

        $this->type->buildView($formView, $form, ['converter' => $converter]);

        $this->assertArrayHasKey('attr', $formView->vars);
        $this->assertEquals(['data-selected-data' => $expectedSelectedData], $formView->vars['attr']);
    }

    /**
     * @param User $user
     *
     * @return string
     */
    public function convertEvent(User $user)
    {
        return $user->getUsername();
    }

    public function testGetName()
    {
        $this->assertEquals(CalendarEventInviteesType::NAME, $this->type->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('oro_user_multiselect', $this->type->getParent());
    }
}
