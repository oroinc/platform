<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TagBundle\DependencyInjection\OroTagExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroTagExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

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
                                '#AC725E',
                                '#D06B64',
                                '#F83A22',
                                '#FA573C',
                                '#FF7537',
                                '#FFAD46',
                                '#42D692',
                                '#16A765',
                                '#7BD148',
                                '#B3DC6C',
                                '#FBE983',
                                '#FAD165',
                                '#92E1C0',
                                '#9FE1E7',
                                '#9FC6E7',
                                '#4986E7',
                                '#9A9CFF',
                                '#B99AFF',
                                '#C2C2C2',
                                '#CABDBF',
                                '#CCA6AC',
                                '#F691B2',
                                '#CD74E6',
                                '#A47AE2'
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
