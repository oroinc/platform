<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\WorkflowBundle\Configuration\Checker\ConfigurationChecker;
use Oro\Bundle\WorkflowBundle\Datagrid\ActionPermissionProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ActionPermissionProviderTest extends TestCase
{
    private FeatureChecker&MockObject $featureChecker;
    private ConfigurationChecker&MockObject $configurationChecker;
    private ActionPermissionProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->configurationChecker = $this->createMock(ConfigurationChecker::class);

        $this->provider = new ActionPermissionProvider($this->featureChecker, $this->configurationChecker);
    }

    /**
     * @dataProvider getWorkflowDefinitionPermissionsDataProvider
     */
    public function testGetWorkflowDefinitionPermissionsSystemRelated(
        array $expected,
        ResultRecordInterface $input,
        bool $featureEnabled,
        bool $configurationClean
    ): void {
        $this->featureChecker->expects($this->any())
            ->method('isResourceEnabled')
            ->willReturn($featureEnabled);
        $this->configurationChecker->expects($this->any())
            ->method('isClean')
            ->willReturn($configurationClean);

        $this->assertEquals($expected, $this->provider->getWorkflowDefinitionPermissions($input));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getWorkflowDefinitionPermissionsDataProvider(): array
    {
        $systemDefinition = $this->createMock(ResultRecordInterface::class);
        $systemDefinition->expects($this->any())
            ->method('getValue')
            ->willReturnMap([
                ['name', 'test'],
                ['system', true],
                ['configuration', []],
            ]);

        $regularDefinition = $this->createMock(ResultRecordInterface::class);
        $regularDefinition->expects($this->any())
            ->method('getValue')
            ->willReturnMap([
                ['name', 'test'],
                ['system', false],
                ['configuration', []],
            ]);

        return [
            'system definition' => [
                'expected' => [
                    'view' => true,
                    'update' => false,
                    'clone'  => true,
                    'delete' => false,
                    'activate' => true,
                    'deactivate' => false
                ],
                'input' => $systemDefinition,
                'featureEnabled' => true,
                'configurationClean' => true
            ],
            'system definition not clean' => [
                'expected' => [
                    'view' => true,
                    'update' => false,
                    'clone'  => false,
                    'delete' => false,
                    'activate' => true,
                    'deactivate' => false
                ],
                'input' => $systemDefinition,
                'featureEnabled' => true,
                'configurationClean' => false
            ],
            'regular definition' => [
                'expected' => [
                    'view' => true,
                    'update' => true,
                    'clone'  => true,
                    'delete' => true,
                    'activate' => true,
                    'deactivate' => false
                ],
                'input' => $regularDefinition,
                'featureEnabled' => true,
                'configurationClean' => true
            ],
            'regular definition not clean' => [
                'expected' => [
                    'view' => true,
                    'update' => false,
                    'clone'  => false,
                    'delete' => true,
                    'activate' => true,
                    'deactivate' => false
                ],
                'input' => $regularDefinition,
                'featureEnabled' => true,
                'configurationClean' => false
            ],
            'system definition feature disabled' => [
                'expected' => [
                    'view' => true,
                    'update' => false,
                    'clone'  => true,
                    'delete' => false,
                    'activate' => false,
                    'deactivate' => false
                ],
                'input' => $systemDefinition,
                'featureEnabled' => false,
                'configurationClean' => true
            ],
            'system definition feature disabled not clean' => [
                'expected' => [
                    'view' => true,
                    'update' => false,
                    'clone'  => false,
                    'delete' => false,
                    'activate' => false,
                    'deactivate' => false
                ],
                'input' => $systemDefinition,
                'featureEnabled' => false,
                'configurationClean' => false
            ],
            'regular definition feature disabled' => [
                'expected' => [
                    'view' => true,
                    'update' => false,
                    'clone'  => true,
                    'delete' => true,
                    'activate' => false,
                    'deactivate' => false
                ],
                'input' => $regularDefinition,
                'featureEnabled' => false,
                'configurationClean' => true
            ],
            'regular definition feature disabled not clean' => [
                'expected' => [
                    'view' => true,
                    'update' => false,
                    'clone'  => false,
                    'delete' => true,
                    'activate' => false,
                    'deactivate' => false
                ],
                'input' => $regularDefinition,
                'featureEnabled' => false,
                'configurationClean' => false
            ]
        ];
    }

    /**
     * @dataProvider getWorkflowDefinitionActivationDataProvider
     */
    public function testGetWorkflowDefinitionPermissionsActivationRelated(
        array $expected,
        ResultRecordInterface $input,
        bool $featureEnabled,
        bool $configurationClean
    ): void {
        $this->featureChecker->expects($this->any())
            ->method('isResourceEnabled')
            ->willReturn($featureEnabled);
        $this->configurationChecker->expects($this->any())
            ->method('isClean')
            ->willReturn($configurationClean);

        $this->assertEquals($expected, $this->provider->getWorkflowDefinitionPermissions($input));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getWorkflowDefinitionActivationDataProvider(): array
    {
        return [
            'no config' => [
                'expected' => [
                    'view' => true,
                    'update' => true,
                    'clone'  => true,
                    'delete' => true,
                    'activate' => true,
                    'deactivate' => false
                ],
                'input' => $this->getDefinition(false),
                'featureEnabled' => true,
                'configurationClean' => true
            ],
            'no config not clean' => [
                'expected' => [
                    'view' => true,
                    'update' => false,
                    'clone'  => false,
                    'delete' => true,
                    'activate' => true,
                    'deactivate' => false
                ],
                'input' => $this->getDefinition(false),
                'featureEnabled' => true,
                'configurationClean' => false
            ],
            'active definition' => [
                'expected' => [
                    'view' => true,
                    'update' => false,
                    'clone'  => true,
                    'delete' => true,
                    'activate' => false,
                    'deactivate' => true
                ],
                'input' => $this->getDefinition(true),
                'featureEnabled' => true,
                'configurationClean' => true
            ],
            'active definition not clean' => [
                'expected' => [
                    'view' => true,
                    'update' => false,
                    'clone'  => false,
                    'delete' => true,
                    'activate' => false,
                    'deactivate' => true
                ],
                'input' => $this->getDefinition(true),
                'featureEnabled' => true,
                'configurationClean' => false
            ],
            'inactive definition' => [
                'expected' => [
                    'view' => true,
                    'update' => true,
                    'clone'  => true,
                    'delete' => true,
                    'activate' => true,
                    'deactivate' => false
                ],
                'input' => $this->getDefinition(false),
                'featureEnabled' => true,
                'configurationClean' => true
            ],
            'inactive definition not clean' => [
                'expected' => [
                    'view' => true,
                    'update' => false,
                    'clone'  => false,
                    'delete' => true,
                    'activate' => true,
                    'deactivate' => false
                ],
                'input' => $this->getDefinition(false),
                'featureEnabled' => true,
                'configurationClean' => false
            ],
            'no config feature disabled' => [
                'expected' => [
                    'view' => true,
                    'update' => false,
                    'clone'  => true,
                    'delete' => true,
                    'activate' => false,
                    'deactivate' => false
                ],
                'input' => $this->getDefinition(false),
                'featureEnabled' => false,
                'configurationClean' => true
            ],
            'no config feature disabled not clean' => [
                'expected' => [
                    'view' => true,
                    'update' => false,
                    'clone'  => false,
                    'delete' => true,
                    'activate' => false,
                    'deactivate' => false
                ],
                'input' => $this->getDefinition(false),
                'featureEnabled' => false,
                'configurationClean' => false
            ],
            'active definition feature disabled' => [
                'expected' => [
                    'view' => true,
                    'update' => false,
                    'clone'  => true,
                    'delete' => true,
                    'activate' => false,
                    'deactivate' => false
                ],
                'input' => $this->getDefinition(true),
                'featureEnabled' => false,
                'configurationClean' => true
            ],
            'active definition feature disabled not clean' => [
                'expected' => [
                    'view' => true,
                    'update' => false,
                    'clone'  => false,
                    'delete' => true,
                    'activate' => false,
                    'deactivate' => false
                ],
                'input' => $this->getDefinition(true),
                'featureEnabled' => false,
                'configurationClean' => false
            ],
            'inactive definition feature disabled' => [
                'expected' => [
                    'view' => true,
                    'update' => false,
                    'clone'  => true,
                    'delete' => true,
                    'activate' => false,
                    'deactivate' => false
                ],
                'input' => $this->getDefinition(false),
                'featureEnabled' => false,
                'configurationClean' => true
            ],
            'inactive definition feature disabled not clean' => [
                'expected' => [
                    'view' => true,
                    'update' => false,
                    'clone'  => false,
                    'delete' => true,
                    'activate' => false,
                    'deactivate' => false
                ],
                'input' => $this->getDefinition(false),
                'featureEnabled' => false,
                'configurationClean' => false
            ]
        ];
    }

    private function getDefinition(bool $active): ResultRecordInterface
    {
        $definition = $this->createMock(ResultRecordInterface::class);
        $definition->expects($this->any())
            ->method('getValue')
            ->willReturnMap([
                ['active', $active],
                ['name', 'workflow_name'],
                ['entityClass', \stdClass::class],
                ['configuration', []]
            ]);

        return $definition;
    }
}
