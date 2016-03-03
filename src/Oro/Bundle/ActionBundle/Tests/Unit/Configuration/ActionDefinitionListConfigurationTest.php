<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Configuration;

use Oro\Bundle\ActionBundle\Configuration\ActionDefinitionConfiguration;
use Oro\Bundle\ActionBundle\Configuration\ActionDefinitionListConfiguration;

class ActionDefinitionListConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ActionDefinitionConfiguration|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configuration;

    /**
     * @var ActionDefinitionListConfiguration
     */
    protected $listConfiguration;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->configuration = $this
            ->getMockBuilder('Oro\Bundle\ActionBundle\Configuration\ActionDefinitionConfiguration')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listConfiguration = new ActionDefinitionListConfiguration($this->configuration);
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider processValidConfigurationProvider
     */
    public function testProcessValidConfiguration(array $inputData, array $expectedData)
    {
        $this->configuration->expects($this->once())
            ->method('addNodes');

        $this->assertEquals(
            $expectedData,
            $this->listConfiguration->processConfiguration($inputData)
        );
    }

    /**
     * @param array $inputData
     *
     * @dataProvider processInvalidConfigurationProvider
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    public function testProcessInvalidConfiguration(array $inputData)
    {
        $this->listConfiguration->processConfiguration($inputData);
    }

    /**
     * @return array
     */
    public function processValidConfigurationProvider()
    {
        return [
            'min valid configuration' => [
                'input' => [
                ],
                'expected' => [
                ],
            ],
            'full valid configuration' => [
                'input' => [
                    'action1' => [
                    ],
                    'action2' => [
                    ],
                ],
                'expected' => [
                    'action1' => [
                    ],
                    'action2' => [
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function processInvalidConfigurationProvider()
    {
        return [
            'incorrect root' => [
                'input' => [
                    'actions' => 'not array value',
                ],
            ],
            'not array action' => [
                'input' => [
                    'actions' => [
                        'not array key'
                    ],
                ],
            ],
        ];
    }
}
