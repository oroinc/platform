<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Oro\Bundle\EmailBundle\Provider\EntityRouteVariablesProvider;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager as EntityConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Symfony\Component\Translation\Translator;

class EntityRouteVariablesProviderTest extends \PHPUnit\Framework\TestCase
{
    const TEST_ENTITY_NAME = 'Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestEntityForVariableProvider';
    const TEST_EXTEND_ENTITY_NAME = ExtendHelper::ENTITY_NAMESPACE . 'TestEntity';

    /** @var EntityRouteVariablesProvider */
    protected $provider;

    /** @var  Translator|\PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /** @var  EntityConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $entityConfigManager;

    /** @var  ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $extendConfigProvider;

    /** @var  ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $entityConfigProvider;

    protected function setUp()
    {
        $this->translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator->expects($this->any())
            ->method('trans')
            ->will($this->returnArgument(0));

        $this->extendConfigProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityConfigProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityConfigManager = $this->getMockBuilder(EntityConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityConfigManager->expects($this->any())->method('getProvider')->willReturnMap([
            ['extend', $this->extendConfigProvider],
            ['entity', $this->entityConfigProvider],
        ]);

        $this->provider = new EntityRouteVariablesProvider(
            $this->translator,
            $this->entityConfigManager
        );
    }

    protected function tearDown()
    {
        unset($this->translator, $this->entityConfigManager, $this->provider);
    }

    /**
     * @param string|null $entityClass
     *
     * @dataProvider variableGettersDataProvider
     */
    public function testGetVariableGetters($entityClass)
    {
        $this->assertEquals([], $this->provider->getVariableGetters($entityClass));
    }

    /**
     * @return \Generator
     */
    public function variableGettersDataProvider()
    {
        yield ['entityClass' => null];
        yield ['entityClass' => self::TEST_ENTITY_NAME];
        yield ['entityClass' => self::TEST_EXTEND_ENTITY_NAME];
    }

    /**
     * @param $entityClass
     * @param array $expected
     *
     * @dataProvider variableDefinitionsDataProvider
     */
    public function testGetVariableDefinitions($entityClass, array $expected)
    {
        $entityMetadata = null;
        $config = $this->createMock(ConfigInterface::class);
        $config->expects($this->atLeastOnce())->method('is')->with('is_extend')->willReturn(false);
        $this->extendConfigProvider->expects($this->atLeastOnce())->method('hasConfig')->willReturn(true);
        $this->extendConfigProvider->expects($this->atLeastOnce())->method('getConfig')->willReturn($config);

        $this->entityConfigManager->expects($this->atLeastOnce())
            ->method('getEntityMetadata')
            ->willReturnCallback(function ($class) {
                if (!ExtendHelper::isCustomEntity($class)) {
                    $metadata = new EntityMetadata(self::TEST_ENTITY_NAME);
                    $metadata->routeName = 'oro_test_index';
                    $metadata->routeView = 'oro_test_view';

                    return $metadata;
                }

                return null;
            });

        if (is_null($entityClass)) {
            $this->entityConfigProvider->expects($this->once())->method('getIds')->willReturn([
                new EntityConfigId(null, self::TEST_ENTITY_NAME),
                new EntityConfigId(null, self::TEST_EXTEND_ENTITY_NAME),
            ]);
        }

        $this->assertEquals($expected, $this->provider->getVariableDefinitions($entityClass));
    }

    /**
     * @return \Generator
     */
    public function variableDefinitionsDataProvider()
    {
        $entityData = [
            'url.index' => [
                'type' => 'string',
                'label' => 'oro.email.emailtemplate.variables.url.index.label',
                'processor' => 'entity_routes',
                'route' => 'oro_test_index',
            ],
            'url.view' => [
                'type' => 'string',
                'label' => 'oro.email.emailtemplate.variables.url.view.label',
                'processor' => 'entity_routes',
                'route' => 'oro_test_view',
            ],
        ];
        $extendEntityData = $entityData;
        $extendEntityData['url.index']['route'] = 'oro_entity_index';
        $extendEntityData['url.view']['route'] = 'oro_entity_view';

        yield 'empty entity class' => ['entityClass' => null, 'expected' => [
            self::TEST_ENTITY_NAME => $entityData,
            self::TEST_EXTEND_ENTITY_NAME => $extendEntityData,
        ]];
        yield 'general entity class' => ['entityClass' => self::TEST_ENTITY_NAME, $entityData];
        yield 'custom entity class' => [
            'entityClass' => ExtendHelper::ENTITY_NAMESPACE . 'TestEntity', $extendEntityData,
        ];
    }
}
