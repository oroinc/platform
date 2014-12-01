<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\TypeTestCase;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Form\Type\CalendarChoiceType;

class CalendarChoiceTypeTest extends TypeTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $calendarEventManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    protected function getExtensions()
    {
        $this->calendarEventManager = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Manager\CalendarEventManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        return [];
    }

    public function testSubmitValidData()
    {
        $entity   = new CalendarEvent();
        $formData = [
            'calendarUid' => 'system_123',
        ];

        $this->calendarEventManager->expects($this->any())
            ->method('getCalendarUid')
            ->will(
                $this->returnCallback(
                    function ($alias, $id) {
                        return sprintf('%s_%d', $alias, $id);
                    }
                )
            );
        $this->calendarEventManager->expects($this->once())
            ->method('getSystemCalendars')
            ->will(
                $this->returnValue(
                    [
                        ['id' => 123, 'name' => 'System1', 'public' => false]
                    ]
                )
            );
        $this->calendarEventManager->expects($this->once())
            ->method('getUserCalendars')
            ->will(
                $this->returnValue(
                    [
                        ['id' => 123, 'name' => 'User1']
                    ]
                )
            );
        $this->calendarEventManager->expects($this->once())
            ->method('parseCalendarUid')
            ->will(
                $this->returnCallback(
                    function ($uid) {
                        return explode('_', $uid);
                    }
                )
            );
        $this->calendarEventManager->expects($this->once())
            ->method('setCalendar')
            ->with($this->identicalTo($entity), 'system', 123)
            ->will(
                $this->returnValue(
                    [
                        ['id' => 123, 'name' => 'User1']
                    ]
                )
            );

        $type = new CalendarChoiceType($this->calendarEventManager, $this->translator);
        $form = $this->factory->createNamed(
            'calendarUid',
            $type,
            null,
            [
                'mapped'          => false,
                'auto_initialize' => false,
            ]
        );

        $parentForm = $this->factory->create('form', $entity);
        $parentForm->add($form);

        $parentForm->submit($formData);

        $this->assertTrue($form->isSynchronized());
    }

    public function testSubmitValidDataForExpanded()
    {
        $entity   = new CalendarEvent();
        $formData = [
            'calendarUid' => ['system_123'],
        ];

        $this->calendarEventManager->expects($this->any())
            ->method('getCalendarUid')
            ->will(
                $this->returnCallback(
                    function ($alias, $id) {
                        return sprintf('%s_%d', $alias, $id);
                    }
                )
            );
        $this->calendarEventManager->expects($this->once())
            ->method('getSystemCalendars')
            ->will(
                $this->returnValue(
                    [
                        ['id' => 123, 'name' => 'System1', 'public' => false]
                    ]
                )
            );
        $this->calendarEventManager->expects($this->never())
            ->method('getUserCalendars');
        $this->calendarEventManager->expects($this->once())
            ->method('parseCalendarUid')
            ->will(
                $this->returnCallback(
                    function ($uid) {
                        return explode('_', $uid);
                    }
                )
            );
        $this->calendarEventManager->expects($this->once())
            ->method('setCalendar')
            ->with($this->identicalTo($entity), 'system', 123)
            ->will(
                $this->returnValue(
                    [
                        ['id' => 123, 'name' => 'User1']
                    ]
                )
            );

        $type = new CalendarChoiceType($this->calendarEventManager, $this->translator);
        $form = $this->factory->createNamed(
            'calendarUid',
            $type,
            null,
            [
                'mapped'          => false,
                'auto_initialize' => false,
                'is_new'          => true,
            ]
        );

        $parentForm = $this->factory->create('form', $entity);
        $parentForm->add($form);

        $parentForm->submit($formData);

        $this->assertTrue($form->isSynchronized());
    }

    public function testGetName()
    {
        $type = new CalendarChoiceType($this->calendarEventManager, $this->translator);
        $this->assertEquals('oro_calendar_choice', $type->getName());
    }

    public function testGetParent()
    {
        $type = new CalendarChoiceType($this->calendarEventManager, $this->translator);
        $this->assertEquals('choice', $type->getParent());
    }
}
