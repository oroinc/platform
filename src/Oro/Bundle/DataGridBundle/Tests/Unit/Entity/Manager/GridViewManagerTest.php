<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Entity\Manager;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\DataGridBundle\Entity\Manager\GridViewManager;
use Oro\Bundle\DataGridBundle\Entity\Repository\GridViewRepository;
use Oro\Bundle\DataGridBundle\Entity\Repository\GridViewUserRepository;
use Oro\Bundle\DataGridBundle\Extension\Board\RestrictionManager;
use Oro\Bundle\DataGridBundle\Extension\GridViews\View;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Contracts\Translation\TranslatorInterface;

class GridViewManagerTest extends \PHPUnit\Framework\TestCase
{
    private const GRID_VIEW_CLASS_NAME = 'GridViewClassName';
    private const GRID_VIEW_USER_CLASS_NAME = 'GridViewUserClassName';

    /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $gridViewRepository;

    /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $gridViewUserRepository;

    /** @var User */
    private $user;

    /** @var Manager|\PHPUnit\Framework\MockObject\MockObject */
    private $dataGridManager;

    /** @var RestrictionManager|\PHPUnit\Framework\MockObject\MockObject */
    private $restrictionManager;

    /** @var GridViewManager */
    private $gridViewManager;

    protected function setUp(): void
    {
        $this->user = new User();
        $this->user->setUsername('username');

        $this->dataGridManager = $this->createMock(Manager::class);
        $this->restrictionManager = $this->createMock(RestrictionManager::class);
        $this->gridViewRepository = $this->createMock(GridViewRepository::class);
        $this->gridViewUserRepository = $this->createMock(GridViewUserRepository::class);

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [self::GRID_VIEW_CLASS_NAME, $this->gridViewRepository],
                [self::GRID_VIEW_USER_CLASS_NAME, $this->gridViewUserRepository]
            ]);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($manager);

        $this->gridViewManager = new GridViewManager(
            $this->createMock(AclHelper::class),
            $doctrine,
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

        $translator = $this->createMock(TranslatorInterface::class);
        $viewList = new ViewListStub($translator);

        $config = $this->createMock(DatagridConfiguration::class);
        $config->expects($this->once())
            ->method('offsetGetOr')
            ->with('views_list', false)
            ->willReturn($viewList);

        $this->dataGridManager->expects($this->once())
            ->method('getConfigurationForGrid')
            ->willReturn($config);

        $this->assertEquals(
            $systemView,
            $this->gridViewManager->getDefaultView($this->user, 'sales-opportunity-grid')
        );
    }
}
