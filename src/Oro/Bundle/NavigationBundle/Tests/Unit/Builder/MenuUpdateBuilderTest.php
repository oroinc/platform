<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Builder;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;

use Knp\Menu\ItemInterface;

use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\NavigationBundle\Builder\MenuUpdateBuilder;
use Oro\Bundle\NavigationBundle\Entity\Repository\MenuUpdateRepository;
use Oro\Bundle\NavigationBundle\Menu\ConfigurationBuilder;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;

class MenuUpdateBuilderTest extends \PHPUnit_Framework_TestCase
{
    /** @var MenuUpdateBuilder */
    private $menuUpdateBuilder;

    /** @var LocalizationHelper|\PHPUnit_Framework_MockObject_MockObject */
    private $localizationHelper;

    /** @var ScopeManager|\PHPUnit_Framework_MockObject_MockObject */
    private $scopeManager;

    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    private $registry;

    /** @var ItemInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $menuItem;

    protected function setUp()
    {
        $this->localizationHelper = $this->getMockBuilder(LocalizationHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->scopeManager = $this->getMockBuilder(ScopeManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->menuItem = $this->createMock(ItemInterface::class);

        $this->menuUpdateBuilder = new MenuUpdateBuilder(
            $this->localizationHelper,
            $this->scopeManager,
            $this->registry
        );

        $this->menuUpdateBuilder->setClassName('Some\Class');
    }

    public function testWrongScopeSet()
    {
        $this->menuUpdateBuilder->setScopeType('some_wrong_type');
        $this->scopeManager->expects($this->never())
            ->method('findRelatedScopeIdsWithPriority');
        $this->menuUpdateBuilder->build($this->menuItem);
    }

    private function configureCommonExpectations()
    {
        $this->scopeManager->expects($this->once())
            ->method('findRelatedScopeIdsWithPriority')
            ->with(null, null)
            ->willReturn([]);

        /** @var $objectManager ObjectManager|\PHPUnit_Framework_MockObject_MockObject */
        $objectManager = $this->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with('Some\Class')
            ->willReturn($objectManager);

        /** @var $repository MenuUpdateRepository|\PHPUnit_Framework_MockObject_MockObject */
        $repository = $this->getMockBuilder(MenuUpdateRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager->expects($this->once())
            ->method('getRepository')
            ->with('Some\Class')
            ->willReturn($repository);

        $repository->expects($this->once())
            ->method('findMenuUpdatesByScopeIds')
            ->with(null, [])
            ->willReturn([]);
    }

    /**
     * @expectedException Oro\Bundle\NavigationBundle\Exception\MaxNestingLevelExceededException
     * @expectedExceptionMessage Item "ChildMenuItem" exceeded max nesting level in menu "MainMenuItem".
     */
    public function testMaxNestingLevelExceededException()
    {
        $this->configureCommonExpectations();

        /** @var ItemInterface|\PHPUnit_Framework_MockObject_MockObject */
        $childItem = $this->createMock(ItemInterface::class);

        $this->menuItem->expects($this->exactly(3))
            ->method('getExtra')
            ->withConsecutive(
                ['scope_type', ConfigurationBuilder::DEFAULT_SCOPE_TYPE],
                ['divider', false],
                ['max_nesting_level', 0]
            )
            ->willReturnOnConsecutiveCalls(null, false, 10);

        $childItem->expects($this->once())
            ->method('getLevel')
            ->willReturn(11);

        $childItem->expects($this->exactly(1))
            ->method('getChildren')
            ->willReturn([]);

        $childItem->expects($this->once())
            ->method('getLabel')
            ->willReturn('ChildMenuItem');

        $this->menuItem->expects($this->exactly(2))
            ->method('getChildren')
            ->willReturnOnConsecutiveCalls([$childItem], [$childItem]);

        $this->menuItem->expects($this->once())
            ->method('getLabel')
            ->willReturn('MainMenuItem');

        $this->menuUpdateBuilder->build($this->menuItem);
    }

    public function testBuild()
    {
        $this->configureCommonExpectations();

        $this->menuItem->expects($this->exactly(2))
            ->method('getChildren')
            ->willReturn([]);

        $this->menuUpdateBuilder->build($this->menuItem);
    }
}
