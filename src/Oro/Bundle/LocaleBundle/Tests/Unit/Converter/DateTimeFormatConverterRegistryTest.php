<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Converter;

use Oro\Bundle\LocaleBundle\Converter\DateTimeFormatConverterInterface;
use Oro\Bundle\LocaleBundle\Converter\DateTimeFormatConverterRegistry;
use Oro\Component\Testing\Unit\TestContainerBuilder;

class DateTimeFormatConverterRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var DateTimeFormatConverterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $converter;

    /** @var DateTimeFormatConverterRegistry */
    private $registry;

    protected function setUp(): void
    {
        $this->converter = $this->createMock(DateTimeFormatConverterInterface::class);

        $container = TestContainerBuilder::create()
            ->add('test', $this->converter)
            ->getContainer($this);

        $this->registry = new DateTimeFormatConverterRegistry(
            ['test'],
            $container
        );
    }

    public function testGetFormatConverter()
    {
        $this->assertSame($this->converter, $this->registry->getFormatConverter('test'));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Format converter with name "not_existing" is not exist
     */
    public function testGetFormatConverterNotExistsException()
    {
        $this->registry->getFormatConverter('not_existing');
    }

    public function getFormatConverters()
    {
        $this->assertEquals(['test' => $this->converter], $this->registry->getFormatConverters());
    }
}
