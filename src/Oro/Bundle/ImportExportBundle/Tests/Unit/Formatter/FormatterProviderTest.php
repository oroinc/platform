<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Formatter;

use Oro\Bundle\ImportExportBundle\Formatter\FormatterProvider;
use Oro\Bundle\ImportExportBundle\Formatter\TypeFormatterInterface;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FormatterProviderTest extends TestCase
{
    private TypeFormatterInterface&MockObject $typeFormatter;
    private FormatterProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->typeFormatter = $this->createMock(TypeFormatterInterface::class);

        $container = TestContainerBuilder::create()
            ->add('test_formatter', $this->typeFormatter)
            ->getContainer($this);

        $this->provider = new FormatterProvider(
            $container,
            ['test_format_type' => ['test_type' => 'test_formatter']]
        );
    }

    public function testGetFormatterFor(): void
    {
        self::assertSame($this->typeFormatter, $this->provider->getFormatterFor('test_format_type', 'test_type'));

        // test that already created formatter is cached
        self::assertSame($this->typeFormatter, $this->provider->getFormatterFor('test_format_type', 'test_type'));

        // test not existing formatter
        self::assertNull($this->provider->getFormatterFor('non_exist_type', 'test_type'));
    }
}
