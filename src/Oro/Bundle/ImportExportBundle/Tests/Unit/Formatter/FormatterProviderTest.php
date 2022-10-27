<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Formatter;

use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ImportExportBundle\Formatter\FormatterProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FormatterProviderTest extends \PHPUnit\Framework\TestCase
{
    private array $formatters = ['exist_alias' => 'exist_formatter'];
    private array $typeFormatters = ['test_format_type' => ['test_type' => 'test_formatter']];

    /** @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $container;

    /** @var FormatterProvider */
    private $formatter;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);

        $this->formatter = new FormatterProvider($this->container, $this->formatters, $this->typeFormatters);
    }

    public function testGetFormatterByAlias()
    {
        $testTypeFormatter = new \stdClass();
        $this->setContainerMock('exist_formatter', $testTypeFormatter);

        $this->assertEquals($testTypeFormatter, $this->formatter->getFormatterByAlias('exist_alias'));

        //test already created formatter will be stored in provider
        $this->assertEquals($testTypeFormatter, $this->formatter->getFormatterByAlias('exist_alias'));
    }

    public function testGetFormatterByAliasWithNotExistsAlias()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The formatter is not found by "non_exist_alias" alias.');

        $this->formatter->getFormatterByAlias('non_exist_alias');
    }

    public function testGetFormatterFor()
    {
        $testTypeFormatter = new \stdClass();
        $this->setContainerMock('test_formatter', $testTypeFormatter);
        $this->assertEquals($testTypeFormatter, $this->formatter->getFormatterFor('test_format_type', 'test_type'));

        // test already created formatter will be stored in provider
        $this->assertEquals($testTypeFormatter, $this->formatter->getFormatterFor('test_format_type', 'test_type'));

        // test not exists formatter
        $this->assertNull($this->formatter->getFormatterFor('non_exist_type', 'test_type'));
    }

    private function setContainerMock(string $id, \stdClass $testTypeFormatter): void
    {
        $this->container->expects($this->once())
            ->method('has')
            ->with($id)
            ->willReturn(true);
        $this->container->expects($this->once())
            ->method('get')
            ->with($id)
            ->willReturn($testTypeFormatter);
    }
}
