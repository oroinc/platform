<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\EmailBundle\DependencyInjection\OroEmailExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use Oro\Component\DependencyInjection\ExtendedContainerBuilder;

class OroEmailExtensionTest extends ExtensionTestCase
{
    /** @var OroEmailExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->extension = new OroEmailExtension();
    }

    public function testGetAlias(): void
    {
        $this->assertEquals('oro_email', $this->extension->getAlias());
    }

    /**
     * @dataProvider prependSecurityConfigDataProvider
     */
    public function testPrepend(array $securityConfig, array $expectedSecurityConfig): void
    {
        $containerBuilder = $this->createMock(ExtendedContainerBuilder::class);

        $containerBuilder
            ->expects($this->once())
            ->method('getExtensionConfig')
            ->with('nelmio_security')
            ->willReturn($securityConfig);

        $containerBuilder
            ->expects($this->once())
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
}
