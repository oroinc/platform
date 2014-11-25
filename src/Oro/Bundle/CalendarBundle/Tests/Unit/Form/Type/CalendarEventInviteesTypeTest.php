<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CalendarBundle\Form\Type\CalendarEventInviteesType;
use Oro\Bundle\CalendarBundle\Form\DataTransformer\EventsToUsersTransformer;

class CalendarEventInviteesTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EventsToUsersTransformer
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
            ->with(['autocomplete_alias' => 'users_without_current']);

        $this->type->setDefaultOptions($resolver);
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
