<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\EventListener\DataGrid;

use Oro\Bundle\EntityMergeBundle\EventListener\DataGrid\MergeMassActionListener;

class MergeMassActionListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MergeMassActionListener
     */
    private $target;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $entityMetadata;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $buildBefore;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $datagridConfig;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $metadataRegistry;

    /**
     * @var string
     */
    private $entityName;

    /**
     * @var array
     */
    private $config;

    public function setUp()
    {
        $this->metadataRegistry = $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\MetadataRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityMetadata = $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $this->buildBefore = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Event\BuildBefore')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityName = 'testEntityName';
        $this->config = array('mass_actions' => array('merge' => array('entity_name' => $this->entityName)));

        $this->datagridConfig = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration')
            ->disableOriginalConstructor()
            ->getMock();

        $this->buildBefore->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($this->datagridConfig));

        $this->target = new MergeMassActionListener($this->metadataRegistry);
    }

    public function testOnBuildUnsetMergeMassAction()
    {
        $this->init();

        $this->entityMetadata->expects($this->once())
            ->method('is')
            ->with('enable', true)
            ->will($this->returnValue(false));

        $this->datagridConfig->expects($this->once())
            ->method('offsetUnsetByPath')
            ->with('[mass_actions][merge]');

        $this->target->onBuildBefore($this->buildBefore);
    }

    public function testOnBuildNotUnsetMergeMass()
    {
        $this->init();

        $this->entityMetadata->expects($this->once())
            ->method('is')
            ->with('enable', true)
            ->will($this->returnValue(true));

        $this->datagridConfig->expects($this->never())
            ->method('offsetUnsetByPath')
            ->withAnyParameters();


        $this->target->onBuildBefore($this->buildBefore);
    }

    public function testOnBuildBeforeSkipsForEmptyMassActions()
    {
        $this->initDatagridConfig(array('mass_actions' => array()));

        $this->metadataRegistry->expects($this->never())
            ->method('getEntityMetadata')
            ->withAnyParameters();

        $this->target->onBuildBefore($this->buildBefore);
    }

    public function testOnBuildBeforeForEmptyEntityName()
    {
        $this->initDatagridConfig(array('mass_actions' => array('merge' => array('entity_name' => ''))));

        $this->metadataRegistry->expects($this->never())
            ->method('getEntityMetadata')
            ->withAnyParameters();

        $this->target->onBuildBefore($this->buildBefore);
    }

    protected function initMetadataRegistry()
    {
        $this->metadataRegistry->expects($this->any())
            ->method('getEntityMetadata')
            ->will($this->returnValue($this->entityMetadata));
    }

    protected function initDatagridConfig($offsetResult = null)
    {
        $rawConfig = $this->config;
        $offsetResult = $offsetResult === null ? $this->config['mass_actions'] : $offsetResult;

        $this->datagridConfig->expects($this->any())
            ->method('offsetExists')
            ->with('mass_actions')
            ->will(
                $this->returnCallback(
                    function ($offset) use ($rawConfig) {
                        return isset($rawConfig[$offset]);
                    }
                )
            );

        $this->datagridConfig->expects($this->any())
            ->method('offsetGet')
            ->with('mass_actions')
            ->will($this->returnValue($offsetResult));

        $this->datagridConfig->expects($this->any())
            ->method('offsetGet')
            ->with('mass_actions')
            ->will($this->returnValue($offsetResult));
    }

    protected function init()
    {
        $this->initMetadataRegistry();
        $this->initDatagridConfig();
    }
}
