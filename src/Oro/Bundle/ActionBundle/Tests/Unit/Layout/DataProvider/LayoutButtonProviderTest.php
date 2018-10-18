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
    protected $buttonProvider;

    /** @var ButtonSearchContext|\PHPUnit\Framework\MockObject\MockObject */
    protected $buttonSearchContext;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var ButtonSearchContextProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $contextProvider;

    /** @var LayoutButtonProvider */
    protected $layoutButtonProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->buttonSearchContext = $this->getMockBuilder(ButtonSearchContext::class)
            ->setMethods(null)
            ->getMock();

        $this->buttonProvider = $this->createMock(ButtonProvider::class);

        $this->contextProvider = $this->getMockBuilder(ButtonSearchContextProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextProvider->expects($this->once())
            ->method('getButtonSearchContext')
            ->willReturn($this->buttonSearchContext);

        $this->layoutButtonProvider = new LayoutButtonProvider(
            $this->buttonProvider,
            $this->doctrineHelper,
            $this->contextProvider
        );
    }

    /**
     * @dataProvider getAllDataProvider
     *
     * @param object|null $entity
     * @param bool $isNew
     * @param string $expectSetEntityClass
     * @param string $expectSetEntityId
     */
    public function testGetAll($entity, $isNew, $expectSetEntityClass, $expectSetEntityId)
    {
        $this->doctrineHelper->expects($this->any())
            ->method('isNewEntity')
            ->willReturn($isNew);

        if (!is_null($entity)) {
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
        $this->doctrineHelper->expects($this->any())->method('isNewEntity')->willReturn($isNew);
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
     *
     * @param string|null $datagrid
     * @param string|null $group
     */
    public function testGetByGroup($datagrid, $group)
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

    /**
     * @return array
     */
    public function getAllDataProvider()
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

    /**
     * @return array
     */
    public function dataGroupsProvider()
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
