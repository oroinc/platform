<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Entity;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowEntityAcl;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowEntityAclIdentity;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\TestCase;

class WorkflowEntityAclIdentityTest extends TestCase
{
    private WorkflowEntityAclIdentity $aclIdentity;

    #[\Override]
    protected function setUp(): void
    {
        $this->aclIdentity = new WorkflowEntityAclIdentity();
    }

    public function testGetId(): void
    {
        $this->assertNull($this->aclIdentity->getId());

        $value = 42;
        ReflectionUtil::setId($this->aclIdentity, $value);
        $this->assertSame($value, $this->aclIdentity->getId());
    }

    /**
     * @dataProvider propertiesDataProvider
     * @param string $property
     * @param mixed $value
     */
    public function testSettersAndGetters($property, $value): void
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($this->aclIdentity, $property, $value);
        $this->assertEquals($value, $accessor->getValue($this->aclIdentity, $property));
    }

    public function propertiesDataProvider(): array
    {
        return [
            ['acl', new WorkflowEntityAcl()],
            ['entityClass', \DateTime::class],
            ['entityId', 123],
            ['workflowItem', new WorkflowItem()],
        ];
    }

    public function testImport(): void
    {
        $step = new WorkflowStep();
        $step->setName('step');

        $entityAcl = new WorkflowEntityAcl();
        $entityAcl->setStep($step)->setAttribute('attribute');

        $this->aclIdentity->setEntityId(123);
        $this->aclIdentity->setAcl($entityAcl);

        $newAclIdentity = new WorkflowEntityAclIdentity();
        $newAclIdentity->setAcl($entityAcl);
        $this->assertEquals($newAclIdentity, $newAclIdentity->import($this->aclIdentity));

        $this->assertEquals(123, $newAclIdentity->getEntityId());

        $this->assertEquals($this->aclIdentity->getAclAttributeStepKey(), $newAclIdentity->getAclAttributeStepKey());
    }

    public function testGetAclAttributeStepKeyNoAclException(): void
    {
        $this->expectException(WorkflowException::class);
        $this->expectExceptionMessage("Workflow ACL identity with ID 1 doesn't have entity ACL");

        ReflectionUtil::setId($this->aclIdentity, 1);
        $this->aclIdentity->getAclAttributeStepKey();
    }
}
