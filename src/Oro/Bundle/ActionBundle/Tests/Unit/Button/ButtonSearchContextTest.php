<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Button;

use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ButtonSearchContextTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    /** @var ButtonSearchContext */
    protected $buttonSearchContext;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->buttonSearchContext = new ButtonSearchContext();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->buttonSearchContext);
    }

    public function testProperties()
    {
        $this->assertPropertyAccessors(
            $this->buttonSearchContext,
            [
                ['routeName', 'test_route'],
                ['datagrid', 'test_grid'],
                ['referrer', 'test_ref'],
                ['group', 'test_group']
            ]
        );
    }

    /**
     * @dataProvider getSetEntityDataProvider
     *
     * @param int|string|array $entityId
     */
    public function testGetSetEntity($entityId)
    {
        $this->buttonSearchContext->setEntity('Class', $entityId);
        $this->assertSame('Class', $this->buttonSearchContext->getEntityClass());
        $this->assertSame($entityId, $this->buttonSearchContext->getEntityId());
    }

    /**
     * @return \Generator
     */
    public function getSetEntityDataProvider()
    {
        yield 'simple int id' => [10];
        yield 'simple string id' => [uniqid('', true)];
        yield 'array id' => [[10, uniqid('', true)]];
    }

    public function testGetHash()
    {
        $this->buttonSearchContext->setEntity('Class', ['id' => 42])
            ->setRouteName('test_route')
            ->setDatagrid('test_datagrid')
            ->setReferrer('test_referrer')
            ->setGroup(['test_group1', 'test_groug2']);

        $this->assertEquals('654dfa2c4ef17b70a92ed9b7c0ffbc5a', $this->buttonSearchContext->getHash());
    }
}
