<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Form\Type\SystemCalendarType;
use Oro\Bundle\FormBundle\Form\Type\OroSimpleColorPickerType;

class SystemCalendarTypeTest extends TypeTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $calendarConfig;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    protected function getExtensions()
    {
        $this->securityFacade       = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->calendarConfig =
            $this->getMockBuilder('Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig')
                ->disableOriginalConstructor()
                ->getMock();

        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator    = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_calendar.calendar_colors')
            ->will($this->returnValue(['#FF0000']));

        return [
            new PreloadedExtension(
                $this->loadTypes(),
                []
            )
        ];
    }

    /**
     * @return AbstractType[]
     */
    protected function loadTypes()
    {
        $types = [
            new OroSimpleColorPickerType($this->configManager, $this->translator),
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

    public function testSubmitValidData()
    {
        $formData = [
            'name'            => 'test',
            'backgroundColor' => '#FF0000',
            'public'          => '1'
        ];

        $this->calendarConfig->expects($this->any())
            ->method('isPublicCalendarEnabled')
            ->will($this->returnValue(true));
        $this->calendarConfig->expects($this->any())
            ->method('isSystemCalendarEnabled')
            ->will($this->returnValue(true));
        $this->securityFacade->expects($this->any())
            ->method('isGranted')
            ->will(
                $this->returnValueMap(
                    [
                        ['oro_public_calendar_management', null, true],
                        ['oro_system_calendar_create', null, true],
                    ]
                )
            );

        $type = new SystemCalendarType($this->securityFacade, $this->calendarConfig);
        $form = $this->factory->create($type);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        /** @var SystemCalendar $result */
        $result = $form->getData();
        $this->assertInstanceOf('Oro\Bundle\CalendarBundle\Entity\SystemCalendar', $result);
        $this->assertEquals('test', $result->getName());
        $this->assertEquals('#FF0000', $result->getBackgroundColor());
        $this->assertTrue($result->isPublic());
    }

    public function testSubmitValidDataPublicCalendarOnly()
    {
        $formData = [
            'name'            => 'test',
            'backgroundColor' => '#FF0000',
            'public'          => '1'
        ];

        $this->calendarConfig->expects($this->any())
            ->method('isPublicCalendarEnabled')
            ->will($this->returnValue(true));
        $this->calendarConfig->expects($this->any())
            ->method('isSystemCalendarEnabled')
            ->will($this->returnValue(false));

        $type = new SystemCalendarType($this->securityFacade, $this->calendarConfig);
        $form = $this->factory->create($type);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        /** @var SystemCalendar $result */
        $result = $form->getData();
        $this->assertInstanceOf('Oro\Bundle\CalendarBundle\Entity\SystemCalendar', $result);
        $this->assertEquals('test', $result->getName());
        $this->assertEquals('#FF0000', $result->getBackgroundColor());
        $this->assertTrue($result->isPublic());
    }

    public function testSubmitValidDataSystemCalendarOnly()
    {
        $formData = [
            'name'            => 'test',
            'backgroundColor' => '#FF0000',
            'public'          => '0'
        ];

        $this->calendarConfig->expects($this->any())
            ->method('isPublicCalendarEnabled')
            ->will($this->returnValue(false));
        $this->calendarConfig->expects($this->any())
            ->method('isSystemCalendarEnabled')
            ->will($this->returnValue(true));

        $type = new SystemCalendarType($this->securityFacade, $this->calendarConfig);
        $form = $this->factory->create($type);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        /** @var SystemCalendar $result */
        $result = $form->getData();
        $this->assertInstanceOf('Oro\Bundle\CalendarBundle\Entity\SystemCalendar', $result);
        $this->assertEquals('test', $result->getName());
        $this->assertEquals('#FF0000', $result->getBackgroundColor());
        $this->assertFalse($result->isPublic());
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                array(
                    'data_class' => 'Oro\Bundle\CalendarBundle\Entity\SystemCalendar',
                    'intention'  => 'system_calendar',
                )
            );

        $type = new SystemCalendarType($this->securityFacade, $this->calendarConfig);
        $type->setDefaultOptions($resolver);
    }

    public function testGetName()
    {
        $type = new SystemCalendarType($this->securityFacade, $this->calendarConfig);
        $this->assertEquals('oro_system_calendar', $type->getName());
    }
}
