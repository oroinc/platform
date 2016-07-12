<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\FieldAcl;

use Symfony\Component\Config\Definition\Processor;

use Oro\Bundle\DataGridBundle\Extension\FieldAcl\Configuration;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    protected $configuration;

    protected function setUp()
    {
        $this->configuration = new Configuration();
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Unrecognized option "a" under "fields_acl"
     */
    public function testValidateWrongArrayData()
    {
        $config = ['fields_acl' => ['a' => 'b']];
        $this->validateConfiguration($this->configuration, $config);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Invalid type for path "fields_acl
     */
    public function testValidateWrongNonArrayData()
    {
        $config = ['fields_acl' => 's'];
        $this->validateConfiguration($this->configuration, $config);
    }

    public function testValidateWithFullData()
    {
        $config = ['fields_acl' =>
           [
               'columns' => [
                   'first' => null,
                   'second' => false,
                   'third' => true,
                   'fourth' => ['data_name' => 'a.fourth'],
                   'fifth' => ['data_name' => 'a.fifth', 'disabled' => true],
                   'sixth' => ['data_name' => 'a.sixth', 'disabled' => false],
               ]
           ]
        ];

        $resultConfig = $this->validateConfiguration($this->configuration, $config);
        $this->assertEquals(
            [
                'columns' => [
                    'first' => ['disabled' => true],
                    'second' => ['disabled' => true],
                    'third' => ['disabled' => false],
                    'fourth' => ['data_name' => 'a.fourth', 'disabled' => false],
                    'fifth' => ['data_name' => 'a.fifth', 'disabled' => true],
                    'sixth' => ['data_name' => 'a.sixth', 'disabled' => false],
                ]
            ],
            $resultConfig
        );
    }

    /**
     * Validate configuration
     *
     * @param Configuration $configuration
     * @param               $config
     *
     * @return array
     */
    protected function validateConfiguration(Configuration $configuration, $config)
    {
        $processor = new Processor();
        return $processor->processConfiguration(
            $configuration,
            $config
        );
    }
}
