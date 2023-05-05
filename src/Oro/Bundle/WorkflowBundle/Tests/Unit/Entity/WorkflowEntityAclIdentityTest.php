<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Entity;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowEntityAcl;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowEntityAclIdentity;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Component\Testing\ReflectionUtil;

class WorkflowEntityAclIdentityTest extends \PHPUnit\Framework\TestCase
{
    /** @var WorkflowEntityAclIdentity */
    private $aclIdentity;

    protected function setUp(): void
    {
        $this->aclIdentity = new WorkflowEntityAclIdentity();
    }

    public function testGetId()
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
    public function testSettersAndGetters($property, $value)
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($this->aclIdentity, $property, $value);
        $this->assertEquals($value, $accessor->getValue($this->aclIdentity, $property));
    }

    public function propertiesDataProvider(): array
    {
        return [
            ['acl', new WorkflowEntityAcl()],
            ['entityClass', new \DateTime()],
            ['entityId', 123],
            ['workflowItem', new WorkflowItem()],
        ];
    }

    public function testImport()
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

    public function testGetAclAttributeStepKeyNoAclException()
    {
        $this->expectException(WorkflowException::class);
        $this->expectExceptionMessage("Workflow ACL identity with ID 1 doesn't have entity ACL");

        ReflectionUtil::setId($this->aclIdentity, 1);
        $this->aclIdentity->getAclAttributeStepKey();
    }
}
