<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\EventListener\Render;

use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityMergeBundle\Event\ValueRenderEvent;
use Oro\Bundle\EntityMergeBundle\EventListener\Render\LocalizedValueRenderListener;
use Oro\Bundle\EntityMergeBundle\Metadata\MetadataInterface;
use Oro\Bundle\LocaleBundle\Formatter\AddressFormatter;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\LocaleBundle\Model\FirstNameInterface;

class LocalizedValueRenderListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var AddressFormatter|\PHPUnit\Framework\MockObject\MockObject */
    private $addressFormatter;

    /** @var DateTimeFormatterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $dateTimeFormatter;

    /** @var EntityNameResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $entityNameResolver;

    /** @var NumberFormatter|\PHPUnit\Framework\MockObject\MockObject */
    private $numberFormatter;

    /** @var ValueRenderEvent|\PHPUnit\Framework\MockObject\MockObject */
    private $event;

    /** @var MetadataInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $metadata;

    /** @var LocalizedValueRenderListener */
    private $target;

    protected function setUp(): void
    {
        $this->addressFormatter = $this->createMock(AddressFormatter::class);
        $this->dateTimeFormatter = $this->createMock(DateTimeFormatterInterface::class);
        $this->entityNameResolver = $this->createMock(EntityNameResolver::class);
        $this->numberFormatter = $this->createMock(NumberFormatter::class);
        $this->event = $this->createMock(ValueRenderEvent::class);
        $this->metadata = $this->createMock(MetadataInterface::class);

        $this->target = new LocalizedValueRenderListener(
            $this->addressFormatter,
            $this->dateTimeFormatter,
            $this->entityNameResolver,
            $this->numberFormatter
        );
    }

    private function expectEventCalls(mixed $originalValue, mixed $localizedValue = null): void
    {
        $this->event->expects($this->any())
            ->method('getOriginalValue')
            ->willReturn($originalValue);

        $this->event->expects($this->any())
            ->method('getMetadata')
            ->willReturn($this->metadata);

        $this->event->expects($this->never())
            ->method('getConvertedValue');

        if ($localizedValue) {
            $this->event->expects($this->once())
                ->method('setConvertedValue')
                ->with($localizedValue);
        } else {
            $this->event->expects($this->never())
                ->method('setConvertedValue');
        }
    }

    public function testBeforeValueRenderWithString()
    {
        $originalValue = 'not need to localize';

        $this->addressFormatter->expects($this->never())
            ->method($this->anything());
        $this->entityNameResolver->expects($this->never())
            ->method($this->anything());
        $this->dateTimeFormatter->expects($this->never())
            ->method($this->anything());
        $this->numberFormatter->expects($this->never())
            ->method($this->anything());

        $this->expectEventCalls($originalValue);

        $this->target->beforeValueRender($this->event);
    }

    public function testBeforeValueRenderWithNumber()
    {
        $originalValue = '1';
        $localizedValue = '1%';

        $this->addressFormatter->expects($this->never())
            ->method($this->anything());
        $this->entityNameResolver->expects($this->never())
            ->method($this->anything());
        $this->dateTimeFormatter->expects($this->never())
            ->method($this->anything());
        $this->numberFormatter->expects($this->once())
            ->method('format')
            ->with($originalValue)->willReturn($localizedValue);

        $this->expectEventCalls($originalValue, $localizedValue);

        $this->target->beforeValueRender($this->event);
    }

    public function testBeforeValueRenderWithNumberAndParameters()
    {
        $originalValue = '1';
        $localizedValue = '1%';

        $testNumberStyle = 'number';

        $this->addressFormatter->expects($this->never())
            ->method($this->anything());
        $this->entityNameResolver->expects($this->never())
            ->method($this->anything());
        $this->dateTimeFormatter->expects($this->never())
            ->method($this->anything());
        $this->numberFormatter->expects($this->once())
            ->method('format')
            ->with($originalValue, $testNumberStyle)
            ->willReturn($localizedValue);

        $this->metadata->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['render_number_style', false, $testNumberStyle],
            ]);

        $this->expectEventCalls($originalValue, $localizedValue);

        $this->target->beforeValueRender($this->event);
    }

    public function testBeforeValueRenderWithAddress()
    {
        $originalValue = $this->getMockForAbstractClass(AddressInterface::class);
        $localizedValue = 'address';

        $this->addressFormatter->expects($this->once())
            ->method('format')
            ->with($originalValue)
            ->willReturn($localizedValue);

        $this->entityNameResolver->expects($this->never())
            ->method($this->anything());
        $this->dateTimeFormatter->expects($this->never())
            ->method($this->anything());
        $this->numberFormatter->expects($this->never())
            ->method($this->anything());

        $this->expectEventCalls($originalValue, $localizedValue);

        $this->target->beforeValueRender($this->event);
    }

    public function testBeforeValueRenderWithDateTime()
    {
        $originalValue = new \DateTime();
        $localizedValue = date('Y-m-d');

        $this->addressFormatter->expects($this->never())
            ->method($this->anything());
        $this->entityNameResolver->expects($this->never())
            ->method($this->anything());
        $this->dateTimeFormatter->expects($this->once())
            ->method('format')
            ->with($originalValue)
            ->willReturn($localizedValue);
        $this->numberFormatter->expects($this->never())
            ->method($this->anything());

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

        $this->addressFormatter->expects($this->never())
            ->method($this->anything());
        $this->entityNameResolver->expects($this->never())
            ->method($this->anything());
        $this->dateTimeFormatter->expects($this->once())
            ->method('format')
            ->with($originalValue, $testDateType, $testTimeType, null, null, $testFormat)
            ->willReturn($localizedValue);

        $this->numberFormatter->expects($this->never())
            ->method($this->anything());

        $this->metadata->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['render_date_type', false, $testDateType],
                ['render_time_type', false, $testTimeType],
                ['render_datetime_pattern', false, $testFormat],
            ]);

        $this->expectEventCalls($originalValue, $localizedValue);

        $this->target->beforeValueRender($this->event);
    }

    public function testBeforeValueRenderWithNameEntity()
    {
        $originalValue = $this->getMockForAbstractClass(FirstNameInterface::class);
        $localizedValue = 'name';

        $this->addressFormatter->expects($this->never())
            ->method($this->anything());
        $this->entityNameResolver->expects($this->once())
            ->method('getName')
            ->with($originalValue)
            ->willReturn($localizedValue);
        $this->dateTimeFormatter->expects($this->never())
            ->method($this->anything());
        $this->numberFormatter->expects($this->never())
            ->method($this->anything());

        $this->expectEventCalls($originalValue, $localizedValue);

        $this->target->beforeValueRender($this->event);
    }
}
