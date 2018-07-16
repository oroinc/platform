<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Entity\Manager;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\DataGridBundle\Entity\GridView;
use Oro\Bundle\DataGridBundle\Entity\GridViewUser;
use Oro\Bundle\DataGridBundle\Entity\Manager\GridViewApiEntityManager;
use Oro\Bundle\DataGridBundle\Entity\Manager\GridViewManager;
use Oro\Bundle\DataGridBundle\Entity\Repository\GridViewRepository;
use Oro\Bundle\DataGridBundle\Entity\Repository\GridViewUserRepository;
use Oro\Bundle\DataGridBundle\Extension\Board\RestrictionManager;
use Oro\Bundle\DataGridBundle\Extension\GridViews\View;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Translation\TranslatorInterface;

class GridViewManagerTest extends \PHPUnit\Framework\TestCase
{
    const GRID_VIEW_CLASS_NAME = 'GridViewClassName';
    const GRID_VIEW_USER_CLASS_NAME = 'GridViewUserClassName';

    /** @var GridViewManager */
    protected $gridViewManager;

    /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject */
    protected $gridViewRepository;

    /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject */
    protected $gridViewUserRepository;

    /** @var  User */
    protected $user;

    /** @var Manager|\PHPUnit\Framework\MockObject\MockObject */
    protected $dataGridManager;

    /** @var RestrictionManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $restrictionManager;

    /** @var  GridViewApiEntityManager */
    protected $gridViewApiEntityManager;

    public function setUp()
    {
        $this->user = new User();
        $this->user->setUsername('username');

        /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject $aclHelper */
        $aclHelper = $this->createMock(AclHelper::class);

        $this->gridViewRepository = $this->createMock(GridViewRepository::class);
        $this->gridViewUserRepository = $this->createMock(GridViewUserRepository::class);

        /** @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject $manager */
        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->any())
            ->method('getRepository')
            ->willReturnMap(
                [
                    [self::GRID_VIEW_CLASS_NAME, $this->gridViewRepository],
                    [self::GRID_VIEW_USER_CLASS_NAME, $this->gridViewUserRepository]
                ]
            );

        /** @var Registry|\PHPUnit\Framework\MockObject\MockObject $registry */
        $registry = $this->createMock(Registry::class);
        $registry->expects($this->any())->method('getManagerForClass')->willReturn($manager);

        $this->dataGridManager = $this->createMock(Manager::class);

        $this->restrictionManager = $this->createMock(RestrictionManager::class);

        $this->gridViewManager = new GridViewManager(
            $aclHelper,
            $registry,
            $this->dataGridManager,
            $this->restrictionManager
        );
        $this->gridViewManager->setGridViewClassName(self::GRID_VIEW_CLASS_NAME);
        $this->gridViewManager->setGridViewUserClassName(self::GRID_VIEW_USER_CLASS_NAME);
    }

    public function testGetDefaultView()
    {
        $systemView = new View('view1');
        $systemView->setDefault(true);

        /** @var TranslatorInterface|\ $translator */
        $translator = $this->createMock(TranslatorInterface::class);
        $viewList = new ViewListStub($translator);
        
        $config = $this->createMock(DatagridConfiguration::class);
        $config->expects($this->once())->method('offsetGetOr')->with('views_list', false)->willReturn($viewList);

        $this->dataGridManager->expects($this->once())->method('getConfigurationForGrid')->willReturn($config);

        $this->assertEquals($systemView, $this->gridViewManager->getDefaultView($this->user, 'sales-opportunity-grid'));
    }
}
