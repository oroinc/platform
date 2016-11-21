<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ActionBundle\Layout\DataProvider\LayoutButtonProvider;
use Oro\Bundle\ActionBundle\Model\ButtonSearchContext;
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
     * @param string $expectSetEntity
     */
    public function testGetAll($entity, $isNew, $expectSetEntity)
    {
        $this->doctrineHelper->expects($this->any())
            ->method('isNewEntity')
            ->willReturn($isNew);
        $this->doctrineHelper->expects($this->$expectSetEntity())
            ->method('getEntityClass')
            ->with($entity)
            ->willReturn('class');
        $this->doctrineHelper->expects($this->$expectSetEntity())
            ->method('getEntityIdentifier')
            ->with($entity)
            ->willReturn('entity_id');

        $this->buttonProvider->expects($this->once())
            ->method('findAll')
            ->with(
                $this->callback(function (ButtonSearchContext $buttonSearchContext) use ($expectSetEntity) {
                    if ($expectSetEntity === 'once') {
                        return $buttonSearchContext->getEntityClass() === 'class' &&
                            $buttonSearchContext->getEntityId() === 'entity_id';
                    }
                    return true;
                })
            );

        $this->layoutButtonProvider->getAll($entity);
    }

    /**
     * @dataProvider dataGroupsProvider
     *
     * @param string $group
     */
    public function testGetByGroup($group)
    {
        $this->buttonProvider->expects($this->once())
            ->method('findAll')
            ->with(
                $this->callback(function (ButtonSearchContext $buttonSearchContext) use ($group) {
                    return ($group !== null) ? $buttonSearchContext->getGroup() === $group : true;
                })
            );

        $this->layoutButtonProvider->getByGroup(null, $group);
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
                'expectSetEntityCalls' => 'never'
            ],
            'testWhenEntityIsNull' => [
                'entity' => null,
                'isNew' => false,
                'expectSetEntityCalls' => 'never'
            ],
            'testWhenEntityIsFlushed' => [
                'entity' => new \stdClass(),
                'isNew' => false,
                'expectSetEntityCalls' => 'once'
            ],
        ];
    }

    /**
     * @return array
     */
    public function dataGroupsProvider()
    {
        return [
            ['groups1'],
            ['groups2'],
            [null]
        ];
    }
}
