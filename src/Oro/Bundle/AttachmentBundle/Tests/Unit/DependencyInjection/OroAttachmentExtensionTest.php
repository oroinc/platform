<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\AttachmentBundle\DependencyInjection\OroAttachmentExtension;
use Oro\Bundle\AttachmentBundle\Tools\WebpConfiguration;
use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroAttachmentExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoadParameters(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $extension = new OroAttachmentExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
        self::assertSame(
            [
                'kernel.environment' => 'prod',
                'oro_attachment.liip_imagine.unsupported_mime_types' => ['image/svg+xml', 'image/svg'],
                'liip_imagine.controller.filter_action' => 'oro_attachment.controller.imagine::filterAction',
                'oro_attachment.debug_images' => true,
                'oro_attachment.upload_file_mime_types' => [],
                'oro_attachment.upload_image_mime_types' => [],
                'oro_attachment.processors_allowed' => true,
                'oro_attachment.png_quality' => 100,
                'oro_attachment.jpeg_quality' => 85,
                'oro_attachment.webp_strategy' => WebpConfiguration::ENABLED_IF_SUPPORTED,
                'oro_attachment.collect_attachment_files_batch_size' => 20000,
                'oro_attachment.load_existing_attachments_batch_size' => 500,
                'oro_attachment.load_attachments_batch_size' => 10000,
                'oro_attachment.files' => [
                    'default' => 'fa-file-o',
                    'doc' => 'fa-file-text-o',
                    'docx' => 'fa-file-text-o',
                    'xls' => 'fa-file-excel-o',
                    'xlsx' => 'fa-file-excel-o',
                    'pdf' => 'fa-file-pdf-o',
                    'png' => 'fa-file-image-o',
                    'jpg' => 'fa-file-image-o',
                    'jpeg' => 'fa-file-image-o',
                    'gif' => 'fa-file-image-o',
                    'mp4' => 'fa-file-movie-o',
                ],
            ],
            $container->getParameterBag()->all()
        );
    }

    public function testPrependWithoutImagineConfigs(): void
    {
        $container = new ExtendedContainerBuilder();
        $container->setExtensionConfig('liip_imagine', [
            ['filter_sets' => []],
            []
        ]);

        $extension = new OroAttachmentExtension();
        $extension->prepend($container);

        self::assertSame(
            [
                [
                    'filter_sets' => [],
                    'loaders'     => ['default' => []],
                    'resolvers'   => ['default' => []]
                ],
                [
                    'loaders'   => ['default' => []],
                    'resolvers' => ['default' => []]
                ],
                [
                    'loaders'   => [
                        'default' => [
                            'filesystem' => [
                                'data_root'        => '%kernel.project_dir%/public',
                                'bundle_resources' => ['enabled' => true]
                            ]
                        ]
                    ],
                    'resolvers' => [
                        'default' => [
                            'oro_gaufrette' => [
                                'file_manager_service' => 'oro_attachment.manager.public_mediacache'
                            ]
                        ]
                    ]
                ]
            ],
            $container->getExtensionConfig('liip_imagine')
        );
    }

    public function testPrependWithCustomDataRootForDefaultLoader(): void
    {
        $container = new ExtendedContainerBuilder();
        $container->setExtensionConfig('liip_imagine', [
            ['filter_sets' => []],
            ['loaders' => ['default' => ['filesystem' => ['data_root' => '%kernel.project_dir%/another']]]]
        ]);

        $extension = new OroAttachmentExtension();
        $extension->prepend($container);

        self::assertSame(
            [
                [
                    'filter_sets' => [],
                    'loaders'     => ['default' => []],
                    'resolvers'   => ['default' => []]
                ],
                [
                    'loaders'   => [
                        'default' => [
                            'filesystem' => [
                                'data_root'        => '%kernel.project_dir%/another',
                                'bundle_resources' => ['enabled' => true]
                            ]
                        ]
                    ],
                    'resolvers' => [
                        'default' => [
                            'oro_gaufrette' => [
                                'file_manager_service' => 'oro_attachment.manager.public_mediacache'
                            ]
                        ]
                    ]
                ]
            ],
            $container->getExtensionConfig('liip_imagine')
        );
    }

    public function testPrependWithDisabledBundleResourcesForDefaultLoader(): void
    {
        $container = new ExtendedContainerBuilder();
        $container->setExtensionConfig('liip_imagine', [
            ['filter_sets' => []],
            ['loaders' => ['default' => ['filesystem' => ['bundle_resources' => ['enabled' => false]]]]]
        ]);

        $extension = new OroAttachmentExtension();
        $extension->prepend($container);

        self::assertSame(
            [
                [
                    'filter_sets' => [],
                    'loaders'     => ['default' => []],
                    'resolvers'   => ['default' => []]
                ],
                [
                    'loaders'   => [
                        'default' => [
                            'filesystem' => [
                                'bundle_resources' => ['enabled' => false],
                                'data_root'        => '%kernel.project_dir%/public'
                            ]
                        ]
                    ],
                    'resolvers' => [
                        'default' => [
                            'oro_gaufrette' => [
                                'file_manager_service' => 'oro_attachment.manager.public_mediacache'
                            ]
                        ]
                    ]
                ]
            ],
            $container->getExtensionConfig('liip_imagine')
        );
    }

    public function testPrependWithCustomFileManagerServiceForDefaultResolver(): void
    {
        $container = new ExtendedContainerBuilder();
        $container->setExtensionConfig('liip_imagine', [
            ['filter_sets' => []],
            ['resolvers' => ['default' => ['oro_gaufrette' => ['file_manager_service' => 'another_service']]]]
        ]);

        $extension = new OroAttachmentExtension();
        $extension->prepend($container);

        self::assertSame(
            [
                [
                    'filter_sets' => [],
                    'loaders'     => ['default' => []],
                    'resolvers'   => ['default' => []]
                ],
                [
                    'resolvers' => [
                        'default' => [
                            'oro_gaufrette' => [
                                'file_manager_service' => 'another_service'
                            ]
                        ]
                    ],
                    'loaders'   => [
                        'default' => [
                            'filesystem' => [
                                'data_root'        => '%kernel.project_dir%/public',
                                'bundle_resources' => ['enabled' => true]
                            ]
                        ]
                    ]
                ]
            ],
            $container->getExtensionConfig('liip_imagine')
        );
    }

    public function testPrependWithCustomUrlPrefixForDefaultResolver(): void
    {
        $container = new ExtendedContainerBuilder();
        $container->setExtensionConfig('liip_imagine', [
            ['filter_sets' => []],
            ['resolvers' => ['default' => ['oro_gaufrette' => ['url_prefix' => 'another_prefix']]]]
        ]);

        $extension = new OroAttachmentExtension();
        $extension->prepend($container);

        self::assertSame(
            [
                [
                    'filter_sets' => [],
                    'loaders'     => ['default' => []],
                    'resolvers'   => ['default' => []]
                ],
                [
                    'resolvers' => [
                        'default' => [
                            'oro_gaufrette' => [
                                'url_prefix'           => 'another_prefix',
                                'file_manager_service' => 'oro_attachment.manager.public_mediacache'
                            ]
                        ]
                    ],
                    'loaders'   => [
                        'default' => [
                            'filesystem' => [
                                'data_root'        => '%kernel.project_dir%/public',
                                'bundle_resources' => ['enabled' => true]
                            ]
                        ]
                    ]
                ]
            ],
            $container->getExtensionConfig('liip_imagine')
        );
    }

    public function testPrependWithNotOroGaufretteDefaultResolver(): void
    {
        $container = new ExtendedContainerBuilder();
        $container->setExtensionConfig('liip_imagine', [
            ['filter_sets' => []],
            ['resolvers' => ['default' => ['another' => []]]]
        ]);

        $extension = new OroAttachmentExtension();
        $extension->prepend($container);

        self::assertSame(
            [
                [
                    'filter_sets' => [],
                    'loaders'     => ['default' => []],
                    'resolvers'   => ['default' => []]
                ],
                [
                    'resolvers' => [
                        'default' => [
                            'another' => []
                        ]
                    ],
                    'loaders'   => [
                        'default' => [
                            'filesystem' => [
                                'data_root'        => '%kernel.project_dir%/public',
                                'bundle_resources' => ['enabled' => true]
                            ]
                        ]
                    ]
                ]
            ],
            $container->getExtensionConfig('liip_imagine')
        );
    }

    public function testPrependWithNotFilesystemDefaultLoader(): void
    {
        $container = new ExtendedContainerBuilder();
        $container->setExtensionConfig('liip_imagine', [
            ['filter_sets' => []],
            ['loaders' => ['default' => ['another' => []]]]
        ]);

        $extension = new OroAttachmentExtension();
        $extension->prepend($container);

        self::assertSame(
            [
                [
                    'filter_sets' => [],
                    'loaders'     => ['default' => []],
                    'resolvers'   => ['default' => []]
                ],
                [
                    'loaders'   => [
                        'default' => [
                            'another' => []
                        ]
                    ],
                    'resolvers' => [
                        'default' => [
                            'oro_gaufrette' => [
                                'file_manager_service' => 'oro_attachment.manager.public_mediacache'
                            ]
                        ]
                    ]
                ]
            ],
            $container->getExtensionConfig('liip_imagine')
        );
    }
}
