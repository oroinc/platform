<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\FieldAcl;

use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Extension\FieldAcl\FieldAclExtension;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SecurityBundle\Owner\OwnershipQueryHelper;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class FieldAclExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigManager */
    protected $configManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|OwnershipQueryHelper */
    protected $ownershipQueryHelper;

    /** @var FieldAclExtension */
    protected $extension;

    public function setUp()
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->ownershipQueryHelper = $this->createMock(OwnershipQueryHelper::class);

        $this->extension = new FieldAclExtension(
            $this->authorizationChecker,
            $this->configManager,
            $this->ownershipQueryHelper
        );

        $this->extension->setParameters(new ParameterBag());
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
