<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\EmailBundle\Controller\Api\Rest as Api;
use Oro\Bundle\EmailBundle\DependencyInjection\OroEmailExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use Oro\Component\DependencyInjection\ExtendedContainerBuilder;

class OroEmailExtensionTest extends ExtensionTestCase
{
    private OroEmailExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new OroEmailExtension();
    }

    /**
     * @dataProvider prependSecurityConfigDataProvider
     */
    public function testPrepend(array $securityConfig, array $expectedSecurityConfig): void
    {
        $containerBuilder = $this->createMock(ExtendedContainerBuilder::class);
        $containerBuilder->expects(self::once())
            ->method('getExtensionConfig')
            ->with('nelmio_security')
            ->willReturn($securityConfig);
        $containerBuilder->expects(self::once())
            ->method('setExtensionConfig')
            ->with('nelmio_security')
            ->willReturn($expectedSecurityConfig);

        $this->extension->prepend($containerBuilder);
    }

    public function prependSecurityConfigDataProvider(): array
    {
        return [
            [
                'securityConfig' => [
                    [
                        'clickjacking' => [
                            'paths' => ['sample/path' => 'ALLOW'],
                        ],
                    ],
                ],
                'expectedSecurityConfig' => [
                    [
                        'clickjacking' => [
                            'paths' => [
                                'sample/path' => 'ALLOW',
                                '/email/emailtemplate/preview' => 'ALLOW',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'securityConfig' => [
                    [
                        'clickjacking' => [],
                    ],
                ],
                'expectedSecurityConfig' => [
                    [
                        'clickjacking' => [],
                    ],
                ],
            ],
        ];
    }

    public function testLoad(): void
    {
        $this->loadExtension($this->extension);

        $expectedDefinitions = [
            Api\AutoResponseRuleController::class,
            Api\EmailActivityController::class,
            Api\EmailActivityEntityController::class,
            Api\EmailActivitySearchController::class,
            Api\EmailActivitySuggestionController::class,
            Api\EmailController::class,
            Api\EmailOriginController::class,
            Api\EmailTemplateController::class,
        ];

        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
