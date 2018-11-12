<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Extension;

use Oro\Bundle\SecurityBundle\Acl\Extension\ActionAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Extension\ActionMaskBuilder;
use Oro\Bundle\SecurityBundle\Annotation\Acl as AclAnnotation;
use Oro\Bundle\SecurityBundle\Metadata\ActionMetadataProvider;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

class ActionAclExtensionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ActionMetadataProvider
     */
    protected $metadataProvider;

    /**
     * @var ActionAclExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->metadataProvider = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Metadata\ActionMetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new ActionAclExtension($this->metadataProvider);
    }

    protected function tearDown()
    {
        unset($this->metadataProvider, $this->extension);
    }

    /**
     * @dataProvider supportsDataProvider
     *
     * @param mixed $id
     * @param string $type
     * @param string $action
     * @param bool $isKnownAction
     * @param bool $expected
     */
    public function testSupports($id, $type, $action, $isKnownAction, $expected)
    {
        $this->metadataProvider->expects($isKnownAction ? $this->once() : $this->never())
            ->method('isKnownAction')
            ->with($action)
            ->willReturn($isKnownAction);

        $this->assertEquals($expected, $this->extension->supports($type, $id));
    }

    /**
     * @return array
     */
    public function supportsDataProvider()
    {
        return [
            [
                'id' => 'entity',
                'type' => '\stdClass',
                'action' => '\stdClass',
                'isKnownAction' => false,
                'expected' => false
            ],
            [
                'id' => 'action',
                'type' => 'action_id',
                'action' => 'action_id',
                'isKnownAction' => true,
                'expected' => true
            ],
            [
                'id' => 'action',
                'type' => '@action_id',
                'action' => 'action_id',
                'isKnownAction' => true,
                'expected' => true
            ],
            [
                'id' => 'action',
                'type' => 'group@action_id',
                'action' => 'action_id',
                'isKnownAction' => true,
                'expected' => true
            ]
        ];
    }

    /**
     * @dataProvider getObjectIdentityDataProvider
     *
     * @param mixed $val
     * @param ObjectIdentity $expected
     */
    public function testGetObjectIdentity($val, $expected)
    {
        $this->assertEquals($expected, $this->extension->getObjectIdentity($val));
    }

    /**
     * @return array
     */
    public function getObjectIdentityDataProvider()
    {
        $annotation = new AclAnnotation([
            'id' => 'action_id',
            'type' => 'action'
        ]);

        $annotation2 = new AclAnnotation([
            'id' => 'action_id',
            'type' => 'action',
            'group_name' => 'group'
        ]);

        return [
            [
                'val' => 'action:action_id',
                'expected' => new ObjectIdentity('action', 'action_id')
            ],
            [
                'val' => 'action:group@action_id',
                'expected' => new ObjectIdentity('action', 'group@action_id')
            ],
            [
                'val' => 'action:@action_id',
                'expected' => new ObjectIdentity('action', 'action_id')
            ],
            [
                'val' => $annotation,
                'expected' => new ObjectIdentity('action', 'action_id')
            ],
            [
                'val' => $annotation2,
                'expected' => new ObjectIdentity('action', 'group@action_id')
            ]
        ];
    }

    /**
     * @dataProvider getPermissionsProvider
     *
     * @param int|null $mask
     * @param bool $setOnly
     * @param bool $byCurrentGroup
     * @param array $expected
     */
    public function testGetPermissions($mask, $setOnly, $byCurrentGroup, array $expected)
    {
        $this->assertEquals($expected, $this->extension->getPermissions($mask, $setOnly, $byCurrentGroup));
    }

    /**
     * @return array
     */
    public function getPermissionsProvider()
    {
        return [
            'mask = 0 and setOnly' => [
                'mask' => 0,
                'setOnly' => true,
                'byCurrentGroup' => false,
                'expected' => [],
            ],
            'null mask and setOnly' => [
                'mask' => null,
                'setOnly' => true,
                'byCurrentGroup' => false,
                'expected' => ['EXECUTE'],
            ],
            'mask = 0 and not setOnly' => [
                'mask' => 0,
                'setOnly' => false,
                'byCurrentGroup' => false,
                'expected' => ['EXECUTE'],
            ],
            'mask = 1 and setOnly' => [
                'mask' => 1,
                'setOnly' => true,
                'byCurrentGroup' => false,
                'expected' => ['EXECUTE'],
            ],
            'mask = 0 and setOnly and byCurrentGroup' => [
                'mask' => 0,
                'setOnly' => true,
                'byCurrentGroup' => true,
                'expected' => [],
            ],
        ];
    }

    /**
     * @dataProvider getServiceBitsProvider
     */
    public function testGetServiceBits($mask, $expectedMask)
    {
        self::assertEquals($expectedMask, $this->extension->getServiceBits($mask));
    }

    public function getServiceBitsProvider()
    {
        return [
            'zero mask'                        => [
                ActionMaskBuilder::GROUP_NONE,
                ActionMaskBuilder::GROUP_NONE
            ],
            'not zero mask'                    => [
                ActionMaskBuilder::MASK_EXECUTE,
                ActionMaskBuilder::GROUP_NONE
            ],
            'zero mask, not zero identity'     => [
                ActionMaskBuilder::REMOVE_SERVICE_BITS + 1,
                ActionMaskBuilder::REMOVE_SERVICE_BITS + 1
            ],
            'not zero mask, not zero identity' => [
                (ActionMaskBuilder::REMOVE_SERVICE_BITS + 1) | ActionMaskBuilder::MASK_EXECUTE,
                ActionMaskBuilder::REMOVE_SERVICE_BITS + 1
            ],
        ];
    }

    /**
     * @dataProvider removeServiceBitsProvider
     */
    public function testRemoveServiceBits($mask, $expectedMask)
    {
        self::assertEquals($expectedMask, $this->extension->removeServiceBits($mask));
    }

    public function removeServiceBitsProvider()
    {
        return [
            'zero mask'                        => [
                ActionMaskBuilder::GROUP_NONE,
                ActionMaskBuilder::GROUP_NONE
            ],
            'not zero mask'                    => [
                ActionMaskBuilder::MASK_EXECUTE,
                ActionMaskBuilder::MASK_EXECUTE
            ],
            'zero mask, not zero identity'     => [
                ActionMaskBuilder::REMOVE_SERVICE_BITS + 1,
                ActionMaskBuilder::GROUP_NONE
            ],
            'not zero mask, not zero identity' => [
                (ActionMaskBuilder::REMOVE_SERVICE_BITS + 1) | ActionMaskBuilder::MASK_EXECUTE,
                ActionMaskBuilder::MASK_EXECUTE
            ],
        ];
    }
}
