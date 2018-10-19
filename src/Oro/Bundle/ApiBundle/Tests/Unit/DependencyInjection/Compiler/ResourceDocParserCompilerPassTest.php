<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\ApiDoc\Parser\MarkdownApiDocParser;
use Oro\Bundle\ApiBundle\ApiDoc\ResourceDocParserRegistry;
use Oro\Bundle\ApiBundle\DependencyInjection\Compiler\ResourceDocParserCompilerPass;
use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class ResourceDocParserCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var ResourceDocParserCompilerPass */
    private $compiler;

    /** @var ContainerBuilder */
    private $container;

    /** @var Definition */
    private $registry;

    protected function setUp()
    {
        $this->container = new ContainerBuilder();
        $this->compiler = new ResourceDocParserCompilerPass();

        $this->container->setDefinition(
            'oro_api.resource_doc_parser.template',
            new Definition(MarkdownApiDocParser::class, [[]])
        );

        $this->registry = $this->container->setDefinition(
            'oro_api.resource_doc_parser_registry',
            new Definition(ResourceDocParserRegistry::class, [[]])
        );
    }

    public function testProcessWhenNoResourceDocParsers()
    {
        $config = ['api_doc_views' => []];
        $this->container->setParameter(DependencyInjectionUtil::API_BUNDLE_CONFIG_PARAMETER_NAME, $config);

        $this->compiler->process($this->container);

        self::assertEquals([], $this->registry->getArgument(0));
    }

    public function testProcessWithoutApiDocViews()
    {
        $config = ['api_doc_views' => []];
        $this->container->setParameter(DependencyInjectionUtil::API_BUNDLE_CONFIG_PARAMETER_NAME, $config);

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
            $this->registry->getArgument(0)
        );
    }

    public function testProcessWithApiDocViews()
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
        $this->container->setParameter(DependencyInjectionUtil::API_BUNDLE_CONFIG_PARAMETER_NAME, $config);

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
            $this->registry->getArgument(0)
        );
    }

    public function testProcessWithNotNormalizedRequestTypeInApiDocView()
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
        $this->container->setParameter(DependencyInjectionUtil::API_BUNDLE_CONFIG_PARAMETER_NAME, $config);

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
            $this->registry->getArgument(0)
        );
    }

    public function testProcessWithNotNormalizedRequestType()
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
        $this->container->setParameter(DependencyInjectionUtil::API_BUNDLE_CONFIG_PARAMETER_NAME, $config);

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
            $this->registry->getArgument(0)
        );
    }

    public function testProcessWithRequestTypeThatCannotBeNormalized()
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
        $this->container->setParameter(DependencyInjectionUtil::API_BUNDLE_CONFIG_PARAMETER_NAME, $config);

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
            $this->registry->getArgument(0)
        );
    }

    public function testProcessWhenResourceDocParserIsNotPublic()
    {
        $config = ['api_doc_views' => []];
        $this->container->setParameter(DependencyInjectionUtil::API_BUNDLE_CONFIG_PARAMETER_NAME, $config);

        $parser1 = $this->container->setDefinition('parser1', new Definition());
        $parser1->setPublic(false);
        $parser1->addTag(
            'oro.api.resource_doc_parser',
            ['requestType' => 'rest']
        );

        $this->compiler->process($this->container);

        self::assertEquals(
            [
                ['parser1', 'rest']
            ],
            $this->registry->getArgument(0)
        );
        self::assertTrue($parser1->isPublic());
    }
}
