<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Entity;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Component\Testing\ReflectionUtil;

class WorkflowStepTest extends \PHPUnit\Framework\TestCase
{
    /** @var WorkflowStep */
    private $step;

    protected function setUp(): void
    {
        $this->step = new WorkflowStep();
    }

    public function testGetId()
    {
        $this->assertNull($this->step->getId());

        $value = 42;
        ReflectionUtil::setId($this->step, $value);
        $this->assertSame($value, $this->step->getId());
    }

    /**
     * @dataProvider propertiesDataProvider
     * @param string $property
     * @param mixed $value
     */
    public function testSettersAndGetters($property, $value)
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($this->step, $property, $value);
        $this->assertEquals($value, $accessor->getValue($this->step, $property));
    }

    public function propertiesDataProvider(): array
    {
        return [
            ['name', 'test'],
            ['label', 'test'],
            ['stepOrder', 1],
            ['definition', $this->createMock(WorkflowDefinition::class)],
            ['final', true]
        ];
    }

    public function testImport()
    {
        $this->step->setName('test');
        $this->step->setLabel('test');
        $this->step->setStepOrder(1);
        $this->step->setFinal(true);

        $newStep = new WorkflowStep();
        $this->assertEquals($newStep, $newStep->import($this->step));

        $this->assertEquals('test', $newStep->getName());
        $this->assertEquals('test', $newStep->getLabel());
        $this->assertEquals(1, $newStep->getStepOrder());
        $this->assertTrue($newStep->isFinal());
    }

    public function testToString()
    {
        $label = 'Step Label';
        $this->step->setLabel($label);
        $this->assertEquals($label, (string)$this->step);
    }
}
