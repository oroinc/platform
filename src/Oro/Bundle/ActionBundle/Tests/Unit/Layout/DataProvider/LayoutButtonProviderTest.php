<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Layout\DataProvider\LayoutButtonProvider;
use Oro\Bundle\ActionBundle\Provider\ButtonProvider;
use Oro\Bundle\ActionBundle\Provider\ButtonSearchContextProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class LayoutButtonProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ButtonProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $buttonProvider;

    /** @var ButtonSearchContext|\PHPUnit_Framework_MockObject_MockObject */
    protected $buttonSearchContext;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var ButtonSearchContextProvider|\PHPUnit_Framework_MockObject_MockObject */
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

        $this->buttonProvider = $this->getMockBuilder(ButtonProvider::class)
            ->getMock();

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
     */
    public function testGetAll($entity, $isNew)
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

        $this->buttonProvider->expects($this->once())
            ->method('findAvailable')
            ->with(
                $this->callback(function (ButtonSearchContext $buttonSearchContext) use ($isNew, $entity) {
                    if (!is_null($entity)) {
                        if (!$isNew) {
                            return $buttonSearchContext->getEntityClass() === 'class' &&
                            $buttonSearchContext->getEntityId() === 'entity_id';
                        }

                        return $buttonSearchContext->getEntityClass() === 'class';
                    }

                    return true;
                })
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
            ],
            'testWhenEntityIsNull' => [
                'entity' => null,
                'isNew' => false,
            ],
            'testWhenEntityIsFlushed' => [
                'entity' => new \stdClass(),
                'isNew' => false,
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
