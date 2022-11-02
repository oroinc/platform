<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\ApiDoc\Parser\MarkdownApiDocParser;
use Oro\Bundle\ApiBundle\ApiDoc\ResourceDocParserRegistry;
use Oro\Bundle\ApiBundle\DependencyInjection\Compiler\ResourceDocParserCompilerPass;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

class ResourceDocParserCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    private ResourceDocParserCompilerPass $compiler;

    private ContainerBuilder $container;

    private Definition $registry;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->compiler = new ResourceDocParserCompilerPass();

        $this->container->setDefinition(
            'oro_api.resource_doc_parser.template',
            new Definition(MarkdownApiDocParser::class, [[]])
        );

        $this->registry = $this->container->setDefinition(
            'oro_api.resource_doc_parser_registry',
            new Definition(ResourceDocParserRegistry::class, [[], null])
        );
    }

    public function testProcessWhenNoResourceDocParsers(): void
    {
        $config = ['api_doc_views' => []];
        $this->container->setParameter('oro_api.bundle_config', $config);

        $this->compiler->process($this->container);

        self::assertEquals([], $this->registry->getArgument('$parsers'));

        $serviceLocatorReference = $this->registry->getArgument('$container');
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $this->container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals([], $serviceLocatorDef->getArgument(0));
    }

    public function testProcessWithoutApiDocViews(): void
    {
        $config = ['api_doc_views' => []];
        $this->container->setParameter('oro_api.bundle_config', $config);

        $parser1 = $this->container->setDefinition('parser1', new Definition());
        $parser1->setShared(false);
        $parser1->addTag(
            'oro.api.resource_doc_parser',
            ['requestType' => 'rest', 'priority' => -20]
        );
        $parser1->addTag(
            'oro.api.resource_doc_parser',
            ['requestType' => 'rest&json_api', 'priority' => -10]
        );

        $this->compiler->process($this->container);

        self::assertEquals(
            [
                ['parser1', 'rest&json_api'],
                ['parser1', 'rest']
            ],
            $this->registry->getArgument('$parsers')
        );

        $serviceLocatorReference = $this->registry->getArgument('$container');
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $this->container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals(
            [
                'parser1' => new ServiceClosureArgument(new Reference('parser1'))
            ],
            $serviceLocatorDef->getArgument(0)
        );
    }

    public function testProcessWithApiDocViews(): void
    {
        $config = [
            'api_doc_views' => [
                'view1' => [
                    'request_type' => ['rest', 'json_api', 'another']
                ],
                'view2' => [
                    'request_type' => ['rest', 'json_api']
                ],
                'view3' => [
                    'request_type' => ['rest']
                ],
                'view4' => [
                    'request_type' => []
                ],
                'view5' => []
            ]
        ];
        $this->container->setParameter('oro_api.bundle_config', $config);

        $parser1 = $this->container->setDefinition('parser1', new Definition());
        $parser1->setShared(false);
        $parser1->addTag(
            'oro.api.resource_doc_parser',
            ['requestType' => 'rest', 'priority' => -20]
        );
        $parser1->addTag(
            'oro.api.resource_doc_parser',
            ['requestType' => 'rest&json_api', 'priority' => -10]
        );

        $this->compiler->process($this->container);

        self::assertEquals(
            [
                ['oro_api.resource_doc_parser.template.view1', 'rest&json_api&another'],
                ['parser1', 'rest&json_api'],
                ['parser1', 'rest']
            ],
            $this->registry->getArgument('$parsers')
        );

        $serviceLocatorReference = $this->registry->getArgument('$container');
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $this->container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals(
            [
                'parser1'                                    =>
                    new ServiceClosureArgument(new Reference('parser1')),
                'oro_api.resource_doc_parser.template.view1' =>
                    new ServiceClosureArgument(new Reference('oro_api.resource_doc_parser.template.view1'))
            ],
            $serviceLocatorDef->getArgument(0)
        );
    }

    public function testProcessWithNotNormalizedRequestTypeInApiDocView(): void
    {
        $config = [
            'api_doc_views' => [
                'view1' => [
                    'request_type' => ['another', 'rest']
                ],
                'view2' => [
                    'request_type' => ['json_api', 'rest']
                ]
            ]
        ];
        $this->container->setParameter('oro_api.bundle_config', $config);

        $parser1 = $this->container->setDefinition('parser1', new Definition());
        $parser1->setShared(false);
        $parser1->addTag(
            'oro.api.resource_doc_parser',
            ['requestType' => 'rest&json_api']
        );

        $this->compiler->process($this->container);

        self::assertEquals(
            [
                ['oro_api.resource_doc_parser.template.view1', 'another&rest'],
                ['parser1', 'rest&json_api']
            ],
            $this->registry->getArgument('$parsers')
        );

        $serviceLocatorReference = $this->registry->getArgument('$container');
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $this->container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals(
            [
                'parser1'                                    =>
                    new ServiceClosureArgument(new Reference('parser1')),
                'oro_api.resource_doc_parser.template.view1' =>
                    new ServiceClosureArgument(new Reference('oro_api.resource_doc_parser.template.view1'))
            ],
            $serviceLocatorDef->getArgument(0)
        );
    }

    public function testProcessWithNotNormalizedRequestType(): void
    {
        $config = [
            'api_doc_views' => [
                'view1' => [
                    'request_type' => ['rest', 'another']
                ],
                'view2' => [
                    'request_type' => ['rest', 'json_api']
                ]
            ]
        ];
        $this->container->setParameter('oro_api.bundle_config', $config);

        $parser1 = $this->container->setDefinition('parser1', new Definition());
        $parser1->setShared(false);
        $parser1->addTag(
            'oro.api.resource_doc_parser',
            ['requestType' => 'json_api&rest', 'priority' => -10]
        );

        $this->compiler->process($this->container);

        self::assertEquals(
            [
                ['oro_api.resource_doc_parser.template.view1', 'rest&another'],
                ['parser1', 'json_api&rest']
            ],
            $this->registry->getArgument('$parsers')
        );

        $serviceLocatorReference = $this->registry->getArgument('$container');
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $this->container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals(
            [
                'parser1'                                    =>
                    new ServiceClosureArgument(new Reference('parser1')),
                'oro_api.resource_doc_parser.template.view1' =>
                    new ServiceClosureArgument(new Reference('oro_api.resource_doc_parser.template.view1'))
            ],
            $serviceLocatorDef->getArgument(0)
        );
    }

    public function testProcessWithRequestTypeThatCannotBeNormalized(): void
    {
        $config = [
            'api_doc_views' => [
                'view1' => [
                    'request_type' => ['rest', 'another']
                ],
                'view2' => [
                    'request_type' => ['rest', 'json_api']
                ]
            ]
        ];
        $this->container->setParameter('oro_api.bundle_config', $config);

        $parser1 = $this->container->setDefinition('parser1', new Definition());
        $parser1->setShared(false);
        $parser1->addTag(
            'oro.api.resource_doc_parser',
            ['requestType' => 'rest|json_api', 'priority' => -10]
        );
        $parser1->addTag(
            'oro.api.resource_doc_parser',
            ['requestType' => 'rest&!json_api', 'priority' => -20]
        );

        $this->compiler->process($this->container);

        self::assertEquals(
            [
                ['oro_api.resource_doc_parser.template.view1', 'rest&another'],
                ['oro_api.resource_doc_parser.template.view2', 'rest&json_api'],
                ['parser1', 'rest|json_api'],
                ['parser1', 'rest&!json_api']
            ],
            $this->registry->getArgument('$parsers')
        );

        $serviceLocatorReference = $this->registry->getArgument('$container');
        self::assertInstanceOf(Reference::class, $serviceLocatorReference);
        $serviceLocatorDef = $this->container->getDefinition((string)$serviceLocatorReference);
        self::assertEquals(ServiceLocator::class, $serviceLocatorDef->getClass());
        self::assertEquals(
            [
                'parser1'                                    =>
                    new ServiceClosureArgument(new Reference('parser1')),
                'oro_api.resource_doc_parser.template.view1' =>
                    new ServiceClosureArgument(new Reference('oro_api.resource_doc_parser.template.view1')),
                'oro_api.resource_doc_parser.template.view2' =>
                    new ServiceClosureArgument(new Reference('oro_api.resource_doc_parser.template.view2'))
            ],
            $serviceLocatorDef->getArgument(0)
        );
    }
}
