<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config\Extension;

use Oro\Bundle\ApiBundle\Config\Extension\FeatureConfigurationExtension;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Provider\ResourceCheckerConfigProvider;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class FeatureConfigurationExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ResourceCheckerConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var FeatureConfigurationExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ResourceCheckerConfigProvider::class);

        $actionProcessorBag = $this->createMock(ActionProcessorBagInterface::class);
        $actionProcessorBag->expects(self::any())
            ->method('getActions')
            ->willReturn([
                'get',
                'create',
                'update',
                'delete',
                'delete_list',
                'update_relationship',
                'add_relationship',
                'delete_relationship'
            ]);

        $this->extension = new FeatureConfigurationExtension(
            $actionProcessorBag,
            $this->configProvider,
            'api_resources',
            'A list of entity FQCNs that are available as API resources.'
        );
    }

    private function processConfiguration(array $configs): array
    {
        $treeBuilder = new TreeBuilder('features');
        $this->extension->extendConfigurationTree(
            $treeBuilder->getRootNode()->useAttributeAsKey('name')->prototype('array')->children()
        );

        return (new Processor())->process($treeBuilder->buildTree(), $configs);
    }

    /**
     * @dataProvider invalidApiResourceDataProvider
     */
    public function testInvalidApiResourceType(mixed $apiResource, string $errorMessage): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(
            'Invalid configuration for path "features.feature1.api_resources.1": ' . $errorMessage
        );
        $this->processConfiguration(
            [['feature1' => ['api_resources' => ['resource1', $apiResource]]]]
        );
    }

    public function invalidApiResourceDataProvider(): array
    {
        return [
            [
                123,
                'The value must be a string or an array.'
            ],
            [
                ['entity' => 'resource2', 'actions' => ['create']],
                'The array value must contains 2 elements, an entity class and an array of API actions.'
            ],
            [
                ['resource2', ['create'], 'another'],
                'The array value must contains 2 elements, an entity class and an array of API actions.'
            ],
            [
                ['resource2'],
                'The array value must contains 2 elements, an entity class and an array of API actions.'
            ],
            [
                [123, ['create']],
                'The first element of the array must be a string that is an entity class.'
            ],
            [
                ['resource2', 123],
                'The second element of the array must not be an empty array.'
            ],
            [
                ['resource2', []],
                'The second element of the array must not be an empty array.'
            ],
            [
                ['resource2', ['create', 'another']],
                'The "another" is unknown API action. Known actions: '
                . '"get, create, update, delete, delete_list, '
                . 'update_relationship, add_relationship, delete_relationship".'
            ],
        ];
    }

    public function testExtendConfigurationTree(): void
    {
        $config = $this->processConfiguration(
            [
                ['feature1' => ['api_resources' => ['resource1', ['resource2', ['create', 'update']]]]],
                ['feature1' => ['api_resources' => ['resource3', ['resource2', ['delete']]]]],
            ]
        );
        self::assertEquals(
            [
                'feature1' => [
                    'api_resources' => [
                        'resource1',
                        ['resource2', ['create', 'update']],
                        'resource3',
                        ['resource2', ['delete']]
                    ]
                ]
            ],
            $config
        );
    }

    public function testProcessConfiguration(): void
    {
        $this->configProvider->expects(self::once())
            ->method('startBuild');
        $this->configProvider->expects(self::exactly(3))
            ->method('addApiResource')
            ->withConsecutive(
                [
                    'feature1',
                    'resource2',
                    ['create', 'update', 'update_relationship', 'add_relationship', 'delete_relationship']
                ],
                ['feature1', 'resource2', ['delete']],
                ['feature2', 'resource2', ['delete', 'delete_list']],
            );
        $this->configProvider->expects(self::once())
            ->method('flush');

        $config = $this->processConfiguration(
            [
                ['feature1' => ['api_resources' => ['resource1', ['resource2', ['create', 'update']]]]],
                ['feature2' => ['api_resources' => [['resource2', ['delete', 'delete_list']]]]],
                ['feature1' => ['api_resources' => ['resource3', ['resource2', ['delete']]]]],
                ['feature3' => ['api_resources' => ['resource1']]],
                ['feature4' => ['api_resources' => []]],
            ]
        );
        $config = $this->extension->processConfiguration($config);
        self::assertEquals(
            [
                'feature1' => [
                    'api_resources' => ['resource1', 'resource3']
                ],
                'feature2' => [
                    'api_resources' => []
                ],
                'feature3' => [
                    'api_resources' => ['resource1']
                ],
                'feature4' => [
                    'api_resources' => []
                ]
            ],
            $config
        );
    }

    public function testCompleteConfiguration(): void
    {
        $this->configProvider->expects(self::exactly(4))
            ->method('getApiResources')
            ->willReturnMap([
                ['feature1', [['resource1', ['create']], ['resource2', ['create', 'update']]]],
                ['feature2', [['resource2', ['delete']]]],
                ['feature3', []],
                ['feature4', []],
            ]);

        $config = [
            'feature1' => ['api_resources' => ['resource3']],
            'feature2' => [],
            'feature3' => ['api_resources' => ['resource1']],
            'feature4' => [],
        ];
        self::assertEquals(
            [
                'feature1' => [
                    'api_resources' => [
                        'resource3',
                        ['resource1', ['create']],
                        ['resource2', ['create', 'update']]
                    ]
                ],
                'feature2' => [
                    'api_resources' => [
                        ['resource2', ['delete']]
                    ]
                ],
                'feature3' => [
                    'api_resources' => ['resource1']
                ],
                'feature4' => []
            ],
            $this->extension->completeConfiguration($config)
        );
    }

    public function testClearConfigurationCache(): void
    {
        $this->configProvider->expects(self::once())
            ->method('clear');

        $this->extension->clearConfigurationCache();
    }
}
