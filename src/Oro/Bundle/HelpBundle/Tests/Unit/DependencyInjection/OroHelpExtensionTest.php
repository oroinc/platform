<?php

namespace Oro\Bundle\HelpBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\HelpBundle\DependencyInjection\OroHelpExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroHelpExtensionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var OroHelpExtension
     */
    protected $extension;

    protected function setUp(): void
    {
        $this->extension = new OroHelpExtension();
    }

    public function testLoad()
    {
        $container = new ContainerBuilder();

        $this->extension->load(
            [
                'oro_help' => [
                    'defaults' => [
                        'server' => 'http://server.com'
                    ]
                ]
            ],
            $container
        );

        $this->assertEquals(
            [
                'server' => 'http://server.com'
            ],
            $container->getDefinition('oro_help.help_link_provider')->getArgument(0)
        );
    }
}
