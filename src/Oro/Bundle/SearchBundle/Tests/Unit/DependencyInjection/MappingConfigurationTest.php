<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\SearchBundle\DependencyInjection\MappingConfiguration;
use Oro\Component\Testing\Unit\LoadTestCaseDataTrait;
use Symfony\Component\Config\Definition\Processor;

class MappingConfigurationTest extends \PHPUnit\Framework\TestCase
{
    use LoadTestCaseDataTrait;

    /**
     * @dataProvider processConfigurationDataProvider
     *
     * @param array $configs
     * @param array $expected
     */
    public function testProcessConfiguration(array $configs, array $expected)
    {
        $processor = new Processor();

        $this->assertEquals(
            $expected,
            $processor->processConfiguration(new MappingConfiguration(), $configs)
        );
    }

    /**
     * @return array
     */
    public function processConfigurationDataProvider()
    {
        return $this->getTestCaseData(__DIR__ . DIRECTORY_SEPARATOR . 'mapping_configuration');
    }
}
