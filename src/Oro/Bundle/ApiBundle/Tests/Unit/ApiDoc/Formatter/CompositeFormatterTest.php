<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc\Formatter;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\Formatter\FormatterInterface;
use Oro\Bundle\ApiBundle\ApiDoc\Formatter\CompositeFormatter;
use Oro\Bundle\ApiBundle\ApiDoc\RestDocViewDetector;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CompositeFormatterTest extends TestCase
{
    private RestDocViewDetector&MockObject $docViewDetector;
    private FormatterInterface&MockObject $formatter;
    private CompositeFormatter $compositeFormatter;

    #[\Override]
    protected function setUp(): void
    {
        $this->docViewDetector = $this->createMock(RestDocViewDetector::class);
        $this->formatter = $this->createMock(FormatterInterface::class);

        $this->compositeFormatter = new CompositeFormatter($this->docViewDetector);
        $this->compositeFormatter->addFormatter('test', $this->formatter);
    }

    public function testFormatForViewWithUnknownFormatter(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot find formatter for "unknown" API view.');

        $data = ['key' => 'value'];

        $this->docViewDetector->expects(self::once())
            ->method('getView')
            ->willReturn('unknown');

        $this->compositeFormatter->format($data);
    }

    public function testFormatOneForViewWithUnknownFormatter(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot find formatter for "unknown" API view.');

        $data = $this->createMock(ApiDoc::class);

        $this->docViewDetector->expects(self::once())
            ->method('getView')
            ->willReturn('unknown');

        $this->compositeFormatter->formatOne($data);
    }

    public function testFormatForViewWithKnownFormatter(): void
    {
        $data = ['key' => 'value'];
        $formatterData = ['key' => 'formattedValue'];

        $this->docViewDetector->expects(self::once())
            ->method('getView')
            ->willReturn('test');

        $this->formatter->expects(self::once())
            ->method('format')
            ->with($data)
            ->willReturn($formatterData);

        self::assertEquals(
            $formatterData,
            $this->compositeFormatter->format($data)
        );
    }

    public function testFormatOneForViewWithKnownFormatter(): void
    {
        $data = $this->createMock(ApiDoc::class);
        $formatterData = ['key' => 'formattedValue'];

        $this->docViewDetector->expects(self::once())
            ->method('getView')
            ->willReturn('test');

        $this->formatter->expects(self::once())
            ->method('formatOne')
            ->with($data)
            ->willReturn($formatterData);

        self::assertEquals(
            $formatterData,
            $this->compositeFormatter->formatOne($data)
        );
    }
}
