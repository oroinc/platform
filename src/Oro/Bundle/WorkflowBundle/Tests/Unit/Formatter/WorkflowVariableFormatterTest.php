<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Formatter;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\WorkflowBundle\Formatter\WorkflowVariableFormatter;
use Oro\Bundle\WorkflowBundle\Model\Variable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class WorkflowVariableFormatterTest extends TestCase
{
    private TranslatorInterface&MockObject $translator;
    private WorkflowVariableFormatter $formatter;

    #[\Override]
    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->formatter = new WorkflowVariableFormatter($this->translator);
    }

    /**
     * @dataProvider formatWorkflowVariableValueDataProvider
     */
    public function testFormatWorkflowVariableValue(
        mixed $value,
        ?string $type,
        string $expected,
        bool $expectedTranslate = false
    ): void {
        $variable = $this->createMock(Variable::class);
        $variable->expects($this->once())
            ->method('getValue')
            ->willReturn($value);
        $variable->expects($this->any())
            ->method('getType')
            ->willReturn($type);
        $this->translator->expects($this->exactly((int) $expectedTranslate))
            ->method('trans')
            ->with($expected)
            ->willReturnArgument(0);

        $this->assertSame($expected, $this->formatter->formatWorkflowVariableValue($variable));
    }

    public function formatWorkflowVariableValueDataProvider(): array
    {
        $entity = new Item();
        $entity->stringValue = 'string value';

        return [
            'array value' => [
                'value' => ['value1', 'value2'],
                'type' => 'array',
                'expected' => 'value1, value2',
            ],
            'traversable value' => [
                'value' => new ArrayCollection(['value1', 'value2']),
                'type' => 'array',
                'expected' => 'value1, value2',
            ],
            'bool value true' => [
                'value' => true,
                'type' => 'bool',
                'expected' => 'Yes',
                'expectedTranslate' => true,
            ],
            'bool value false' => [
                'value' => false,
                'type' => 'bool',
                'expected' => 'No',
                'expectedTranslate' => true,
            ],
            'string value' => [
                'value' => 'Test',
                'type' => 'string',
                'expected' => 'Test',
            ],
            'null value' => [
                'value' => null,
                'type' => null,
                'expected' => '',
            ],
            'entity value' => [
                'value' => $entity,
                'type' => 'entity',
                'expected' => 'string value',
            ],
        ];
    }
}
