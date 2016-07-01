<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Entity\Manager;

use Oro\Bundle\DataGridBundle\Entity\Manager\GridViewApiEntityManager;
use Oro\Bundle\DataGridBundle\Entity\Manager\GridViewManager;
use Oro\Bundle\DataGridBundle\Extension\GridViews\View;
use Oro\Bundle\UserBundle\Entity\User;

class GridViewManagerTest extends \PHPUnit_Framework_TestCase
{
    const CLASS_NAME = "Oro\\Bundle\\DataGridBundle\\Entity\\GridView";
    /** @var GridViewManager */
    protected $gridViewManager;

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    protected $om;

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    protected $repository;

    /** @var  User */
    protected $user;

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    protected $dataGridManager;

    /** @var  GridViewApiEntityManager */
    protected $gridViewApiEntityManager;

    public function setUp()
    {
        $this->user = new User();
        $this->user->setUsername('username');

        $aclHelper = $this->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $registry->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->repository);

        $this->dataGridManager = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Manager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->gridViewManager = new GridViewManager(
            $aclHelper,
            $registry,
            $this->dataGridManager
        );
    }

    public function testGetDefaultView()
    {
        $systemView = new View('view1');
        $systemView->setDefault(true);

        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $viewList = new ViewListStub($translator);
        
        $config = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration')
            ->disableOriginalConstructor()
            ->getMock();

        $config->expects($this->once())
            ->method('offsetGetOr')
            ->with('views_list', false)
            ->willReturn($viewList);

        $this->dataGridManager->expects($this->once())
            ->method('getConfigurationForGrid')
            ->willReturn($config);

        $defaultView = $this->gridViewManager->getDefaultView($this->user, 'sales-opportunity-grid');
        $this->assertEquals($systemView, $defaultView);
    }
}
