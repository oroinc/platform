<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Entity\Manager;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\DataGridBundle\Entity\GridView;
use Oro\Bundle\DataGridBundle\Entity\Manager\GridViewApiEntityManager;
use Oro\Bundle\DataGridBundle\Entity\Manager\GridViewManager;
use Oro\Bundle\DataGridBundle\Extension\Board\RestrictionManager;
use Oro\Bundle\DataGridBundle\Extension\GridViews\GridViewsExtension;
use Oro\Bundle\DataGridBundle\Extension\GridViews\View;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class GridViewApiEntityManagerTest extends \PHPUnit\Framework\TestCase
{
    const CLASS_NAME = GridView::class;

    /** @var GridViewManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $gridViewManager;

    /** @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $om;

    /** @var GridViewApiEntityManager */
    protected $gridViewApiEntityManager;

    protected function setUp(): void
    {
        $this->om = $this->createMock(ObjectManager::class);

        $metadata = new ClassMetadata(self::CLASS_NAME);
        $this->om->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($metadata);

        $this->gridViewManager = $this->createMock(GridViewManager::class);

        $this->gridViewApiEntityManager = new GridViewApiEntityManager(
            self::CLASS_NAME,
            $this->om,
            $this->gridViewManager
        );
    }

    public function testGetViewSystemAll()
    {
        $repo = $this->createMock(EntityRepository::class);

        $registry = $this->createMock(Registry::class);

        $registry->expects($this->any())
            ->method('getRepository')
            ->willReturn($repo);

        $dataGridManager = $this->createMock(Manager::class);

        $aclHelper = $this->createMock(AclHelper::class);

        $restrictionManager = $this->createMock(RestrictionManager::class);

        $gridViewManager = new GridViewManager(
            $aclHelper,
            $registry,
            $dataGridManager,
            $restrictionManager
        );

        $systemAllView = new View(GridViewsExtension::DEFAULT_VIEW_ID);
        $systemAllView->setGridName('Test');
        $view = $gridViewManager->getView('Test', 0, 'Test');

        $this->assertEquals($view, $systemAllView);
    }
}
