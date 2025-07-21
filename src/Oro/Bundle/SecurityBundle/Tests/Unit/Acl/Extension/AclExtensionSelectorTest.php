<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Extension;

use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionInterface;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionSelector;
use Oro\Bundle\SecurityBundle\Acl\Extension\NullAclExtension;
use Oro\Bundle\SecurityBundle\Attribute\Acl as AclAttribute;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException;
use Symfony\Component\Security\Acl\Voter\FieldVote;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AclExtensionSelectorTest extends TestCase
{
    private ObjectIdAccessor&MockObject $objectIdAccessor;
    private AclExtensionInterface&MockObject $entityExtension;
    private AclExtensionInterface&MockObject $fieldExtension;
    private AclExtensionInterface&MockObject $actionExtension;
    private AclExtensionSelector $selector;

    #[\Override]
    protected function setUp(): void
    {
        $this->objectIdAccessor = $this->createMock(ObjectIdAccessor::class);
        $this->entityExtension = $this->getMockExtension('entity');
        $this->actionExtension = $this->getMockExtension('action');
        $this->fieldExtension = $this->getMockExtension('entity', false);

        $this->entityExtension->expects($this->any())
            ->method('getFieldExtension')
            ->willReturn($this->fieldExtension);

        $container = TestContainerBuilder::create()
            ->add('action_acl_extension', $this->actionExtension)
            ->add('entity_acl_extension', $this->entityExtension)
            ->getContainer($this);

        $this->selector = new AclExtensionSelector(
            ['action_acl_extension', 'entity_acl_extension'],
            $container,
            $this->objectIdAccessor
        );
    }

    private function getMockExtension(
        string $supportedType,
        bool $setSupportsExpectation = true
    ): AclExtensionInterface&MockObject {
        $extension = $this->createMock(AclExtensionInterface::class);
        if ($setSupportsExpectation) {
            $extension->expects($this->any())
                ->method('supports')
                ->willReturnCallback(function ($type, $id) use ($supportedType) {
                    return $id === $supportedType;
                });
        }
        $extension->expects($this->any())
            ->method('getExtensionKey')
            ->willReturn($supportedType);

        return $extension;
    }

    public function testSelectByExtensionKeyForExistingExtension(): void
    {
        $this->assertSame($this->actionExtension, $this->selector->selectByExtensionKey('action'));
    }

    public function testSelectByExtensionKeyForNotExistingExtension(): void
    {
        $this->assertNull($this->selector->selectByExtensionKey('not existing'));
    }

    public function testSelectWthNullValue(): void
    {
        $result = $this->selector->select(null);
        $this->assertInstanceOf(NullAclExtension::class, $result);
    }

    public function testSelectEntityExtensionByStringValue(): void
    {
        $this->assertSame($this->entityExtension, $this->selector->select('entity:Test\Entity'));
    }

    public function testSelectActionExtensionByStringValue(): void
    {
        $this->assertSame($this->actionExtension, $this->selector->select('action:testAction'));
    }

    public function testSelectNotExistingExtensionByStringValue(): void
    {
        $this->expectException(InvalidDomainObjectException::class);
        $this->expectExceptionMessage(
            'An ACL extension was not found for: wrong:testAction. Type: testAction. Id: wrong.'
        );

        $this->selector->select('wrong:testAction');
    }

    public function testSelectNotExistingExtensionByStringValueAndThrowExceptionIsNotRequested(): void
    {
        self::assertNull($this->selector->select('wrong:testAction', false));
    }

    public function testSelectEntityExtensionByObjectIdentity(): void
    {
        $this->assertSame(
            $this->entityExtension,
            $this->selector->select(new ObjectIdentity('entity', 'Test\Entity'))
        );
    }

    public function testSelectActionExtensionByObjectIdentity(): void
    {
        $this->assertSame($this->actionExtension, $this->selector->select(new ObjectIdentity('action', 'testAction')));
    }

    public function testSelectByWrongObjectIdentity(): void
    {
        $this->expectException(InvalidDomainObjectException::class);
        $this->expectExceptionMessage(
            'An ACL extension was not found for: ObjectIdentity(wrong, testAction). Type: testAction. Id: wrong.'
        );

        $this->selector->select(new ObjectIdentity('wrong', 'testAction'));
    }

    public function testSelectByWrongObjectIdentityAndThrowExceptionIsNotRequested(): void
    {
        self::assertNull($this->selector->select(new ObjectIdentity('wrong', 'testAction'), false));
    }

    public function testSelectEntityExtensionByAclAttribute(): void
    {
        $this->assertSame(
            $this->entityExtension,
            $this->selector->select(AclAttribute::fromArray(['type' => 'entity', 'id' => 'Test\Entity']))
        );
    }

    public function testSelectActionExtensionByAclAttribute(): void
    {
        $this->assertSame(
            $this->actionExtension,
            $this->selector->select(AclAttribute::fromArray(['type' => 'action', 'id' => 'testAction']))
        );
    }

    public function testSelectByWrongAclAttribute(): void
    {
        $this->expectException(InvalidDomainObjectException::class);
        $this->expectExceptionMessage(
            'An ACL extension was not found for: Oro\Bundle\SecurityBundle\Attribute\Acl. Type: wrong. Id: testAction.'
        );

        $this->selector->select(AclAttribute::fromArray(['id' => 'wrong', 'type' => 'testAction']));
    }

    public function testSelectByWrongAclAttributeAndThrowExceptionIsNotRequested(): void
    {
        self::assertNull(
            $this->selector->select(AclAttribute::fromArray(['id' => 'wrong', 'type' => 'testAction']), false)
        );
    }

    public function testSelectByFieldVote(): void
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

    public function testSelectByFieldVoteWhenFieldAclIsNotSupported(): void
    {
        $this->expectException(InvalidDomainObjectException::class);
        $this->expectExceptionMessage(
            'An ACL extension was not found for: Symfony\Component\Security\Acl\Voter\FieldVote.'
            . ' Type: Test\Entity. Id: entity. Field: test.'
        );

        $this->fieldExtension->expects(self::once())
            ->method('supports')
            ->with('Test\Entity', 'entity')
            ->willReturn(false);

        $this->assertSame(
            $this->fieldExtension,
            $this->selector->select(new FieldVote(new ObjectIdentity('entity', 'Test\Entity'), 'test'))
        );
    }

    public function testSelectByFieldVoteWhenFieldAclIsNotSupportedAndThrowExceptionIsNotRequested(): void
    {
        $this->fieldExtension->expects(self::once())
            ->method('supports')
            ->with('Test\Entity', 'entity')
            ->willReturn(false);

        $this->assertNull(
            $this->selector->select(new FieldVote(new ObjectIdentity('entity', 'Test\Entity'), 'test'), false)
        );
    }

    public function testSelectByInvalidDomainObject(): void
    {
        $this->expectException(InvalidDomainObjectException::class);
        $this->expectExceptionMessage('An ACL extension was not found for: stdClass. Type: . Id: .');

        $val = new \stdClass();

        $this->objectIdAccessor->expects(self::once())
            ->method('getId')
            ->with(self::identicalTo($val))
            ->willThrowException(new InvalidDomainObjectException());

        $this->selector->select($val);
    }

    public function testSelectByInvalidDomainObjectAndThrowExceptionIsNotRequested(): void
    {
        $val = new \stdClass();

        $this->objectIdAccessor->expects(self::once())
            ->method('getId')
            ->with(self::identicalTo($val))
            ->willThrowException(new InvalidDomainObjectException());

        self::assertNull($this->selector->select($val, false));
    }

    public function testSelectByInvalidDomainObjectAndThrowExceptionIsNotRequestedWhenPhpErrorOccurred(): void
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

    public function testAll(): void
    {
        $result = $this->selector->all();
        $this->assertCount(2, $result);
    }
}
