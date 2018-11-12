<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Loggable;

use Oro\Bundle\DataAuditBundle\Loggable\AuditEntityMapper;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\UserBundle\Entity\User;

class AuditEntityMapperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var AuditEntityMapper
     */
    protected $mapper;

    protected function setUp()
    {
        $this->mapper = new AuditEntityMapper();
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Audit entry not found for "Oro\Bundle\UserBundle\Entity\User"
     */
    public function testShouldThrowExceptionIfAuditEntryIsRequestedInEmptyMap()
    {
        $this->mapper->getAuditEntryClass(new User());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Audit entry field not found for "Oro\Bundle\UserBundle\Entity\User"
     */
    public function testShouldThrowExceptionIfAuditEntryFieldIsRequestedInEmptyMap()
    {
        $this->mapper->getAuditEntryFieldClass(new User());
    }

    public function testShouldGetAuditEntryClass()
    {
        $user1 = $this->getMockForAbstractClass(AbstractUser::class);
        $user2 = new User();
        $this->mapper->addAuditEntryClasses(get_class($user1), 'Test\AuditEntry1', 'Test\AuditEntryField1');
        $this->mapper->addAuditEntryClasses(get_class($user2), 'Test\AuditEntry2', 'Test\AuditEntryField2');
        $this->assertEquals(
            'Test\AuditEntry2',
            $this->mapper->getAuditEntryClass($user2)
        );
    }

    public function testShouldGetAuditEntryFieldClass()
    {
        $user1 = $this->getMockForAbstractClass(AbstractUser::class);
        $user2 = new User();
        $this->mapper->addAuditEntryClasses(get_class($user1), 'Test\AuditEntry1', 'Test\AuditEntryField1');
        $this->mapper->addAuditEntryClasses(get_class($user2), 'Test\AuditEntry2', 'Test\AuditEntryField2');
        $this->assertEquals(
            'Test\AuditEntryField2',
            $this->mapper->getAuditEntryFieldClass($user2)
        );
    }

    public function testShouldBePossibleToOverrideAuditEntryClasses()
    {
        $user = new User();
        $this->mapper->addAuditEntryClasses(get_class($user), 'Test\AuditEntry1', 'Test\AuditEntryField1');
        $this->mapper->addAuditEntryClasses(get_class($user), 'Test\AuditEntry2', 'Test\AuditEntryField2');
        $this->assertEquals(
            'Test\AuditEntry2',
            $this->mapper->getAuditEntryClass($user)
        );
        $this->assertEquals(
            'Test\AuditEntryField2',
            $this->mapper->getAuditEntryFieldClass($user)
        );
    }

    public function testShouldBePossibleToOverrideOnlyAuditEntryClass()
    {
        $user = new User();
        $this->mapper->addAuditEntryClasses(get_class($user), 'Test\AuditEntry1', 'Test\AuditEntryField1');
        $this->mapper->addAuditEntryClass(get_class($user), 'Test\AuditEntry2');
        $this->assertEquals(
            'Test\AuditEntry2',
            $this->mapper->getAuditEntryClass($user)
        );
        $this->assertEquals(
            'Test\AuditEntryField1',
            $this->mapper->getAuditEntryFieldClass($user)
        );
    }

    public function testShouldBePossibleToOverrideOnlyAuditEntryFieldClass()
    {
        $user = new User();
        $this->mapper->addAuditEntryClasses(get_class($user), 'Test\AuditEntry1', 'Test\AuditEntryField1');
        $this->mapper->addAuditEntryFieldClass(get_class($user), 'Test\AuditEntryField2');
        $this->assertEquals(
            'Test\AuditEntry1',
            $this->mapper->getAuditEntryClass($user)
        );
        $this->assertEquals(
            'Test\AuditEntryField2',
            $this->mapper->getAuditEntryFieldClass($user)
        );
    }

    public function testShouldGetDefaultAuditEntryClassIfUserEntityIsNull()
    {
        $user1 = $this->getMockForAbstractClass(AbstractUser::class);
        $user2 = new User();
        $this->mapper->addAuditEntryClasses(get_class($user1), 'Test\AuditEntry1', 'Test\AuditEntryField1');
        $this->mapper->addAuditEntryClasses(get_class($user2), 'Test\AuditEntry2', 'Test\AuditEntryField2');
        $this->assertEquals(
            'Test\AuditEntry1',
            $this->mapper->getAuditEntryClass()
        );
    }

    public function testShouldGetDefaultAuditEntryFieldClassIfUserEntityIsNull()
    {
        $user1 = $this->getMockForAbstractClass(AbstractUser::class);
        $user2 = new User();
        $this->mapper->addAuditEntryClasses(get_class($user1), 'Test\AuditEntry1', 'Test\AuditEntryField1');
        $this->mapper->addAuditEntryClasses(get_class($user2), 'Test\AuditEntry2', 'Test\AuditEntryField2');
        $this->assertEquals(
            'Test\AuditEntryField1',
            $this->mapper->getAuditEntryFieldClass()
        );
    }

    public function testShouldGetAuditEntryFieldClassForAuditEntry()
    {
        $user1 = $this->getMockForAbstractClass(AbstractUser::class);
        $user2 = new User();
        $this->mapper->addAuditEntryClasses(get_class($user1), 'Test\AuditEntry1', 'Test\AuditEntryField1');
        $this->mapper->addAuditEntryClasses(get_class($user2), 'Test\AuditEntry2', 'Test\AuditEntryField2');
        $this->assertEquals(
            'Test\AuditEntryField2',
            $this->mapper->getAuditEntryFieldClassForAuditEntry('Test\AuditEntry2')
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Audit entry field not found for "Test\AuditEntry2"
     */
    public function testShouldThrowExceptionIfAuditEntryFieldCannotBeFound()
    {
        $user1 = $this->getMockForAbstractClass(AbstractUser::class);
        $this->mapper->addAuditEntryClasses(get_class($user1), 'Test\AuditEntry1', 'Test\AuditEntryField1');

        $this->mapper->getAuditEntryFieldClassForAuditEntry('Test\AuditEntry2');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Audit entry field not found for "Test\AuditEntry3"
     */
    public function testShouldThrowExceptionIfAuditEntryFieldCannotBeFoundDueInvalidMap()
    {
        $user1 = $this->getMockBuilder(AbstractUser::class)
            ->setMockClassName('User1')
            ->getMockForAbstractClass();
        $user2 = $this->getMockBuilder(AbstractUser::class)
            ->setMockClassName('User2')
            ->getMockForAbstractClass();
        $user3 = $this->getMockBuilder(AbstractUser::class)
            ->setMockClassName('User3')
            ->getMockForAbstractClass();
        $this->mapper->addAuditEntryFieldClass(get_class($user1), 'Test\AuditEntryField1');
        $this->mapper->addAuditEntryClass(get_class($user2), 'Test\AuditEntry2');
        $this->mapper->addAuditEntryClass(get_class($user3), 'Test\AuditEntry3');

        // guard
        $this->assertAttributeCount(3, 'map', $this->mapper);

        $this->mapper->getAuditEntryFieldClassForAuditEntry('Test\AuditEntry3');
    }
}
