<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Button;

use Oro\Bundle\ActionBundle\Button\ButtonContext;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

class ButtonContextTest extends TestCase
{
    use EntityTestCaseTrait;

    private ButtonContext $buttonContext;

    #[\Override]
    protected function setUp(): void
    {
        $this->buttonContext = new ButtonContext();
    }

    public function testGetSetButtonContext(): void
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
     */
    public function testSetGetEntity(mixed $entityId): void
    {
        $this->buttonContext->setEntity('Class', $entityId);
        $this->assertSame('Class', $this->buttonContext->getEntityClass());
        $this->assertSame($entityId, $this->buttonContext->getEntityId());
    }

    public function getSetEntityDataProvider(): array
    {
        return [
            [10],
            [uniqid()],
            [[10, uniqid()]],
            [null]
        ];
    }
}
