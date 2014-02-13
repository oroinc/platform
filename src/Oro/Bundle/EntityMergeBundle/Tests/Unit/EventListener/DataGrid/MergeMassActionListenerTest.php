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
    private $object;

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

        $this->object = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Common\Object')
            ->disableOriginalConstructor()
            ->getMock();
        $this->buildBefore->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($this->object));

        $this->target = new MergeMassActionListener($this->metadataRegistry);
    }

    public function testOnBuildBeforeShouldUnsetMergeMassActionIfEnableConfigReturnFalse()
    {
        $this->init();
        $this->entityMetadata->expects($this->once())
            ->method('get')
            ->with($this->equalTo('enable'))
            ->will($this->returnValue(false));
        $this->object->expects($this->once())
            ->method('offsetUnsetByPath')
            ->with($this->equalTo('[mass_actions][merge]'));

        $this->target->onBuildBefore($this->buildBefore);
    }

    public function testOnBuildBeforeShouldNotUnsetMergeMassActionIfEnableConfigReturnTrue()
    {
        $this->initMetadataRegistry();
        $this->initObject();
        $this->entityMetadata->expects($this->once())
            ->method('get')
            ->with($this->equalTo('enable'))
            ->will($this->returnValue(true));
        $this->object->expects($this->never())
            ->method('offsetUnsetByPath')
            ->withAnyParameters();


        $this->target->onBuildBefore($this->buildBefore);
    }

    public function testOnBuildBeforeDoesNotCallGetEntityMetadataIfMergeKeyDoesNotExistInConfig()
    {
        $this->initObject(array('mass_actions' => array()));
        $this->metadataRegistry->expects($this->never())
            ->method('getEntityMetadata')
            ->withAnyParameters();

        $this->target->onBuildBefore($this->buildBefore);
    }

    public function testOnBuildBeforeDoesNotCallGetEntityMetadataIfEntityNameIsEmpty()
    {
        $this->initObject(array('mass_actions' => array('merge' => array('entity_name' => ''))));
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

    protected function initObject($offsetResult = null)
    {
        $offsetResult = $offsetResult === null ? $this->config['mass_actions'] : $offsetResult;

        $this->object->expects($this->any())
            ->method('offsetGet')
            ->with($this->equalTo('mass_actions'))
            ->will($this->returnValue($offsetResult));
    }

    protected function init()
    {
        $this->initMetadataRegistry();
        $this->initObject();
    }
}
