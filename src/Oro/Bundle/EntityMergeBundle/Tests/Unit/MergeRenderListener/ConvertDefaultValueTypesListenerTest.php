<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\MergeRenderListener;

use DateTime;
use Oro\Bundle\EntityMergeBundle\EventListener\MergeRenderListener\ConvertDefaultValueTypesListener;

class ConvertDefaultValueTypesListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConvertDefaultValueTypesListener
     */
    protected $target;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $addressFormatter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $dateTimeFormatter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $nameFormatter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $numberFormatter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $event;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $metadata;

    public function setUp()
    {
        $this->addressFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\AddressFormatter')
            ->disableOriginalConstructor()
            ->getMock();
        $this->dateTimeFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter')
            ->disableOriginalConstructor()
            ->getMock();
        $this->nameFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\NameFormatter')
            ->disableOriginalConstructor()
            ->getMock();
        $this->numberFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\NumberFormatter')
            ->disableOriginalConstructor()
            ->getMock();
        $this->event = $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Event\FieldValueRenderEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadata = $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\MetadataInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->target = new ConvertDefaultValueTypesListener(
            $this->addressFormatter,
            $this->dateTimeFormatter,
            $this->nameFormatter,
            $this->numberFormatter
        );
    }

    public function init($entity)
    {
        $this->event->expects($this->any())
            ->method('getEntity')
            ->withAnyParameters()
            ->will($this->returnValue($entity));
        $this->event->expects($this->any())
            ->method('getMetadata')
            ->withAnyParameters()
            ->will($this->returnValue($this->metadata));
        $this->event->expects($this->any())
            ->method('getFieldValue')
            ->withAnyParameters()
            ->will($this->returnValue('Not localised representative or localised by previous listener'));
    }

    public function testAfterCalculateShouldNotCallAnyFormatterIfEntityNotAnObjectAndNotANumber()
    {
        $this->addressFormatter->expects($this->never())
            ->method('format')
            ->withAnyParameters();
        $this->nameFormatter->expects($this->never())
            ->method('format')
            ->withAnyParameters();
        $this->dateTimeFormatter->expects($this->never())
            ->method('format')
            ->withAnyParameters();
        $this->numberFormatter->expects($this->never())
            ->method('format')
            ->withAnyParameters();
        $this->event->expects($this->never())
            ->method('setFieldValue')
            ->withAnyParameters();
        $entity = 'not need to localise';

        $this->init($entity);

        $this->target->afterCalculate($this->event);
    }

    public function testAfterCalculateShouldCallNumberFormatterIfEntityIsNumber()
    {
        $entity = '1';
        $this->addressFormatter->expects($this->never())
            ->method('format')
            ->withAnyParameters();
        $this->nameFormatter->expects($this->never())
            ->method('format')
            ->withAnyParameters();
        $this->dateTimeFormatter->expects($this->never())
            ->method('format')
            ->withAnyParameters();
        $this->numberFormatter->expects($this->once())
            ->method('format')
            ->with($entity);

        $this->init($entity, 'Not localised representative or localised by previous listener');

        $this->target->afterCalculate($this->event);
    }

    public function testAfterCalculateShouldCallSetFieldValueIfEntityIsNumberWithCorrectValues()
    {
        $entity = '1';
        $localised = '1%';
        $this->addressFormatter->expects($this->never())
            ->method('format')
            ->withAnyParameters();
        $this->nameFormatter->expects($this->never())
            ->method('format')
            ->withAnyParameters();
        $this->dateTimeFormatter->expects($this->never())
            ->method('format')
            ->withAnyParameters();
        $this->numberFormatter->expects($this->once())
            ->method('format')
            ->with($entity)->will($this->returnValue($localised));
        $this->event->expects($this->once())
            ->method('setFieldValue')
            ->with($localised);

        $this->init($entity);

        $this->target->afterCalculate($this->event);
    }

    public function testAfterCalculateShouldNotChangePreviousRepresentativeIfFormatterReturnFalse()
    {
        $entity = '1';
        $this->addressFormatter->expects($this->never())
            ->method('format')
            ->withAnyParameters();
        $this->nameFormatter->expects($this->never())
            ->method('format')
            ->withAnyParameters();
        $this->dateTimeFormatter->expects($this->never())
            ->method('format')
            ->withAnyParameters();
        $this->numberFormatter->expects($this->once())
            ->method('format')
            ->with($entity)->will($this->returnValue(false));
        $this->event->expects($this->never())
            ->method('setFieldValue')
            ->withAnyParameters();

        $value = 'Not localised representative or localised by previous listener';
        $this->init($entity, $value);

        $this->target->afterCalculate($this->event);
    }

    public function testAfterCalculateShouldCallAddressFormatterIfEntityIsAddress()
    {
        $entity = $this->getMockForAbstractClass('Oro\Bundle\LocaleBundle\Model\AddressInterface');
        $this->addressFormatter->expects($this->once())
            ->method('format')
            ->withAnyParameters()->will($this->returnValue('test'));
        $this->nameFormatter->expects($this->never())
            ->method('format')
            ->withAnyParameters();
        $this->dateTimeFormatter->expects($this->never())
            ->method('format')
            ->withAnyParameters();
        $this->numberFormatter->expects($this->never())
            ->method('format')
            ->withAnyParameters();
        $this->event->expects($this->once())
            ->method('setFieldValue')
            ->withAnyParameters();

        $this->init($entity);

        $this->target->afterCalculate($this->event);
    }

    public function testAfterCalculateShouldCallSetFieldValueIfEntityIsAddressWithCorrectAddress()
    {
        $localised = 'test localised address';
        $entity = $this->getMockForAbstractClass('Oro\Bundle\LocaleBundle\Model\AddressInterface');
        $this->addressFormatter->expects($this->once())
            ->method('format')
            ->withAnyParameters()->will($this->returnValue($localised));
        $this->nameFormatter->expects($this->never())
            ->method('format')
            ->withAnyParameters();
        $this->dateTimeFormatter->expects($this->never())
            ->method('format')
            ->withAnyParameters();
        $this->numberFormatter->expects($this->never())
            ->method('format')
            ->withAnyParameters();
        $this->event->expects($this->once())
            ->method('setFieldValue')
            ->with($localised);

        $this->init($entity);

        $this->target->afterCalculate($this->event);
    }

    public function testAfterCalculateShouldNotChangePreviousRepresentativeIfAddressFormatterReturnFalse()
    {
        $entity = $this->getMockForAbstractClass('Oro\Bundle\LocaleBundle\Model\AddressInterface');
        $this->addressFormatter->expects($this->once())
            ->method('format')
            ->withAnyParameters()->will($this->returnValue(false));
        $this->nameFormatter->expects($this->never())
            ->method('format')
            ->withAnyParameters();
        $this->dateTimeFormatter->expects($this->never())
            ->method('format')
            ->withAnyParameters();
        $this->numberFormatter->expects($this->never())
            ->method('format')
            ->withAnyParameters();
        $this->event->expects($this->never())
            ->method('setFieldValue')
            ->withAnyParameters();

        $this->init($entity);

        $this->target->afterCalculate($this->event);
    }


    public function testAfterCalculateShouldCallDateTimeFormatterFormatterIfEntityIsDateTime()
    {
        $entity = new DateTime();
        $this->addressFormatter->expects($this->never())
            ->method('format')
            ->withAnyParameters();
        $this->nameFormatter->expects($this->never())
            ->method('format')
            ->withAnyParameters();
        $this->dateTimeFormatter->expects($this->once())
            ->method('format')
            ->withAnyParameters();
        $this->numberFormatter->expects($this->never())
            ->method('format')
            ->withAnyParameters();
        $this->event->expects($this->once())
            ->method('setFieldValue')
            ->withAnyParameters();

        $this->init($entity);

        $this->target->afterCalculate($this->event);
    }

    public function testAfterCalculateShouldCallSetFieldValueIfEntityIsDateTimeWithCorrectString()
    {
        $localised = date('d/m/Y H:i:s');
        $entity = new DateTime();
        $this->addressFormatter->expects($this->never())
            ->method('format')
            ->withAnyParameters();
        $this->nameFormatter->expects($this->never())
            ->method('format')
            ->withAnyParameters();
        $this->dateTimeFormatter->expects($this->once())
            ->method('format')
            ->withAnyParameters()
            ->will($this->returnValue($localised));
        $this->numberFormatter->expects($this->never())
            ->method('format')
            ->withAnyParameters();
        $this->event->expects($this->once())
            ->method('setFieldValue')
            ->with($localised);

        $this->init($entity);

        $this->target->afterCalculate($this->event);
    }

    public function testAfterCalculateShouldNotChangePreviousRepresentativeIfDateTimeFormatterReturnFalse()
    {
        $entity = new DateTime();
        $this->addressFormatter->expects($this->never())
            ->method('format')
            ->withAnyParameters();
        $this->nameFormatter->expects($this->never())
            ->method('format')
            ->withAnyParameters();
        $this->dateTimeFormatter->expects($this->once())
            ->method('format')
            ->withAnyParameters()
            ->will($this->returnValue(false));
        $this->numberFormatter->expects($this->never())
            ->method('format')
            ->withAnyParameters();
        $this->event->expects($this->never())
            ->method('setFieldValue')
            ->withAnyParameters();

        $value = 'Not localised representative or localised by previous listener';
        $this->init($entity, $value);

        $this->target->afterCalculate($this->event);
    }

    public function testAfterCalculateShouldCallNameFormatterFormatterIfEntityIsName()
    {
        $entity = $this->getMockForAbstractClass('Oro\Bundle\LocaleBundle\Model\FirstNameInterface');
        $this->addressFormatter->expects($this->never())
            ->method('format')
            ->withAnyParameters();
        $this->nameFormatter->expects($this->once())
            ->method('format')
            ->withAnyParameters();
        $this->dateTimeFormatter->expects($this->never())
            ->method('format')
            ->withAnyParameters();
        $this->numberFormatter->expects($this->never())
            ->method('format')
            ->withAnyParameters();
        $this->event->expects($this->once())
            ->method('setFieldValue')
            ->withAnyParameters();

        $this->init($entity);

        $this->target->afterCalculate($this->event);
    }

    public function testAfterCalculateShouldCallSetFieldValueIfEntityIsNameWithCorrectString()
    {
        $localised = 'Alex Smith';
        $entity = $this->getMockForAbstractClass('Oro\Bundle\LocaleBundle\Model\FirstNameInterface');
        $this->addressFormatter->expects($this->never())
            ->method('format')
            ->withAnyParameters();
        $this->nameFormatter->expects($this->once())
            ->method('format')
            ->withAnyParameters()
            ->will($this->returnValue($localised));
        $this->dateTimeFormatter->expects($this->never())
            ->method('format')
            ->withAnyParameters();
        $this->numberFormatter->expects($this->never())
            ->method('format')
            ->withAnyParameters();
        $this->event->expects($this->once())
            ->method('setFieldValue')
            ->with($localised);

        $this->init($entity);

        $this->target->afterCalculate($this->event);
    }

    public function testAfterCalculateShouldNotChangePreviousRepresentativeIfNameFormatterReturnFalse()
    {
        $entity = $this->getMockForAbstractClass('Oro\Bundle\LocaleBundle\Model\FirstNameInterface');
        $this->addressFormatter->expects($this->never())
            ->method('format')
            ->withAnyParameters();
        $this->nameFormatter->expects($this->once())
            ->method('format')
            ->withAnyParameters()
            ->will($this->returnValue(false));
        $this->dateTimeFormatter->expects($this->never())
            ->method('format')
            ->withAnyParameters();
        $this->numberFormatter->expects($this->never())
            ->method('format')
            ->withAnyParameters();
        $this->event->expects($this->never())
            ->method('setFieldValue')
            ->withAnyParameters();

        $value = 'Not localised representative or localised by previous listener';
        $this->init($entity, $value);

        $this->target->afterCalculate($this->event);
    }
}
