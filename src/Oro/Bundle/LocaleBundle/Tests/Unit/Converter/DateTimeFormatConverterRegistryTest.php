<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Converter;

use Oro\Bundle\LocaleBundle\Converter\DateTimeFormatConverterInterface;
use Oro\Bundle\LocaleBundle\Converter\DateTimeFormatConverterRegistry;
use Oro\Component\Testing\Unit\TestContainerBuilder;

class DateTimeFormatConverterRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var DateTimeFormatConverterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $converter1;

    /** @var DateTimeFormatConverterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $converter2;

    /** @var DateTimeFormatConverterRegistry */
    private $registry;

    protected function setUp(): void
    {
        $this->converter1 = $this->createMock(DateTimeFormatConverterInterface::class);
        $this->converter2 = $this->createMock(DateTimeFormatConverterInterface::class);

        $container = TestContainerBuilder::create()
            ->add('test1', $this->converter1)
            ->add('test2', $this->converter2)
            ->getContainer($this);

        $this->registry = new DateTimeFormatConverterRegistry(
            ['test1', 'test2'],
            $container
        );
    }

    public function testGetFormatConverter()
    {
        $this->assertSame($this->converter1, $this->registry->getFormatConverter('test1'));
        $this->assertSame($this->converter2, $this->registry->getFormatConverter('test2'));
    }

    public function testGetFormatConverterWhenConverterNotExists()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Format converter with name "not_existing" is not exist');

        $this->registry->getFormatConverter('not_existing');
    }

    public function testGetFormatConverterWhenConverterNotExistsAndExistingConverterWasRequestedBefore()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Format converter with name "not_existing" is not exist');

        $this->assertSame($this->converter1, $this->registry->getFormatConverter('test1'));
        $this->registry->getFormatConverter('not_existing');
    }

    public function getFormatConvertersWhenNoAnyConverterWasRequestedBefore()
    {
        $this->assertEquals(
            ['test1' => $this->converter1, 'test2' => $this->converter2],
            $this->registry->getFormatConverters()
        );
    }

    public function getFormatConvertersWhenSomeConverterWasRequestedBefore()
    {
        $this->assertSame($this->converter1, $this->registry->getFormatConverter('test1'));
        $this->assertEquals(
            ['test1' => $this->converter1, 'test2' => $this->converter2],
            $this->registry->getFormatConverters()
        );
    }
}
