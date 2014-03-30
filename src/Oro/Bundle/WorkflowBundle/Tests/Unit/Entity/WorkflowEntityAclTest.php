<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Entity;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowEntityAcl;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;

class WorkflowEntityAclTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WorkflowEntityAcl
     */
    protected $entityAcl;

    protected function setUp()
    {
        $this->entityAcl = new WorkflowEntityAcl();
    }

    protected function tearDown()
    {
        unset($this->entityAcl);
    }

    public function testGetId()
    {
        $this->assertNull($this->entityAcl->getId());

        $value = 42;
        $this->setEntityId($value);
        $this->assertEquals($value, $this->entityAcl->getId());
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

    public function propertiesDataProvider()
    {
        return array(
            array('attribute', 'test'),
            array('step', new WorkflowStep()),
            array('definition', new WorkflowDefinition()),
            array('entityClass', new \DateTime()),
            array('updatable', false),
            array('deletable', false),
        );
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

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\WorkflowException
     * @expectedExceptionMessage Workflow entity ACL with ID 1 doesn't have workflow step
     */
    public function testGetAttributeStepKeyNoStepException()
    {
        $this->setEntityId(1);
        $this->entityAcl->getAttributeStepKey();
    }

    /**
     * @param int $value
     */
    protected function setEntityId($value)
    {
        $idReflection = new \ReflectionProperty('Oro\Bundle\WorkflowBundle\Entity\WorkflowEntityAcl', 'id');
        $idReflection->setAccessible(true);
        $idReflection->setValue($this->entityAcl, $value);
    }
}
