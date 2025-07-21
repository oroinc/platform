<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\FieldAcl;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Extension\FieldAcl\FieldAclExtension;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SecurityBundle\Owner\OwnershipQueryHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class FieldAclExtensionTest extends TestCase
{
    private AuthorizationCheckerInterface&MockObject $authorizationChecker;
    private ConfigManager&MockObject $configManager;
    private OwnershipQueryHelper&MockObject $ownershipQueryHelper;
    private FieldAclExtension $extension;

    #[\Override]
    protected function setUp(): void
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

    public function testIsApplicableOnValidConfig(): void
    {
        $config = DatagridConfiguration::create(['source' => ['type' => 'orm']]);
        $this->assertTrue($this->extension->isApplicable($config));
    }

    public function testIsApplicableOnNonValidConfig(): void
    {
        $config = DatagridConfiguration::create(['source' => ['type' => 'search']]);
        $this->assertFalse($this->extension->isApplicable($config));
    }

    public function testGetPriority(): void
    {
        $this->assertEquals(255, $this->extension->getPriority());
    }

    public function testProcessConfigs(): void
    {
        $config = DatagridConfiguration::create(
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
