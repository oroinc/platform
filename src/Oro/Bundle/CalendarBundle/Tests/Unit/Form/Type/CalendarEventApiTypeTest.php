<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\ReminderBundle\Form\Type\MethodType;
use Oro\Bundle\ReminderBundle\Form\Type\ReminderCollectionType;
use Oro\Bundle\ReminderBundle\Form\Type\ReminderInterval\UnitType;
use Oro\Bundle\ReminderBundle\Form\Type\ReminderIntervalType;
use Oro\Bundle\ReminderBundle\Form\Type\ReminderType;
use Oro\Bundle\ReminderBundle\Model\SendProcessorRegistry;
use Oro\Bundle\CalendarBundle\Form\Type\CalendarEventApiType;

class CalendarEventApiTypeTest extends TypeTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $calendarEventManager;

    protected function getExtensions()
    {
        $this->registry              = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->calendarEventManager =
            $this->getMockBuilder('Oro\Bundle\CalendarBundle\Manager\CalendarEventManager')
                ->disableOriginalConstructor()
                ->getMock();

        return array(
            new PreloadedExtension(
                $this->loadTypes(),
                array()
            )
        );
    }

    public function testSubmitValidData()
    {
        $formData = array(
            'calendar'        => 1,
            'title'           => 'testTitle',
            'description'     => 'testDescription',
            'start'           => '2013-10-05T13:00:00Z',
            'end'             => '2013-10-05T13:30:00+00:00',
            'allDay'          => true,
            'backgroundColor' => '#FF0000',
            'reminders'       => new ArrayCollection()
        );

        $type = new CalendarEventApiType($this->calendarEventManager);
        $form = $this->factory->create($type);

        $this->calendarEventManager->expects($this->once())
            ->method('setCalendar')
            ->with(
                $this->isInstanceOf('Oro\Bundle\CalendarBundle\Entity\CalendarEvent'),
                Calendar::CALENDAR_ALIAS,
                1
            );

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        /** @var CalendarEvent $result */
        $result = $form->getData();
        $this->assertInstanceOf('Oro\Bundle\CalendarBundle\Entity\CalendarEvent', $result);
        $this->assertEquals('testTitle', $result->getTitle());
        $this->assertEquals('testDescription', $result->getDescription());
        $this->assertDateTimeEquals(new \DateTime('2013-10-05T13:00:00Z'), $result->getStart());
        $this->assertDateTimeEquals(new \DateTime('2013-10-05T13:30:00Z'), $result->getEnd());
        $this->assertTrue($result->getAllDay());
        $this->assertEquals('#FF0000', $result->getBackgroundColor());

        $view     = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children);
        }
    }

    public function testSubmitValidDataSystemCalendar()
    {
        $formData = array(
            'calendar'        => 1,
            'calendarAlias'   => 'system',
            'title'           => 'testTitle',
            'description'     => 'testDescription',
            'start'           => '2013-10-05T13:00:00Z',
            'end'             => '2013-10-05T13:30:00+00:00',
            'allDay'          => true,
            'backgroundColor' => '#FF0000',
            'reminders'       => new ArrayCollection()
        );

        $type = new CalendarEventApiType($this->calendarEventManager);
        $form = $this->factory->create($type);

        $this->calendarEventManager->expects($this->once())
            ->method('setCalendar')
            ->with(
                $this->isInstanceOf('Oro\Bundle\CalendarBundle\Entity\CalendarEvent'),
                'system',
                1
            );

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        /** @var CalendarEvent $result */
        $result = $form->getData();
        $this->assertInstanceOf('Oro\Bundle\CalendarBundle\Entity\CalendarEvent', $result);
        $this->assertEquals('testTitle', $result->getTitle());
        $this->assertEquals('testDescription', $result->getDescription());
        $this->assertDateTimeEquals(new \DateTime('2013-10-05T13:00:00Z'), $result->getStart());
        $this->assertDateTimeEquals(new \DateTime('2013-10-05T13:30:00Z'), $result->getEnd());
        $this->assertTrue($result->getAllDay());
        $this->assertEquals('#FF0000', $result->getBackgroundColor());

        $view     = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children);
        }
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                array(
                    'data_class'           => 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent',
                    'intention'            => 'calendar_event',
                    'csrf_protection'      => false,
                    'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"',
                )
            );

        $type = new CalendarEventApiType($this->calendarEventManager);
        $type->setDefaultOptions($resolver);
    }

    public function testGetName()
    {
        $type = new CalendarEventApiType($this->calendarEventManager);
        $this->assertEquals('oro_calendar_event_api', $type->getName());
    }

    /**
     * @return AbstractType[]
     */
    protected function loadTypes()
    {
        $types = [
            new ReminderCollectionType($this->registry),
            new CollectionType($this->registry),
            new ReminderType($this->registry),
            new MethodType(new SendProcessorRegistry([])),
            new ReminderIntervalType(),
            new UnitType(),
        ];

        $keys = array_map(
            function ($type) {
                /* @var AbstractType $type */
                return $type->getName();
            },
            $types
        );

        return array_combine($keys, $types);
    }
}
