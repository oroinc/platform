<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Serializer\WorkflowItem;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Serializer\WorkflowItem\DebugWorkflowItemSerializer;
use Oro\Bundle\WorkflowBundle\Serializer\WorkflowItem\WorkflowItemSerializerInterface;

class DebugWorkflowItemSerializerTest extends \PHPUnit\Framework\TestCase
{
    /** @var WorkflowItemSerializerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $innerSerializer;

    /** @var DebugWorkflowItemSerializer */
    private $serializer;

    protected function setUp(): void
    {
        $this->innerSerializer = $this->createMock(WorkflowItemSerializerInterface::class);

        $this->serializer = new DebugWorkflowItemSerializer($this->innerSerializer);
    }

    private function doSerialize(?array $serializedWorkflowResult): array
    {
        $workflowItem = new WorkflowItem();
        $this->innerSerializer->expects(self::once())
            ->method('serialize')
            ->with(self::identicalTo($workflowItem))
            ->willReturn(['result' => $serializedWorkflowResult]);

        return $this->serializer->serialize($workflowItem);
    }

    public function testSerializeForEmptyWorkflowResult(): void
    {
        self::assertSame(['result' => null], $this->doSerialize(null));
    }

    public function testSerializeWhenSerializedWorkflowResultDoesNotContainObjects(): void
    {
        $serializedWorkflowResult = [
            'key1' => null,
            'key2' => 1,
            'key3' => 'test',
            'key4' => [
                'key1' => null,
                'key2' => 1,
                'key3' => 'test'
            ],
            'key5' => [
                [
                    'key1' => null,
                    'key2' => 1,
                    'key3' => 'test'
                ]
            ]
        ];

        self::assertSame(['result' => $serializedWorkflowResult], $this->doSerialize($serializedWorkflowResult));
    }

    /**
     * @dataProvider serializeWhenSerializedWorkflowResultContainsObjectsDataProvider
     */
    public function testSerializeWhenSerializedWorkflowResultContainsObjects(
        array $serializedWorkflowResult,
        string $propertyPath
    ): void {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf(
            'The serialized workflow result must not contain objects, but "stdClass" found by the path "%s".',
            $propertyPath
        ));

        $this->doSerialize($serializedWorkflowResult);
    }

    public function serializeWhenSerializedWorkflowResultContainsObjectsDataProvider(): array
    {
        return [
            [
                ['key1' => 'test', 'key2' => new \stdClass()],
                'key2'
            ],
            [
                ['key1' => ['key1' => 'test', 'key2' => new \stdClass()]],
                'key1.key2'
            ],
            [
                ['key1' => [['key1' => 'test', 'key2' => new \stdClass()]]],
                'key1.0.key2'
            ]
        ];
    }
}
