<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\FieldAcl;

use Oro\Bundle\DataGridBundle\Extension\FieldAcl\Configuration;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    private function processConfiguration(array $config): array
    {
        return (new Processor())->processConfiguration(new Configuration(), $config);
    }

    public function testValidateWrongArrayData()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Unrecognized option "a" under "fields_acl"');

        $config = ['fields_acl' => ['a' => 'b']];
        $this->processConfiguration($config);
    }

    public function testValidateWrongNonArrayData()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid type for path "fields_acl');

        $config = ['fields_acl' => 's'];
        $this->processConfiguration($config);
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

        $resultConfig = $this->processConfiguration($config);
        $this->assertEquals(
            [
                'columns' => [
                    'first' => ['disabled' => false],
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
}
