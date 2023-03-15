<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\FormBundle\DependencyInjection\OroFormExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroFormExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $extension = new OroFormExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
        self::assertSame(
            [
                [
                    'settings' => [
                        'resolved' => true,
                        'wysiwyg_enabled' => ['value' => true, 'scope' => 'app']
                    ]
                ]
            ],
            $container->getExtensionConfig('oro_form')
        );

        self::assertSame(
            [],
            $container->getDefinition('oro_form.provider.html_tag_provider')->getArgument(0)
        );
    }

    public function testLoadWithHtmlPurifierModes(): void
    {
        $container = new ContainerBuilder();

        $configs = [
            ['html_purifier_modes' => ['lax' => ['extends' => 'default']]]
        ];

        $extension = new OroFormExtension();
        $extension->load($configs, $container);

        self::assertSame(
            [
                'lax' => [
                    'extends' => 'default',
                    'allowed_rel' => [],
                    'allowed_iframe_domains' => [],
                    'allowed_uri_schemes' => [],
                    'allowed_html_elements' => [],
                ]
            ],
            $container->getDefinition('oro_form.provider.html_tag_provider')->getArgument(0)
        );
    }
}
