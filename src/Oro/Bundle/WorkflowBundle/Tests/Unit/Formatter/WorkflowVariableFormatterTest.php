<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Formatter;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\WorkflowBundle\Formatter\WorkflowVariableFormatter;
use Oro\Bundle\WorkflowBundle\Model\Variable;
use Symfony\Component\Translation\TranslatorInterface;

class WorkflowVariableFormatterTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface */
    protected $translator;

    /** @var WorkflowVariableFormatter */
    protected $formatter;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->formatter = new WorkflowVariableFormatter($this->translator);
    }

    /**
     * @param $value
     * @param $type
     * @param string $expected
     * @param bool $expectedTranslate
     *
     * @dataProvider formatWorkflowVariableValueDataProvider
     */
    public function testFormatWorkflowVariableValue($value, $type, $expected, $expectedTranslate = false)
    {
        $variable = $this->createMock(Variable::class);
        $variable->expects($this->once())->method('getValue')->willReturn($value);
        $variable->expects($this->any())->method('getType')->willReturn($type);
        $this->translator->expects($this->exactly((int) $expectedTranslate))
            ->method('trans')
            ->with($expected)
            ->willReturnArgument(0);

        $this->assertSame($expected, $this->formatter->formatWorkflowVariableValue($variable));
    }

    /**
     * @return array
     */
    public function formatWorkflowVariableValueDataProvider()
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
