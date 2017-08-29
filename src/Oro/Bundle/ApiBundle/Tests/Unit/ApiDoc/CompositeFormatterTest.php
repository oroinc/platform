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
    protected $defaultFormatter;

    /** @var \PHPUnit_Framework_MockObject_MockObject|FormatterInterface */
    protected $anotherFormatter;

    /** @var CompositeFormatter */
    protected $compositeFormatter;

    protected function setUp()
    {
        $this->docViewDetector = $this->createMock(RestDocViewDetector::class);
        $this->defaultFormatter = $this->createMock(FormatterInterface::class);
        $this->anotherFormatter = $this->createMock(FormatterInterface::class);

        $this->compositeFormatter = new CompositeFormatter($this->docViewDetector);
        $this->compositeFormatter->addFormatter('', $this->defaultFormatter);
        $this->compositeFormatter->addFormatter('another', $this->anotherFormatter);
    }

    public function testFormatForDefaultView()
    {
        $data = ['key' => 'value'];
        $formatterData = ['key' => 'formattedValue'];

        $this->docViewDetector->expects(self::once())
            ->method('getView')
            ->willReturn('default');

        $this->defaultFormatter->expects(self::once())
            ->method('format')
            ->with($data)
            ->willReturn($formatterData);

        self::assertEquals(
            $formatterData,
            $this->compositeFormatter->format($data)
        );
    }

    public function testFormatOneForDefaultView()
    {
        $data = $this->createMock(ApiDoc::class);
        $formatterData = ['key' => 'formattedValue'];

        $this->docViewDetector->expects(self::once())
            ->method('getView')
            ->willReturn('default');

        $this->defaultFormatter->expects(self::once())
            ->method('formatOne')
            ->with($data)
            ->willReturn($formatterData);

        self::assertEquals(
            $formatterData,
            $this->compositeFormatter->formatOne($data)
        );
    }

    public function testFormatForViewWithOwnFormatter()
    {
        $data = ['key' => 'value'];
        $formatterData = ['key' => 'formattedValue'];

        $this->docViewDetector->expects(self::once())
            ->method('getView')
            ->willReturn('another');

        $this->anotherFormatter->expects(self::once())
            ->method('format')
            ->with($data)
            ->willReturn($formatterData);

        self::assertEquals(
            $formatterData,
            $this->compositeFormatter->format($data)
        );
    }

    public function testFormatOneForViewWithOwnFormatter()
    {
        $data = $this->createMock(ApiDoc::class);
        $formatterData = ['key' => 'formattedValue'];

        $this->docViewDetector->expects(self::once())
            ->method('getView')
            ->willReturn('another');

        $this->anotherFormatter->expects(self::once())
            ->method('formatOne')
            ->with($data)
            ->willReturn($formatterData);

        self::assertEquals(
            $formatterData,
            $this->compositeFormatter->formatOne($data)
        );
    }
}
