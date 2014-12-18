<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarProperty;
use Oro\Bundle\CalendarBundle\Form\Type\CalendarPropertyApiType;
use Oro\Bundle\CalendarBundle\Tests\Unit\ReflectionUtil;
use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;

class CalendarPropertyApiTypeTest extends TypeTestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    protected function getExtensions()
    {
        $this->registry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $em             = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $meta           = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $repo           = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $calendar       = new Calendar();
        ReflectionUtil::setId($calendar, 1);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with('OroCalendarBundle:Calendar')
            ->will($this->returnValue($em));
        $em->expects($this->any())
            ->method('getClassMetadata')
            ->with('OroCalendarBundle:Calendar')
            ->will($this->returnValue($meta));
        $em->expects($this->any())
            ->method('getRepository')
            ->with('OroCalendarBundle:Calendar')
            ->will($this->returnValue($repo));
        $meta->expects($this->any())
            ->method('getSingleIdentifierFieldName')
            ->will($this->returnValue('id'));
        $repo->expects($this->any())
            ->method('find')
            ->with($calendar->getId())
            ->will($this->returnValue($calendar));

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
            new EntityIdentifierType($this->registry),
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
            'targetCalendar'  => 1,
            'calendarAlias'   => 'testCalendarAlias',
            'calendar'        => 2,
            'position'        => 100,
            'visible'         => true,
            'backgroundColor' => '#00FF00',
        ];

        $type = new CalendarPropertyApiType();
        $form = $this->factory->create($type);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        /** @var CalendarProperty $result */
        $result = $form->getData();
        $this->assertInstanceOf('Oro\Bundle\CalendarBundle\Entity\CalendarProperty', $result);
        $calendar = new Calendar();
        ReflectionUtil::setId($calendar, 1);
        $this->assertEquals($calendar, $result->getTargetCalendar());
        $this->assertEquals('testCalendarAlias', $result->getCalendarAlias());
        $this->assertEquals(2, $result->getCalendar());
        $this->assertEquals(100, $result->getPosition());
        $this->assertTrue($result->getVisible());
        $this->assertEquals('#00FF00', $result->getBackgroundColor());

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
                [
                    'data_class'           => 'Oro\Bundle\CalendarBundle\Entity\CalendarProperty',
                    'csrf_protection'      => false,
                    'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"',
                ]
            );

        $type = new CalendarPropertyApiType();
        $type->setDefaultOptions($resolver);
    }

    public function testGetName()
    {
        $type = new CalendarPropertyApiType();
        $this->assertEquals('oro_calendar_property_api', $type->getName());
    }
}
