<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Oro\Bundle\ActionBundle\Model\ButtonSearchContext;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ButtonSearchContextTest extends \PHPUnit_Framework_TestCase
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
        $properties = [
            ['routeName', 'test_route'],
            ['gridName', 'test_grid'],
            ['referrer', 'test_ref'],
            ['group', 'test_group']
        ];

        $this->assertPropertyAccessors($this->buttonSearchContext, $properties);
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
     * @return array
     */
    public function getSetEntityDataProvider()
    {
        return [
            [10],
            [uniqid()],
            [[10, uniqid()]],
        ];
    }
}
