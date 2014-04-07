<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Entity;

use Oro\Bundle\DashboardBundle\Configuration\ConfigurationLoader;
use Oro\Bundle\DashboardBundle\Configuration\ConfigurationManager;
use Oro\Bundle\DashboardBundle\Entity\DashboardWidget;

class ConfigurationManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityManager;

    protected function setUp()
    {
        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $repository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');

        $this->entityManager
            ->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($repository));
    }

    /**
     * @param string $name
     * @param array  $configuration
     *
     * @dataProvider configurationProvider
     */
    public function testSaveConfiguration($name, array $configuration)
    {
        $manager   = new ConfigurationManager($this->entityManager);
        $dashboard = $manager->saveConfiguration($name, $configuration);

        $this->assertEquals($name, $dashboard->getName());

        foreach ($dashboard->getWidgets() as $widget) {
            /* @var DashboardWidget $widget */
            $this->assertArrayHasKey($widget->getName(), $configuration[ConfigurationLoader::NODE_WIDGET]);
        }
    }

    /**
     * @return array
     */
    public function configurationProvider()
    {
        return [
            'new dashboard' => [
                'name'          => 'new dashboard',
                'configuration' => [
                    ConfigurationLoader::NODE_WIDGET => [
                        'new widget' => [
                            'position' => 10
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @expectedException \Oro\Bundle\DashboardBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Position for "widget" widget should not be empty
     */
    public function testSaveConfigurationFailed()
    {
        $configuration = [
            ConfigurationLoader::NODE_WIDGET => [
                'widget' => []
            ]
        ];

        $manager = new ConfigurationManager($this->entityManager);
        $manager->saveConfiguration('dashboard', $configuration);
    }
}
