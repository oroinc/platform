<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Layout\DataProvider\LayoutButtonProvider;
use Oro\Bundle\ActionBundle\Provider\ButtonProvider;
use Oro\Bundle\ActionBundle\Provider\ButtonSearchContextProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class LayoutButtonProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ButtonProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $buttonProvider;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var LayoutButtonProvider */
    private $layoutButtonProvider;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->buttonProvider = $this->createMock(ButtonProvider::class);

        $contextProvider = $this->createMock(ButtonSearchContextProvider::class);
        $contextProvider->expects($this->once())
            ->method('getButtonSearchContext')
            ->willReturn(new ButtonSearchContext());

        $this->layoutButtonProvider = new LayoutButtonProvider(
            $this->buttonProvider,
            $this->doctrineHelper,
            $contextProvider
        );
    }

    /**
     * @dataProvider getAllDataProvider
     */
    public function testGetAll(?object $entity, bool $isNew, string $expectSetEntityClass, string $expectSetEntityId)
    {
        $this->doctrineHelper->expects($this->any())
            ->method('isNewEntity')
            ->willReturn($isNew);

        if (null !== $entity) {
            $this->doctrineHelper->expects($this->atLeastOnce())
                ->method('getEntityClass')
                ->with($entity)
                ->willReturn('class');
            if (!$isNew) {
                $this->doctrineHelper->expects($this->atLeastOnce())
                    ->method('getSingleEntityIdentifier')
                    ->with($entity)
                    ->willReturn('entity_id');
            }
        }
        $this->doctrineHelper->expects($this->any())
            ->method('isNewEntity')
            ->willReturn($isNew);
        $this->doctrineHelper->expects($this->$expectSetEntityClass())
            ->method('getEntityClass')
            ->with($entity)
            ->willReturn('class');
        $this->doctrineHelper->expects($this->$expectSetEntityId())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->willReturn('entity_id');

        $this->buttonProvider->expects($this->once())
            ->method('findAvailable')
            ->with(
                $this->callback(
                    function (ButtonSearchContext $searchContext) use ($expectSetEntityClass, $expectSetEntityId) {
                        if ($expectSetEntityClass === 'once') {
                            $entityId = $expectSetEntityId === 'once' ? 'entity_id' : null;

                            return $searchContext->getEntityClass() === 'class' &&
                                $searchContext->getEntityId() === $entityId;
                        }

                        return true;
                    }
                )
            );

        $this->layoutButtonProvider->getAll($entity);
    }

    /**
     * @dataProvider dataGroupsProvider
     */
    public function testGetByGroup(?string $datagrid, ?string $group)
    {
        $this->buttonProvider->expects($this->once())
            ->method('findAvailable')
            ->with(
                $this->callback(function (ButtonSearchContext $buttonSearchContext) use ($group, $datagrid) {
                    return ($buttonSearchContext->getGroup() === $group) &&
                    ($buttonSearchContext->getDatagrid() === $datagrid);
                })
            );

        $this->layoutButtonProvider->getByGroup(null, $datagrid, $group);
    }

    public function getAllDataProvider(): array
    {
        return [
            'testWhenEntityIsNew' => [
                'entity' => new \stdClass(),
                'isNew' => true,
                'expectSetEntityClassCalls' => 'once',
                'expectSetEntityIdCalls' => 'never'
            ],
            'testWhenEntityIsNull' => [
                'entity' => null,
                'isNew' => false,
                'expectSetEntityClassCalls' => 'never',
                'expectSetEntityIdCalls' => 'never'
            ],
            'testWhenEntityIsFlushed' => [
                'entity' => new \stdClass(),
                'isNew' => false,
                'expectSetEntityClassCalls' => 'once',
                'expectSetEntityIdCalls' => 'once'
            ],
        ];
    }

    public function dataGroupsProvider(): array
    {
        return [
            ['datagrid', 'groups1'],
            ['datagrid', 'groups2'],
            ['datagrid', null],
            [null, 'group'],
            [null, null],
        ];
    }
}
