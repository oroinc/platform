<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\FormBundle\DependencyInjection\OroFormExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class OroFormExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad()
    {
        $definition = $this->createMock(Definition::class);
        $definition->expects($this->once())
            ->method('replaceArgument')
            ->with(
                0,
                [
                    'default' => [
                        'allowed_html_elements' => [],
                        'extends' => null,
                        'allowed_iframe_domains' => [],
                        'allowed_uri_schemes' => [],
                        'allowed_rel' => []
                    ]
                ]
            );

        /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject $container */
        $container = $this->createMock(ContainerBuilder::class);
        $container->expects($this->once())
            ->method('getDefinition')
            ->with('oro_form.provider.html_tag_provider')
            ->willReturn($definition);

        $extension = new OroFormExtension();

        $config = [
            'oro_form' => [
                'html_purifier_modes' => [
                    'default' => [
                        'allowed_html_elements' => []
                    ]
                ]
            ]
        ];

        $extension->load($config, $container);
    }
}
