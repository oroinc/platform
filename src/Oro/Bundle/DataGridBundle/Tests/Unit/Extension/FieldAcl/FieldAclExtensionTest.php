<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\FieldAcl;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use Oro\Bundle\DataGridBundle\Extension\FieldAcl\FieldAclExtension;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;

class FieldAclExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|OwnershipMetadataProvider */
    protected $ownershipMetadataProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EntityClassResolver */
    protected $entityClassResolver;

    /** @var \PHPUnit_Framework_MockObject_MockObject|AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigProvider */
    protected $configProvider;

    /** @var FieldAclExtension */
    protected $extension;

    public function setUp()
    {
        $this->ownershipMetadataProvider = $this
            ->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityClassResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $this->authorizationChecker = $this
            ->getMock('Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface');

        $this->configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new FieldAclExtension(
            $this->ownershipMetadataProvider,
            $this->entityClassResolver,
            $this->authorizationChecker,
            $this->configProvider
        );
    }

    public function testIsApplicableOnValidConfig()
    {
        $config = new TestGridConfiguration(['source' => ['type' => 'orm']]);
        $this->assertTrue($this->extension->isApplicable($config));
    }

    public function testIsApplicableOnNonValidConfig()
    {
        $config = new TestGridConfiguration(['source' => ['type' => 'search']]);
        $this->assertFalse($this->extension->isApplicable($config));
    }

    public function testGetPriority()
    {
        $this->assertEquals(255, $this->extension->getPriority());
    }

    public function testProcessConfigs()
    {
        $config = new TestGridConfiguration(
            [
                'source' => ['type' => 'orm'],
                'fields_acl' =>
                    [
                         'columns' => [
                             'first' => null,
                             'second' => false,
                             'third' => true,
                             'fourth' => ['data_name' => 'a.fourth'],
                             'fifth' => ['data_name' => 'a.fifth', 'disabled' => true],
                             'sixth' => ['data_name' => 'a.sixth', 'disabled' => false],
                         ]
                    ]
            ]
        );

        $this->extension->processConfigs($config);

        $this->assertEquals(
            [
                'columns' => [
                    'first' => ['disabled' => false],
                    'second' => ['disabled' => true],
                    'third' => ['disabled' => false],
                    'fourth' => ['data_name' => 'a.fourth', 'disabled' => false],
                    'fifth' => ['data_name' => 'a.fifth', 'disabled' => true],
                    'sixth' => ['data_name' => 'a.sixth', 'disabled' => false],
                ]
            ],
            $config->offsetGetByPath('[fields_acl]')
        );
    }
}
