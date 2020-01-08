<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\EmailBundle\DependencyInjection\OroEmailExtension;
use Oro\Component\DependencyInjection\ExtendedContainerBuilder;

class OroEmailExtensionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider prependSecurityConfigDataProvider
     *
     * @param array $securityConfig
     * @param array $expectedSecurityConfig
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

        (new OroEmailExtension())->prepend($containerBuilder);
    }

    /**
     * @return array
     */
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
