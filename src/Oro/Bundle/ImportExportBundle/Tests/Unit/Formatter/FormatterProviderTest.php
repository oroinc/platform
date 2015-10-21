<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Formatter;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Oro\Bundle\ImportExportBundle\Formatter\FormatterProvider;

class FormatterProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FormatterProvider
     */
    protected $formatter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ContainerInterface
     */
    protected $container;

    /** @var array */
    protected $formatters = ['exist_alias' => 'exist_formatter'];

    /** @var array */
    protected $typeFormatters = ['test_format_type' => ['test_type' => 'test_formatter']];

    protected function setUp()
    {
        $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $this->formatter = new FormatterProvider($this->container, $this->formatters, $this->typeFormatters);
    }

    public function testGetFormatter()
    {
        $testTypeFormatter = new \stdClass();
        $this->setContainerMock('exist_formatter', $testTypeFormatter);

        $this->assertEquals($testTypeFormatter, $this->formatter->getFormatterByAlias('exist_alias'));

        //test already created formatter will be stored in provider
        $this->assertEquals($testTypeFormatter, $this->formatter->getFormatterByAlias('exist_alias'));
        $this->setExpectedException(
            'Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException',
            'The formatter is not found by "non_exist_alias" alias.'
        );
        $this->formatter->getFormatterByAlias('non_exist_alias');
    }

    public function testGetFormatterFor()
    {
        $testTypeFormatter = new \stdClass();
        $this->setContainerMock('test_formatter', $testTypeFormatter);
        $this->assertEquals($testTypeFormatter, $this->formatter->getFormatterFor('test_format_type', 'test_type'));

        // test already created formatter will be stored in provider
        $this->assertEquals($testTypeFormatter, $this->formatter->getFormatterFor('test_format_type', 'test_type'));

        $this->setExpectedException(
            'Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException',
            'No available formatters for "non_exist_type" format_type and "test_type" data_type.'
        );
        $this->formatter->getFormatterFor('non_exist_type', 'test_type');
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
