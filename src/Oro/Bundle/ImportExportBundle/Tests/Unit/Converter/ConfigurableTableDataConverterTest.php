<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Converter;

use Oro\Bundle\ImportExportBundle\Converter\ConfigurableTableDataConverter;

class ConfigurableTableDataConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    protected $fields = array(
        'RootEntity' => array(
            array(
                'name' => 'created',
                'label' => 'Created',
            ),
            array(
                'name' => 'name',
                'label' => 'Name',
            ),
            array(
                'name' => 'id',
                'label' => 'ID',
            ),
            array(
                'name' => 'description',
                'label' => 'Description',
            ),
        )
    );

    /**
     * @var array
     */
    protected $config = array(
        'RootEntity' => array(
            'id' => array(
                'order' => 10
            ),
            'name' => array(
                'header' => 'Entity Name',
                'identity' => true,
                'order' => 20,
            ),
            'description' => array(
                'excluded' => true,
            ),
        ),
    );

    /**
     * @var ConfigurableTableDataConverter
     */
    protected $converter;

    protected function setUp()
    {
        $fieldProvider = $this->prepareFieldProvider();
        $configProvider = $this->prepareConfigProvider();
        $this->converter = new ConfigurableTableDataConverter($fieldProvider, $configProvider);
    }

    /**
     * @expectedException \Oro\Bundle\ImportExportBundle\Exception\LogicException
     * @expectedExceptionMessage Entity class for data converter is not specified
     */
    public function testAssertEntityName()
    {
        $this->converter->convertToExportFormat(array());
    }

    /**
     * @return array
     */
    public function exportDataProvider()
    {
        return array(
            'empty scalar value' => array(
                'entityName' => 'RootEntity',
                'input' => array(),
                'expected' => array(
                    'ID' => '',
                    'Entity Name' => '',
                    'Created' => '',
                ),
            ),
            'full scalar value' => array(
                'entityName' => 'RootEntity',
                'input' => array(
                    'id' => 42,
                    'name' => 'qwerty',
                    'created' => '2012-12-12 12:12:12'
                ),
                'expected' => array(
                    'ID' => '42',
                    'Entity Name' => 'qwerty',
                    'Created' => '2012-12-12 12:12:12',
                ),
            ),
        );
    }

    /**
     * @param string $entityName
     * @param array $input
     * @param array $expected
     * @dataProvider exportDataProvider
     */
    public function testExport($entityName, array $input, array $expected)
    {
        $this->converter->setEntityName($entityName);
        $this->assertSame($expected, $this->converter->convertToExportFormat($input));
    }

    /**
     * @return array
     */
    public function importDataProvider()
    {
        return array(
            'empty scalar value' => array(
                'entityName' => 'RootEntity',
                'input' => array(),
                'expected' => array(),
            ),
            'full scalar value' => array(
                'entityName' => 'RootEntity',
                'input' => array(
                    'ID' => '42',
                    'Entity Name' => 'qwerty',
                    'Created' => '2012-12-12 12:12:12',
                ),
                'expected' => array(
                    'id' => '42',
                    'name' => 'qwerty',
                    'created' => '2012-12-12 12:12:12'
                ),
            ),
        );
    }

    /**
     * @param string $entityName
     * @param array $input
     * @param array $expected
     * @dataProvider importDataProvider
     */
    public function testImport($entityName, array $input, array $expected)
    {
        $this->converter->setEntityName($entityName);
        $this->assertSame($expected, $this->converter->convertToImportFormat($input));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function prepareFieldProvider()
    {
        $fieldProvider = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\EntityFieldProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $fieldProvider->expects($this->any())->method('getFields')->with($this->isType('string'))
            ->will(
                $this->returnCallback(
                    function ($entityName) {
                        return isset($this->fields[$entityName]) ? $this->fields[$entityName] : array();
                    }
                )
            );

        return $fieldProvider;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function prepareConfigProvider()
    {
        $configProvider = $this->getMock('Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface');
        $configProvider->expects($this->any())->method('hasConfig')
            ->with($this->isType('string'), $this->isType('string'))
            ->will(
                $this->returnCallback(
                    function ($entityName, $fieldName) {
                        return isset($this->config[$entityName][$fieldName]);
                    }
                )
            );
        $configProvider->expects($this->any())->method('getConfig')
            ->with($this->isType('string'), $this->isType('string'))
            ->will(
                $this->returnCallback(
                    function ($entityName, $fieldName) {
                        $entityConfig = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
                        $entityConfig->expects($this->any())->method('has')->with($this->isType('string'))
                            ->will(
                                $this->returnCallback(
                                    function ($parameter) use ($entityName, $fieldName) {
                                        return isset($this->config[$entityName][$fieldName][$parameter]);
                                    }
                                )
                            );
                        $entityConfig->expects($this->any())->method('get')->with($this->isType('string'))
                            ->will(
                                $this->returnCallback(
                                    function ($parameter) use ($entityName, $fieldName) {
                                        return isset($this->config[$entityName][$fieldName][$parameter])
                                            ? $this->config[$entityName][$fieldName][$parameter]
                                            : null;
                                    }
                                )
                            );

                        return $entityConfig;
                    }
                )
            );

        return $configProvider;
    }
}
