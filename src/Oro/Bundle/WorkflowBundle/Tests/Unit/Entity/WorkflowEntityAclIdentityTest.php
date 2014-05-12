<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Entity;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowEntityAcl;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowEntityAclIdentity;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;

class WorkflowEntityAclIdentityTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WorkflowEntityAclIdentity
     */
    protected $aclIdentity;

    protected function setUp()
    {
        $this->aclIdentity = new WorkflowEntityAclIdentity();
    }

    protected function tearDown()
    {
        unset($this->aclIdentity);
    }

    public function testGetId()
    {
        $this->assertNull($this->aclIdentity->getId());

        $value = 42;
        $this->setEntityId($value);
        $this->assertEquals($value, $this->aclIdentity->getId());
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

    public function propertiesDataProvider()
    {
        return array(
            array('acl', new WorkflowEntityAcl()),
            array('entityClass', new \DateTime()),
            array('entityId', 123),
            array('workflowItem', new WorkflowItem()),
        );
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

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\WorkflowException
     * @expectedExceptionMessage Workflow ACL identity with ID 1 doesn't have entity ACL
     */
    public function testGetAclAttributeStepKeyNoAclException()
    {
        $this->setEntityId(1);
        $this->aclIdentity->getAclAttributeStepKey();
    }

    /**
     * @param int $value
     */
    protected function setEntityId($value)
    {
        $idReflection = new \ReflectionProperty('Oro\Bundle\WorkflowBundle\Entity\WorkflowEntityAclIdentity', 'id');
        $idReflection->setAccessible(true);
        $idReflection->setValue($this->aclIdentity, $value);
    }
}
