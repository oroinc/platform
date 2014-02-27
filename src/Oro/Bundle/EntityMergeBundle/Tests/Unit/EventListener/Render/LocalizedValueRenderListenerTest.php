<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\EventListener\Render;

use Oro\Bundle\EntityMergeBundle\EventListener\Render\LocalizedValueRenderListener;

class LocalizedValueRenderListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LocalizedValueRenderListener
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

    protected function setUp()
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
        $this->event = $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Event\ValueRenderEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadata = $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\MetadataInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->target = new LocalizedValueRenderListener(
            $this->addressFormatter,
            $this->dateTimeFormatter,
            $this->nameFormatter,
            $this->numberFormatter
        );
    }

    protected function expectEventCalls($originalValue, $localizedValue = null)
    {
        $this->event->expects($this->any())
            ->method('getOriginalValue')
            ->will($this->returnValue($originalValue));

        $this->event->expects($this->any())
            ->method('getMetadata')
            ->will($this->returnValue($this->metadata));

        $this->event->expects($this->never())->method('getConvertedValue');

        if ($localizedValue) {
            $this->event->expects($this->once())->method('setConvertedValue')->with($localizedValue);
        } else {
            $this->event->expects($this->never())->method('setConvertedValue');
        }
    }

    public function testBeforeValueRenderWithString()
    {
        $originalValue = 'not need to localize';

        $this->addressFormatter->expects($this->never())->method($this->anything());
        $this->nameFormatter->expects($this->never())->method($this->anything());
        $this->dateTimeFormatter->expects($this->never())->method($this->anything());
        $this->numberFormatter->expects($this->never())->method($this->anything());

        $this->expectEventCalls($originalValue);

        $this->target->beforeValueRender($this->event);
    }

    public function testBeforeValueRenderWithNumber()
    {
        $originalValue = '1';
        $localizedValue = '1%';

        $this->addressFormatter->expects($this->never())->method($this->anything());
        $this->nameFormatter->expects($this->never())->method($this->anything());
        $this->dateTimeFormatter->expects($this->never())->method($this->anything());
        $this->numberFormatter->expects($this->once())
            ->method('format')
            ->with($originalValue)->will($this->returnValue($localizedValue));

        $this->expectEventCalls($originalValue, $localizedValue);

        $this->target->beforeValueRender($this->event);
    }

    public function testBeforeValueRenderWithNumberAndParameters()
    {
        $originalValue = '1';
        $localizedValue = '1%';

        $testNumberStyle = 'number';

        $this->addressFormatter->expects($this->never())->method($this->anything());
        $this->nameFormatter->expects($this->never())->method($this->anything());
        $this->dateTimeFormatter->expects($this->never())->method($this->anything());
        $this->numberFormatter->expects($this->once())
            ->method('format')
            ->with($originalValue, $testNumberStyle)
            ->will($this->returnValue($localizedValue));

        $this->metadata->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValueMap(
                    array(
                        array('render_number_style', false, $testNumberStyle),
                    )
                )
            );

        $this->expectEventCalls($originalValue, $localizedValue);

        $this->target->beforeValueRender($this->event);
    }

    public function testBeforeValueRenderWithAddress()
    {
        $originalValue = $this->getMockForAbstractClass('Oro\Bundle\LocaleBundle\Model\AddressInterface');
        $localizedValue = 'address';

        $this->addressFormatter->expects($this->once())
            ->method('format')
            ->with($originalValue)
            ->will($this->returnValue($localizedValue));

        $this->nameFormatter->expects($this->never())->method($this->anything());
        $this->dateTimeFormatter->expects($this->never())->method($this->anything());
        $this->numberFormatter->expects($this->never())->method($this->anything());

        $this->expectEventCalls($originalValue, $localizedValue);

        $this->target->beforeValueRender($this->event);
    }

    public function testBeforeValueRenderWithDateTime()
    {
        $originalValue = new \DateTime();
        $localizedValue = date('Y-m-d');

        $this->addressFormatter->expects($this->never())->method($this->anything());
        $this->nameFormatter->expects($this->never())->method($this->anything());
        $this->dateTimeFormatter->expects($this->once())
            ->method('format')
            ->with($originalValue)
            ->will($this->returnValue($localizedValue));
        $this->numberFormatter->expects($this->never())->method($this->anything());

        $this->expectEventCalls($originalValue, $localizedValue);

        $this->target->beforeValueRender($this->event);
    }

    public function testBeforeValueRenderWithDateTimeAndParameters()
    {
        $originalValue = new \DateTime();
        $localizedValue = date('Y-m-d');

        $testDateType = 'medium';
        $testTimeType = 'FULL';
        $testFormat = 'd_m_y';

        $this->addressFormatter->expects($this->never())->method($this->anything());
        $this->nameFormatter->expects($this->never())->method($this->anything());
        $this->dateTimeFormatter->expects($this->once())
            ->method('format')
            ->with($originalValue, $testDateType, $testTimeType, null, null, $testFormat)
            ->will($this->returnValue($localizedValue));

        $this->numberFormatter->expects($this->never())->method($this->anything());

        $this->metadata->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValueMap(
                    array(
                        array('render_date_type', false, $testDateType),
                        array('render_time_type', false, $testTimeType),
                        array('render_datetime_pattern', false, $testFormat),
                    )
                )
            );

        $this->expectEventCalls($originalValue, $localizedValue);

        $this->target->beforeValueRender($this->event);
    }

    public function testBeforeValueRenderWithNameEntity()
    {
        $originalValue = $this->getMockForAbstractClass('Oro\Bundle\LocaleBundle\Model\FirstNameInterface');
        $localizedValue = 'name';

        $this->addressFormatter->expects($this->never())->method($this->anything());
        $this->nameFormatter->expects($this->once())
            ->method('format')
            ->with($originalValue)
            ->will($this->returnValue($localizedValue));
        $this->dateTimeFormatter->expects($this->never())->method($this->anything());
        $this->numberFormatter->expects($this->never())->method($this->anything());

        $this->expectEventCalls($originalValue, $localizedValue);

        $this->target->beforeValueRender($this->event);
    }
}
