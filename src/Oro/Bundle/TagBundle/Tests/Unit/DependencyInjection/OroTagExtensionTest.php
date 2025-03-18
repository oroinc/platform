<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TagBundle\DependencyInjection\OroTagExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroTagExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $extension = new OroTagExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
        self::assertSame(
            [
                [
                    'settings' => [
                        'resolved' => true,
                        'taxonomy_colors' => [
                            'value' => [
                                '#A57261',
                                '#CD7B6C',
                                '#A92F1F',
                                '#CD5642',
                                '#DE703F',
                                '#E09B45',
                                '#80C4A6',
                                '#368360',
                                '#96C27C',
                                '#CADEAE',
                                '#E3D47D',
                                '#D1C15C',
                                '#ACD5C4',
                                '#9EC8CC',
                                '#8EADC7',
                                '#5978A9',
                                '#AA9FC2',
                                '#C2C2C2',
                                '#CABDBF',
                                '#CCA6AC',
                                '#AF6C82',
                                '#895E95',
                                '#7D6D94'
                            ],
                            'scope' => 'app'
                        ]
                    ]
                ]
            ],
            $container->getExtensionConfig('oro_tag')
        );
    }
}
