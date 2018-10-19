<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\EventListener\DataGrid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityConfigBundle\Config\Config as EntityConfig;
use Oro\Bundle\EntityMergeBundle\EventListener\DataGrid\MergeMassActionListener;

class MergeMassActionListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MergeMassActionListener
     */
    private $mergeMassActionListener;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $entityConfigProvider;

    protected function setUp()
    {
        $this->entityConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mergeMassActionListener = new MergeMassActionListener($this->entityConfigProvider);
    }

    public function testOnBuildUnsetMergeMassAction()
    {
        $entityName = 'testEntityName';
        $datagridConfig = DatagridConfiguration::create(
            ['mass_actions' => ['merge' => ['entity_name' => $entityName]]]
        );
        $entityConfig = $this->getEntityConfig();

        $this->entityConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($entityName)
            ->willReturn($entityConfig);

        $event = $this->getBuildBeforeEvent($datagridConfig);
        $this->mergeMassActionListener->onBuildBefore($event);

        $this->assertEquals(
            ['mass_actions' => []],
            $datagridConfig->toArray()
        );
    }

    public function testOnBuildNotUnsetMergeMass()
    {
        $entityName = 'testEntityName';
        $datagridConfig = DatagridConfiguration::create(
            ['mass_actions' => ['merge' => ['entity_name' => $entityName]]]
        );
        $entityConfig = $this->getEntityConfig(['enable' => true]);

        $this->entityConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($entityName)
            ->willReturn($entityConfig);

        $event = $this->getBuildBeforeEvent($datagridConfig);
        $this->mergeMassActionListener->onBuildBefore($event);

        $this->assertEquals(
            ['mass_actions' => ['merge' => ['entity_name' => $entityName]]],
            $datagridConfig->toArray()
        );
    }

    public function testOnBuildBeforeSkipsForEmptyMassActions()
    {
        $datagridConfig = DatagridConfiguration::create(
            ['mass_actions' => []]
        );

        $this->entityConfigProvider->expects($this->never())
            ->method('getConfig');

        $event = $this->getBuildBeforeEvent($datagridConfig);
        $this->mergeMassActionListener->onBuildBefore($event);
    }

    public function testOnBuildBeforeForEmptyEntityName()
    {
        $datagridConfig = DatagridConfiguration::create(
            ['mass_actions' => ['merge' => ['entity_name' => '']]]
        );

        $this->entityConfigProvider->expects($this->never())
            ->method('getConfig');

        $event = $this->getBuildBeforeEvent($datagridConfig);
        $this->mergeMassActionListener->onBuildBefore($event);
    }

    /**
     * @param DatagridConfiguration $datagridConfig
     *
     * @return BuildBefore
     */
    protected function getBuildBeforeEvent(DatagridConfiguration $datagridConfig)
    {
        return new BuildBefore(
            $this->createMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface'),
            $datagridConfig
        );
    }

    /**
     * @param array $values
     *
     * @return EntityConfig
     */
    protected function getEntityConfig(array $values = [])
    {
        return new EntityConfig(
            $this->createMock('Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface'),
            $values
        );
    }
}
