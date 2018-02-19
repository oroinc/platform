<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc\Parser;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\Formatter\FormatterInterface;

use Oro\Bundle\ApiBundle\ApiDoc\CompositeFormatter;
use Oro\Bundle\ApiBundle\ApiDoc\RestDocViewDetector;

class CompositeFormatterTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|RestDocViewDetector */
    protected $docViewDetector;

    /** @var \PHPUnit_Framework_MockObject_MockObject|FormatterInterface */
    protected $formatter;

    /** @var CompositeFormatter */
    protected $compositeFormatter;

    protected function setUp()
    {
        $this->docViewDetector = $this->createMock(RestDocViewDetector::class);
        $this->formatter = $this->createMock(FormatterInterface::class);

        $this->compositeFormatter = new CompositeFormatter($this->docViewDetector);
        $this->compositeFormatter->addFormatter('test', $this->formatter);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Cannot find formatter for "unknown" API view.
     */
    public function testFormatForViewWithUnknownFormatter()
    {
        $data = ['key' => 'value'];

        $this->docViewDetector->expects(self::once())
            ->method('getView')
            ->willReturn('unknown');

        $this->compositeFormatter->format($data);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Cannot find formatter for "unknown" API view.
     */
    public function testFormatOneForViewWithUnknownFormatter()
    {
        $data = $this->createMock(ApiDoc::class);

        $this->docViewDetector->expects(self::once())
            ->method('getView')
            ->willReturn('unknown');

        $this->compositeFormatter->formatOne($data);
    }

    public function testFormatForViewWithKnownFormatter()
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

    public function testFormatOneForViewWithKnownFormatter()
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
