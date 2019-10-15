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
                        'html_allowed_elements' => [],
                        'extends' => null,
                        'html_purifier_iframe_domains' => [],
                        'html_purifier_uri_schemes' => []
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
                        'html_allowed_elements' => []
                    ]
                ]
            ]
        ];

        $extension->load($config, $container);
    }
}
