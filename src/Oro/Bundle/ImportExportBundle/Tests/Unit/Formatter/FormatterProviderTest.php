<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Formatter;

use Oro\Bundle\ImportExportBundle\Tests\Unit\Fixtures\TestTypeFormatter;
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
    protected $typeFormatters = ['test_type' => 'test_formatter'];

    protected function setUp()
    {
        $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $this->formatter = new FormatterProvider($this->container, $this->formatters, $this->typeFormatters);
    }

    public function testGetFormatter()
    {
        $testTypeFormatter = new TestTypeFormatter();
        $this->setContainerMock('exist_formatter', $testTypeFormatter);

        $this->assertEquals($testTypeFormatter, $this->formatter->getFormatter('exist_alias'));

        //test already created formatter will be stored in provider
        $this->assertEquals($testTypeFormatter, $this->formatter->getFormatter('exist_alias'));
        $this->setExpectedException(
            'Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException',
            'The formatter alias "non_exist_alias" is not registered with the provider.'
        );
        $this->formatter->getFormatter('non_exist_alias');
    }

    public function testGetFormatterFor()
    {
        $testTypeFormatter = new TestTypeFormatter();
        $this->setContainerMock('test_formatter', $testTypeFormatter);
        $this->assertEquals($testTypeFormatter, $this->formatter->getFormatterFor('test_type'));

        // test already created formatter will be stored in provider
        $this->assertEquals($testTypeFormatter, $this->formatter->getFormatterFor('test_type'));

        $this->setExpectedException(
            'Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException',
            'No available formatters for "non_exist_type" type.'
        );
        $this->formatter->getFormatterFor('non_exist_type');
    }

    protected function setContainerMock($id, TestTypeFormatter $testTypeFormatter)
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
