<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Extension;

use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionSelector;
use Oro\Bundle\SecurityBundle\Annotation\Acl as AclAnnotation;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException;
use Symfony\Component\Security\Acl\Voter\FieldVote;

class AclExtensionSelectorTest extends \PHPUnit\Framework\TestCase
{
    /** @var AclExtensionSelector */
    protected $selector;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $entityExtension;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $fieldExtension;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $actionExtension;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $objectIdAccessor;

    protected function setUp()
    {
        $this->objectIdAccessor = $this->createMock(ObjectIdAccessor::class);

        $this->selector = new AclExtensionSelector($this->objectIdAccessor);

        $this->entityExtension = $this->getMockExtension('entity');
        $this->actionExtension = $this->getMockExtension('action');
        $this->fieldExtension = $this->getMockExtension('entity', false);

        $this->selector->addAclExtension($this->entityExtension);
        $this->selector->addAclExtension($this->actionExtension);
        $this->entityExtension->expects($this->any())
            ->method('getFieldExtension')
            ->willReturn($this->fieldExtension);
    }

    public function testSelectByExtensionKeyForExistingExtension()
    {
        $this->assertSame($this->actionExtension, $this->selector->selectByExtensionKey('action'));
    }

    public function testSelectByExtensionKeyForNotExistingExtension()
    {
        $this->assertNull($this->selector->selectByExtensionKey('not existing'));
    }

    public function testSelectWthNullValue()
    {
        $result = $this->selector->select(null);
        $this->assertInstanceOf('Oro\Bundle\SecurityBundle\Acl\Extension\NullAclExtension', $result);
    }

    public function testSelectEntityExtensionByStringValue()
    {
        $this->assertSame($this->entityExtension, $this->selector->select('entity:Test\Entity'));
    }

    public function testSelectActionExtensionByStringValue()
    {
        $this->assertSame($this->actionExtension, $this->selector->select('action:testAction'));
    }

    /**
     * @expectedException \Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException
     * @expectedExceptionMessage An ACL extension was not found for: wrong:testAction. Type: testAction. Id: wrong.
     */
    public function testSelectNotExistingExtensionByStringValue()
    {
        $this->selector->select('wrong:testAction');
    }

    public function testSelectNotExistingExtensionByStringValueAndThrowExceptionIsNotRequested()
    {
        self::assertNull($this->selector->select('wrong:testAction', false));
    }

    public function testSelectEntityExtensionByObjectIdentity()
    {
        $this->assertSame(
            $this->entityExtension,
            $this->selector->select(new ObjectIdentity('entity', 'Test\Entity'))
        );
    }

    public function testSelectActionExtensionByObjectIdentity()
    {
        $this->assertSame($this->actionExtension, $this->selector->select(new ObjectIdentity('action', 'testAction')));
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException
     * @expectedExceptionMessage An ACL extension was not found for: ObjectIdentity(wrong, testAction). Type: testAction. Id: wrong.
     */
    // @codingStandardsIgnoreEnd
    public function testSelectByWrongObjectIdentity()
    {
        $this->selector->select(new ObjectIdentity('wrong', 'testAction'));
    }

    public function testSelectByWrongObjectIdentityAndThrowExceptionIsNotRequested()
    {
        self::assertNull($this->selector->select(new ObjectIdentity('wrong', 'testAction'), false));
    }

    public function testSelectEntityExtensionByAclAnnotation()
    {
        $this->assertSame(
            $this->entityExtension,
            $this->selector->select(new AclAnnotation(['type' => 'entity', 'id' => 'Test\Entity']))
        );
    }

    public function testSelectActionExtensionByAclAnnotation()
    {
        $this->assertSame(
            $this->actionExtension,
            $this->selector->select(new AclAnnotation(['type' => 'action', 'id' => 'testAction']))
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException
     * @expectedExceptionMessage An ACL extension was not found for: Oro\Bundle\SecurityBundle\Annotation\Acl. Type: wrong. Id: testAction.
     */
    // @codingStandardsIgnoreEnd
    public function testSelectByWrongAclAnnotation()
    {
        $this->selector->select(new AclAnnotation(['id' => 'wrong', 'type' => 'testAction']));
    }

    public function testSelectByWrongAclAnnotationAndThrowExceptionIsNotRequested()
    {
        self::assertNull(
            $this->selector->select(new AclAnnotation(['id' => 'wrong', 'type' => 'testAction']), false)
        );
    }

    public function testSelectByFieldVote()
    {
        $this->fieldExtension->expects(self::once())
            ->method('supports')
            ->with('Test\Entity', 'entity')
            ->willReturn(true);

        $this->assertSame(
            $this->fieldExtension,
            $this->selector->select(new FieldVote(new ObjectIdentity('entity', 'Test\Entity'), 'test'))
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException
     * @expectedExceptionMessage An ACL extension was not found for: Symfony\Component\Security\Acl\Voter\FieldVote. Type: Test\Entity. Id: entity. Field: test.
     */
    // @codingStandardsIgnoreEnd
    public function testSelectByFieldVoteWhenFieldAclIsNotSupported()
    {
        $this->fieldExtension->expects(self::once())
            ->method('supports')
            ->with('Test\Entity', 'entity')
            ->willReturn(false);

        $this->assertSame(
            $this->fieldExtension,
            $this->selector->select(new FieldVote(new ObjectIdentity('entity', 'Test\Entity'), 'test'))
        );
    }

    public function testSelectByFieldVoteWhenFieldAclIsNotSupportedAndThrowExceptionIsNotRequested()
    {
        $this->fieldExtension->expects(self::once())
            ->method('supports')
            ->with('Test\Entity', 'entity')
            ->willReturn(false);

        $this->assertNull(
            $this->selector->select(new FieldVote(new ObjectIdentity('entity', 'Test\Entity'), 'test'), false)
        );
    }

    /**
     * @expectedException \Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException
     * @expectedExceptionMessage An ACL extension was not found for: stdClass. Type: . Id: .
     */
    public function testSelectByInvalidDomainObject()
    {
        $val = new \stdClass();

        $this->objectIdAccessor->expects(self::once())
            ->method('getId')
            ->with(self::identicalTo($val))
            ->willThrowException(new InvalidDomainObjectException());

        $this->selector->select($val);
    }

    public function testSelectByInvalidDomainObjectAndThrowExceptionIsNotRequested()
    {
        $val = new \stdClass();

        $this->objectIdAccessor->expects(self::once())
            ->method('getId')
            ->with(self::identicalTo($val))
            ->willThrowException(new InvalidDomainObjectException());

        self::assertNull($this->selector->select($val, false));
    }

    public function testSelectByInvalidDomainObjectAndThrowExceptionIsNotRequestedWhenPhpErrorOccurred()
    {
        $val = new \stdClass();

        $this->objectIdAccessor->expects(self::once())
            ->method('getId')
            ->with(self::identicalTo($val))
            ->willReturnCallback(function () {
                throw new \Error();
            });

        self::assertNull($this->selector->select($val, false));
    }

    public function testAll()
    {
        $result = $this->selector->all();
        $this->assertCount(2, $result);
    }

    /**
     * @param string $supportedType
     * @param bool   $setSupportsExpectation
     *
     * @return \Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getMockExtension($supportedType, $setSupportsExpectation = true)
    {
        $extension = $this->createMock('Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionInterface');
        if ($setSupportsExpectation) {
            $extension->expects($this->any())
                ->method('supports')
                ->willReturnCallback(
                    function ($type, $id) use ($supportedType) {
                        return $id === $supportedType;
                    }
                );
        }
        $extension->expects($this->any())
            ->method('getExtensionKey')
            ->willReturn($supportedType);

        return $extension;
    }
}
