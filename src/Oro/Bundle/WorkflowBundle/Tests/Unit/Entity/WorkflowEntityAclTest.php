<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Entity;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowEntityAcl;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Component\Testing\ReflectionUtil;

class WorkflowEntityAclTest extends \PHPUnit\Framework\TestCase
{
    /** @var WorkflowEntityAcl */
    private $entityAcl;

    protected function setUp(): void
    {
        $this->entityAcl = new WorkflowEntityAcl();
    }

    public function testGetId()
    {
        $this->assertNull($this->entityAcl->getId());

        $value = 42;
        ReflectionUtil::setId($this->entityAcl, $value);
        $this->assertSame($value, $this->entityAcl->getId());
    }

    /**
     * @dataProvider propertiesDataProvider
     * @param string $property
     * @param mixed $value
     */
    public function testSettersAndGetters($property, $value)
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($this->entityAcl, $property, $value);
        $this->assertEquals($value, $accessor->getValue($this->entityAcl, $property));
    }

    public function propertiesDataProvider(): array
    {
        return [
            ['attribute', 'test'],
            ['step', new WorkflowStep()],
            ['definition', new WorkflowDefinition()],
            ['entityClass', new \DateTime()],
            ['updatable', false],
            ['deletable', false],
        ];
    }

    public function testImport()
    {
        $step = new WorkflowStep();
        $step->setName('step');
        $definition = new WorkflowDefinition();
        $definition->addStep($step);

        $this->entityAcl->setAttribute('attribute');
        $this->entityAcl->setStep($step);
        $this->entityAcl->setEntityClass('TestEntity');
        $this->entityAcl->setUpdatable(false);
        $this->entityAcl->setDeletable(false);

        $newEntityAcl = new WorkflowEntityAcl();
        $newEntityAcl->setDefinition($definition);
        $this->assertEquals($newEntityAcl, $newEntityAcl->import($this->entityAcl));

        $this->assertEquals('attribute', $newEntityAcl->getAttribute());
        $this->assertEquals($step, $newEntityAcl->getStep());
        $this->assertEquals('TestEntity', $newEntityAcl->getEntityClass());
        $this->assertFalse($this->entityAcl->isUpdatable());
        $this->assertFalse($this->entityAcl->isDeletable());

        $this->assertEquals($this->entityAcl->getAttributeStepKey(), $newEntityAcl->getAttributeStepKey());
    }

    public function testGetAttributeStepKeyNoStepException()
    {
        $this->expectException(WorkflowException::class);
        $this->expectExceptionMessage("Workflow entity ACL with ID 1 doesn't have workflow step");

        ReflectionUtil::setId($this->entityAcl, 1);
        $this->entityAcl->getAttributeStepKey();
    }
}
