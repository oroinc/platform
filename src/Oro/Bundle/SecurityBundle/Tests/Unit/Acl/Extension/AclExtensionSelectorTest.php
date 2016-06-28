<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Extension;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionSelector;
use Oro\Bundle\SecurityBundle\Annotation\Acl as AclAnnotation;
use Symfony\Component\Security\Acl\Voter\FieldVote;

class AclExtensionSelectorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AclExtensionSelector
     */
    protected $selector;

    protected $entityExtension;
    protected $actionExtension;
    protected $fieldExtension;

    protected function setUp()
    {
        $objectIdAccessor = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdAccessor')
            ->disableOriginalConstructor()
            ->getMock();

        $this->selector = new AclExtensionSelector($objectIdAccessor);

        $this->entityExtension = $this->getMockExtension('entity');
        $this->actionExtension = $this->getMockExtension('action');
        $this->fieldExtension = $this->getMockExtension('field');

        $this->selector->addAclExtension($this->entityExtension);
        $this->selector->addAclExtension($this->actionExtension);
        $this->selector->addAclExtension($this->fieldExtension);

    }

    public function testSelectByExtensionKey()
    {
        $this->assertSame($this->entityExtension, $this->selector->selectByExtensionKey('entity'));
        $this->assertSame($this->actionExtension, $this->selector->selectByExtensionKey('action'));
        $this->assertNull($this->selector->selectByExtensionKey('not existing'));
    }

    public function testSelectWthNullValue()
    {
        $result = $this->selector->select(null);
        $this->assertInstanceOf('Oro\Bundle\SecurityBundle\Acl\Extension\NullAclExtension', $result);
    }

    public function testSelectWithStringValue()
    {
        $this->assertSame($this->entityExtension, $this->selector->select('entity:\test\entity'));
        $this->assertSame($this->actionExtension, $this->selector->select('action:testAction'));
    }

    /**
     * @expectedException \Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException
     * @expectedExceptionMessage An ACL extension was not found for: wrong:testAction. Type: testAction. Id: wrong
     */
    public function testSelectWithNonExistingStringType()
    {
        $this->selector->select('wrong:testAction');
    }

    public function testSelectWithObjectIdentity()
    {
        $this->assertSame(
            $this->entityExtension,
            $this->selector->select(new ObjectIdentity('entity', '\test\entity'))
        );
        $this->assertSame($this->actionExtension, $this->selector->select(new ObjectIdentity('action', 'testAction')));
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException
     * @expectedExceptionMessage An ACL extension was not found for: ObjectIdentity(wrong, testAction). Type: testAction. Id: wrong
     */
    // @codingStandardsIgnoreEnd
    public function testSelectWithWrongObjectIdentity()
    {
        $this->selector->select(new ObjectIdentity('wrong', 'testAction'));
    }

    public function testSelectWithAclAnnotation()
    {
        $this->assertSame(
            $this->entityExtension,
            $this->selector->select(new AclAnnotation(['type' => 'entity', 'id' => '\test\entity']))
        );
        $this->assertSame(
            $this->actionExtension,
            $this->selector->select(new AclAnnotation(['type' => 'action', 'id' => 'testAction']))
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException
     * @expectedExceptionMessage An ACL extension was not found for: Oro\Bundle\SecurityBundle\Annotation\Acl. Type: wrong. Id: testAction
     */
    // @codingStandardsIgnoreEnd
    public function testSelectWithWrongAclAnnotation()
    {
        $this->selector->select(new AclAnnotation(['id' => 'wrong', 'type' => 'testAction']));
    }

    public function testSelectWithFieldVote()
    {
        $this->assertSame(
            $this->fieldExtension,
            $this->selector->select(new FieldVote(new \stdClass(), 'test'))
        );
    }

    public function testAll()
    {
        $result = $this->selector->all();
        $this->assertCount(3, $result);
    }

    /**
     * @param string $supportedType
     *
     * @return \Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockExtension($supportedType)
    {
        $extension = $this->getMock('Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionInterface');
        $extension->expects($this->any())
            ->method('supports')
            ->willReturnCallback(
                function ($type, $id) use ($supportedType) {
                    return $id === $supportedType;
                }
            );
        $extension->expects($this->any())
            ->method('getExtensionKey')
            ->willReturn($supportedType);

        return $extension;
    }
}
