<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Button;

use Oro\Bundle\ActionBundle\Button\ButtonContext;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ButtonContextTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    /** @var ButtonContext */
    protected $buttonContext;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->buttonContext = new ButtonContext();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->buttonContext);
    }

    public function testGetSetButtonContext()
    {
        $this->assertPropertyAccessors(
            $this->buttonContext,
            [
                ['routeName', 'test_route'],
                ['datagridName', 'datagrid'],
                ['group', 'test_group'],
                ['executionRoute', 'test_url1'],
                ['formDialogRoute', 'test_url2'],
                ['formPageRoute', 'test_url3'],
                ['originalUrl', 'test_url4'],
                ['enabled', true],
                ['unavailableHidden', true],
                ['errors', ['test_error'], []],
            ]
        );
    }

    /**
     * @dataProvider getSetEntityDataProvider
     *
     * @param int|string|array|null $entityId
     */
    public function testSetGetEntity($entityId)
    {
        $this->buttonContext->setEntity('Class', $entityId);
        $this->assertSame('Class', $this->buttonContext->getEntityClass());
        $this->assertSame($entityId, $this->buttonContext->getEntityId());
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
            [null]
        ];
    }
}
