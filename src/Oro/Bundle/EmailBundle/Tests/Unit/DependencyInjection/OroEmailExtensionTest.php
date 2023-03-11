<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\EmailBundle\DependencyInjection\OroEmailExtension;
use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroEmailExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $extension = new OroEmailExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());

        self::assertSame([], $container->getParameter('oro_email.email_sync_exclusions'));
        self::assertSame([], $container->getParameter('oro_email.public_email_owners'));
        self::assertSame(4, $container->getParameter('oro_email.flash_notification.max_emails_display'));
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

        $extension = new OroEmailExtension();
        $extension->prepend($containerBuilder);
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
