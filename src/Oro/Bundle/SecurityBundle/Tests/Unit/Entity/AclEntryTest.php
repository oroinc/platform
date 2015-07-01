<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Entity;

use Oro\Bundle\SecurityBundle\Entity\AclClass;
use Oro\Bundle\SecurityBundle\Entity\AclEntry;
use Oro\Bundle\SecurityBundle\Entity\AclObjectIdentity;
use Oro\Bundle\SecurityBundle\Entity\AclSecurityIdentity;

class AclEntryTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY_ID         = 2;
    const FIELD_NAME        = 'fieldName';
    const ACE_ORDER         = 3;
    const MASK              = 4;
    const GRANTING          = true;
    const GRANTING_STRATEGY = 'all';
    const AUDIT_SUCCESS     = false;
    const AUDIT_FAILURE     = true;
    const RECORD_ID         = 5;

    /**
     * @var AclEntry
     */
    protected $entity;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->entity = new AclEntry();
    }

    public function testGettersSetters()
    {
        $class = new \ReflectionClass($this->entity);
        $prop = $class->getProperty('id');
        $prop->setAccessible(true);
        $prop->setValue($this->entity, self::ENTITY_ID);

        $this->assertEquals(self::ENTITY_ID, $this->entity->getId());

        $class = new AclClass();
        $this->entity->setClass($class);
        $this->assertEquals($class, $this->entity->getClass());

        $objectIdentity = new AclObjectIdentity();
        $this->entity->setObjectIdentity($objectIdentity);
        $this->assertEquals($objectIdentity, $this->entity->getObjectIdentity());

        $securityIdentity = new AclSecurityIdentity();
        $this->entity->setSecurityIdentity($securityIdentity);
        $this->assertEquals($securityIdentity, $this->entity->getSecurityIdentity());

        $this->entity->setFieldName(self::FIELD_NAME);
        $this->assertEquals(self::FIELD_NAME, $this->entity->getFieldName());

        $this->entity->setAceOrder(self::ACE_ORDER);
        $this->assertEquals(self::ACE_ORDER, $this->entity->getAceOrder());

        $this->entity->setMask(self::MASK);
        $this->assertEquals(self::MASK, $this->entity->getMask());

        $this->entity->setGranting(self::GRANTING);
        $this->assertEquals(self::GRANTING, $this->entity->getGranting());

        $this->entity->setGrantingStrategy(self::GRANTING_STRATEGY);
        $this->assertEquals(self::GRANTING_STRATEGY, $this->entity->getGrantingStrategy());

        $this->entity->setAuditSuccess(self::AUDIT_SUCCESS);
        $this->assertEquals(self::AUDIT_SUCCESS, $this->entity->getAuditSuccess());

        $this->entity->setAuditFailure(self::AUDIT_FAILURE);
        $this->assertEquals(self::AUDIT_FAILURE, $this->entity->getAuditFailure());

        $this->entity->setRecordId(self::RECORD_ID);
        $this->assertEquals(self::RECORD_ID, $this->entity->getRecordId());
    }
}
