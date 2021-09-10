<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Extension;

use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionInterface;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionSelector;
use Oro\Bundle\SecurityBundle\Acl\Extension\NullAclExtension;
use Oro\Bundle\SecurityBundle\Annotation\Acl as AclAnnotation;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException;
use Symfony\Component\Security\Acl\Voter\FieldVote;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AclExtensionSelectorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ObjectIdAccessor|\PHPUnit\Framework\MockObject\MockObject */
    private $objectIdAccessor;

    /** @var AclExtensionInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $entityExtension;

    /** @var AclExtensionInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $fieldExtension;

    /** @var AclExtensionInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $actionExtension;

    /** @var AclExtensionSelector */
    private $selector;

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

    /**
     * @return AclExtensionInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getMockExtension(string $supportedType, bool $setSupportsExpectation = true)
    {
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
        $this->assertInstanceOf(NullAclExtension::class, $result);
    }

    public function testSelectEntityExtensionByStringValue()
    {
        $this->assertSame($this->entityExtension, $this->selector->select('entity:Test\Entity'));
    }

    public function testSelectActionExtensionByStringValue()
    {
        $this->assertSame($this->actionExtension, $this->selector->select('action:testAction'));
    }

    public function testSelectNotExistingExtensionByStringValue()
    {
        $this->expectException(InvalidDomainObjectException::class);
        $this->expectExceptionMessage(
            'An ACL extension was not found for: wrong:testAction. Type: testAction. Id: wrong.'
        );

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

    public function testSelectByWrongObjectIdentity()
    {
        $this->expectException(InvalidDomainObjectException::class);
        $this->expectExceptionMessage(
            'An ACL extension was not found for: ObjectIdentity(wrong, testAction). Type: testAction. Id: wrong.'
        );

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

    public function testSelectByWrongAclAnnotation()
    {
        $this->expectException(InvalidDomainObjectException::class);
        $this->expectExceptionMessage(
            'An ACL extension was not found for: Oro\Bundle\SecurityBundle\Annotation\Acl. Type: wrong. Id: testAction.'
        );

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

    public function testSelectByFieldVoteWhenFieldAclIsNotSupported()
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

    public function testSelectByInvalidDomainObject()
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
}
