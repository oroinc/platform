<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Entity\Manager;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DataGridBundle\Entity\GridView;
use Oro\Bundle\DataGridBundle\Entity\Manager\GridViewApiEntityManager;
use Oro\Bundle\DataGridBundle\Entity\Manager\GridViewManager;
use Oro\Bundle\DataGridBundle\Extension\GridViews\View;
use Oro\Bundle\DataGridBundle\Extension\GridViews\ViewInterface;
use Oro\Bundle\UserBundle\Entity\AbstractUser;

class GridViewApiEntityManagerTest extends \PHPUnit\Framework\TestCase
{
    private const CLASS_NAME = GridView::class;

    /** @var GridViewManager|\PHPUnit\Framework\MockObject\MockObject */
    private $gridViewManager;

    /** @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject */
    private $om;

    /** @var GridViewApiEntityManager */
    private $gridViewApiEntityManager;

    protected function setUp(): void
    {
        $this->om = $this->createMock(ObjectManager::class);
        $this->gridViewManager = $this->createMock(GridViewManager::class);

        $this->om->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn(new ClassMetadata(self::CLASS_NAME));

        $this->gridViewApiEntityManager = new GridViewApiEntityManager(
            self::CLASS_NAME,
            $this->om,
            $this->gridViewManager
        );
    }

    public function testSetDefaultGridView()
    {
        $user = $this->createMock(AbstractUser::class);
        $gridView = $this->createMock(ViewInterface::class);

        $this->gridViewManager->expects(self::once())
            ->method('setDefaultGridView')
            ->with(self::identicalTo($user), self::identicalTo($gridView));

        $this->om->expects(self::once())
            ->method('flush');

        $this->gridViewApiEntityManager->setDefaultGridView($user, $gridView);
    }
    public function testGetView()
    {
        $id = 'test_view';
        $default = 0;
        $gridName = 'test_grid';
        $view = $this->createMock(View::class);

        $this->gridViewManager->expects(self::once())
            ->method('getView')
            ->with($id, $default, $gridName)
            ->willReturn($view);

        $this->assertSame($view, $this->gridViewApiEntityManager->getView($id, $default, $gridName));
    }
}
