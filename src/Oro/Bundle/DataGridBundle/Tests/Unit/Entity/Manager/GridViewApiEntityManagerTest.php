<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Entity\Manager;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\DataGridBundle\Entity\Manager\GridViewApiEntityManager;
use Oro\Bundle\DataGridBundle\Entity\Manager\GridViewManager;
use Oro\Bundle\DataGridBundle\Extension\GridViews\GridViewsExtension;
use Oro\Bundle\DataGridBundle\Extension\GridViews\View;

class GridViewApiEntityManagerTest extends \PHPUnit_Framework_TestCase
{
    const CLASS_NAME = "Oro\\Bundle\\DataGridBundle\\Entity\\GridView";
    /** @var   */
    protected $gridViewManager;

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    protected $om;

    /** @var  GridViewApiEntityManager */
    protected $gridViewApiEntityManager;

    public function setUp()
    {
        $this->om = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $metadata = new ClassMetadata(self::CLASS_NAME);
        $this->om->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($metadata);

        $this->gridViewManager = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Entity\Manager\GridViewManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->gridViewApiEntityManager = new GridViewApiEntityManager(
            self::CLASS_NAME,
            $this->om,
            $this->gridViewManager
        );
    }


    public function testGetViewSystemAll()
    {
        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $registry->expects($this->any())
            ->method('getRepository')
            ->willReturn($repo);

        $dataGridManager = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Manager')
            ->disableOriginalConstructor()
            ->getMock();

        $aclHelper = $this->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $gridViewManager = new GridViewManager(
            $aclHelper,
            $registry,
            $dataGridManager
        );

        $systemAllView = new View(GridViewsExtension::DEFAULT_VIEW_ID);
        $systemAllView->setGridName('Test');
        $view = $gridViewManager->getView('Test', 0, 'Test');


        $this->assertEquals($view, $systemAllView);
    }
}
