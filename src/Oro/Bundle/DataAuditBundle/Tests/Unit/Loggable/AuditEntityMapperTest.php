<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Loggable;

use Oro\Bundle\DataAuditBundle\Loggable\AuditEntityMapper;
use Oro\Bundle\UserBundle\Entity\User;

class AuditEntityMapperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AuditEntityMapper
     */
    protected $mapper;

    protected function setUp()
    {
        $this->mapper = new AuditEntityMapper();
    }

    protected function tearDown()
    {
        unset($this->mapper);
    }

    public function testClasses()
    {
        $this->mapper->addAuditEntryClass('Oro\Bundle\UserBundle\Entity\User', '\stdClass');
        $this->assertEquals(
            '\stdClass',
            $this->mapper->getAuditEntryClass($this->getUser())
        );

        $this->mapper->addAuditEntryFieldClass('Oro\Bundle\UserBundle\Entity\User', '\stdClass');
        $this->assertEquals(
            '\stdClass',
            $this->mapper->getAuditEntryFieldClass($this->getUser())
        );
    }

    public function testOverrideClasses()
    {
        $this->mapper->addAuditEntryClass('Oro\Bundle\UserBundle\Entity\User', '\stdClass');
        $this->assertEquals(
            '\stdClass',
            $this->mapper->getAuditEntryClass($this->getUser())
        );

        $this->mapper->addAuditEntryClass('Oro\Bundle\UserBundle\Entity\User', '\stdClass2');
        $this->assertEquals(
            '\stdClass2',
            $this->mapper->getAuditEntryClass($this->getUser())
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Audit entry not found for "Oro\Bundle\UserBundle\Entity\User"
     */
    public function testAuditEntryFailed()
    {
        $this->mapper->getAuditEntryClass($this->getUser());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Audit entry field not found for "Oro\Bundle\UserBundle\Entity\User"
     */
    public function testAuditEntryFieldFailed()
    {
        $this->mapper->getAuditEntryFieldClass($this->getUser());
    }

    /**
     * @return User
     */
    protected function getUser()
    {
        return new User();
    }
}
