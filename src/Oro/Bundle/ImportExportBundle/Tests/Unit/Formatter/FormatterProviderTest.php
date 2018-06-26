<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Formatter;

use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ImportExportBundle\Formatter\FormatterProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FormatterProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FormatterProvider
     */
    protected $formatter;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ContainerInterface
     */
    protected $container;

    /** @var array */
    protected $formatters = ['exist_alias' => 'exist_formatter'];

    /** @var array */
    protected $typeFormatters = ['test_format_type' => ['test_type' => 'test_formatter']];

    protected function setUp()
    {
        $this->container = $this->createMock('Symfony\Component\DependencyInjection\ContainerInterface');
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

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The formatter is not found by "non_exist_alias" alias.
     */
    public function testGetFormatterByAliasWithNotExistsAlias()
    {
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

    protected function setContainerMock($id, \stdClass $testTypeFormatter)
    {
        $this->container
            ->expects($this->at(0))
            ->method('has')
            ->with($id)
            ->willReturn(true);
        $this->container
            ->expects($this->at(1))
            ->method('get')
            ->with($id)
            ->willReturn($testTypeFormatter);
    }
}
