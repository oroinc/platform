<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\ConfigExtraSectionInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\FiltersConfig;
use Oro\Bundle\ApiBundle\Config\FiltersConfigExtra;
use Oro\Bundle\ApiBundle\Config\SortersConfig;
use Oro\Bundle\ApiBundle\Config\SortersConfigExtra;
use Oro\Bundle\ApiBundle\Filter\FilterInterface;
use Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface;
use Oro\Bundle\ApiBundle\Metadata\ActionMetadataExtra;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\HateoasMetadataExtra;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\DocumentBuilderInterface;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ContextTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigProvider */
    private $configProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|MetadataProvider */
    private $metadataProvider;

    /** @var Context */
    private $context;

    protected function setUp()
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->metadataProvider = $this->createMock(MetadataProvider::class);

        $this->context = new Context($this->configProvider, $this->metadataProvider);
    }

    /**
     * keys of request headers should be are case insensitive
     */
    public function testRequestHeaders()
    {
        $headers = $this->context->getRequestHeaders();

        $key1   = 'test1';
        $key2   = 'test2';
        $value1 = 'value1';
        $value2 = 'value2';

        self::assertFalse($headers->has($key1));
        self::assertFalse(isset($headers[$key1]));
        self::assertNull($headers->get($key1));
        self::assertNull($headers[$key1]);

        $headers->set($key1, $value1);
        self::assertTrue($headers->has($key1));
        self::assertTrue(isset($headers[$key1]));
        self::assertEquals($value1, $headers->get($key1));
        self::assertEquals($value1, $headers[$key1]);

        self::assertTrue($headers->has(strtoupper($key1)));
        self::assertTrue(isset($headers[strtoupper($key1)]));
        self::assertEquals($value1, $headers->get(strtoupper($key1)));
        self::assertEquals($value1, $headers[strtoupper($key1)]);

        $headers->remove(strtoupper($key1));
        self::assertFalse($headers->has($key1));
        self::assertFalse(isset($headers[$key1]));
        self::assertNull($headers->get($key1));
        self::assertNull($headers[$key1]);

        $headers[strtoupper($key2)] = $value2;
        self::assertTrue($headers->has($key2));
        self::assertTrue(isset($headers[$key2]));
        self::assertEquals($value2, $headers->get($key2));
        self::assertEquals($value2, $headers[$key2]);

        unset($headers[$key2]);
        self::assertFalse($headers->has(strtoupper($key2)));
        self::assertFalse(isset($headers[strtoupper($key2)]));
        self::assertNull($headers->get(strtoupper($key2)));
        self::assertNull($headers[strtoupper($key2)]);

        $headers->set(strtoupper($key1), null);
        self::assertTrue($headers->has($key1));
        self::assertTrue(isset($headers[$key1]));
        self::assertNull($headers->get($key1));
        self::assertNull($headers[$key1]);

        self::assertCount(1, $headers);
        self::assertEquals([$key1 => null], $headers->toArray());

        $headers->clear();
        self::assertCount(0, $headers);
    }

    /**
     * keys of response headers should be are case sensitive
     */
    public function testResponseHeaders()
    {
        $headers = $this->context->getResponseHeaders();

        $key1   = 'test1';
        $key2   = 'test2';
        $value1 = 'value1';
        $value2 = 'value2';

        self::assertFalse($headers->has($key1));
        self::assertFalse(isset($headers[$key1]));
        self::assertNull($headers->get($key1));
        self::assertNull($headers[$key1]);

        $headers->set($key1, $value1);
        self::assertTrue($headers->has($key1));
        self::assertTrue(isset($headers[$key1]));
        self::assertEquals($value1, $headers->get($key1));
        self::assertEquals($value1, $headers[$key1]);

        self::assertFalse($headers->has(strtoupper($key1)));
        self::assertFalse(isset($headers[strtoupper($key1)]));
        self::assertNull($headers->get(strtoupper($key1)));
        self::assertNull($headers[strtoupper($key1)]);
        $headers->remove(strtoupper($key1));
        self::assertTrue($headers->has($key1));
        unset($headers[strtoupper($key1)]);
        self::assertTrue($headers->has($key1));

        $headers->remove($key1);
        self::assertFalse($headers->has($key1));
        self::assertFalse(isset($headers[$key1]));
        self::assertNull($headers->get($key1));
        self::assertNull($headers[$key1]);

        $headers[$key2] = $value2;
        self::assertTrue($headers->has($key2));
        self::assertTrue(isset($headers[$key2]));
        self::assertEquals($value2, $headers->get($key2));
        self::assertEquals($value2, $headers[$key2]);

        unset($headers[$key2]);
        self::assertFalse($headers->has($key2));
        self::assertFalse(isset($headers[$key2]));
        self::assertNull($headers->get($key2));
        self::assertNull($headers[$key2]);

        $headers->set($key1, null);
        self::assertTrue($headers->has($key1));
        self::assertTrue(isset($headers[$key1]));
        self::assertNull($headers->get($key1));
        self::assertNull($headers[$key1]);

        self::assertCount(1, $headers);
        self::assertEquals([$key1 => null], $headers->toArray());

        $headers->clear();
        self::assertCount(0, $headers);
    }

    public function testResponseStatusCode()
    {
        self::assertNull($this->context->getResponseStatusCode());

        $this->context->setResponseStatusCode(500);
        self::assertEquals(500, $this->context->getResponseStatusCode());
        self::assertEquals(500, $this->context->get(Context::RESPONSE_STATUS_CODE));
    }

    public function testIsSuccessResponse()
    {
        self::assertFalse($this->context->isSuccessResponse());

        $this->context->setResponseStatusCode(200);
        self::assertTrue($this->context->isSuccessResponse());
        $this->context->setResponseStatusCode(299);
        self::assertTrue($this->context->isSuccessResponse());

        $this->context->setResponseStatusCode(199);
        self::assertFalse($this->context->isSuccessResponse());
        $this->context->setResponseStatusCode(300);
        self::assertFalse($this->context->isSuccessResponse());
    }

    public function testResponseDocumentBuilder()
    {
        self::assertNull($this->context->getResponseDocumentBuilder());

        $documentBuilder = $this->createMock(DocumentBuilderInterface::class);
        $this->context->setResponseDocumentBuilder($documentBuilder);
        self::assertSame($documentBuilder, $this->context->getResponseDocumentBuilder());

        $this->context->setResponseDocumentBuilder(null);
        self::assertNull($this->context->getResponseDocumentBuilder());
    }

    public function testClassName()
    {
        self::assertNull($this->context->getClassName());

        $this->context->setClassName('test');
        self::assertEquals('test', $this->context->getClassName());
        self::assertEquals('test', $this->context->get(Context::CLASS_NAME));
    }

    public function testGetConfigSections()
    {
        $configExtras = [
            new TestConfigSection('section1'),
            new TestConfigSection('section2'),
            new TestConfigExtra('extra1')
        ];

        $this->context->setConfigExtras($configExtras);

        self::assertEquals(
            ['section1', 'section2'],
            $this->context->getConfigSections()
        );
    }

    public function testLoadConfigByGetConfig()
    {
        $version      = '1.1';
        $requestType  = 'rest';
        $entityClass  = 'Test\Class';
        $configExtras = [
            new TestConfigSection('section1'),
            new TestConfigSection('section2'),
            new TestConfigExtra('extra1')
        ];

        $config = new EntityDefinitionConfig();
        $config->setExcludeAll();
        $section1Config = ['test'];

        $this->context->setVersion($version);
        $this->context->getRequestType()->add($requestType);
        $this->context->setConfigExtras($configExtras);
        $this->context->setClassName($entityClass);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $entityClass,
                $version,
                new RequestType([$requestType]),
                $configExtras
            )
            ->willReturn(
                $this->getConfig(
                    [
                        ConfigUtil::DEFINITION => $config,
                        'section1'             => $section1Config
                    ]
                )
            );

        // test that a config is not loaded yet
        self::assertFalse($this->context->hasConfig());
        self::assertFalse($this->context->hasConfigOf('section1'));
        self::assertFalse($this->context->hasConfigOf('section2'));

        self::assertEquals($config, $this->context->getConfig()); // load config
        self::assertTrue($this->context->hasConfig());
        self::assertTrue($this->context->has(Context::CONFIG_PREFIX . ConfigUtil::DEFINITION));
        self::assertEquals($config, $this->context->get(Context::CONFIG_PREFIX . ConfigUtil::DEFINITION));

        self::assertTrue($this->context->hasConfigOf('section1'));
        self::assertEquals($section1Config, $this->context->getConfigOf('section1'));
        self::assertTrue($this->context->has(Context::CONFIG_PREFIX . 'section1'));
        self::assertEquals($section1Config, $this->context->get(Context::CONFIG_PREFIX . 'section1'));

        self::assertTrue($this->context->hasConfigOf('section2'));
        self::assertNull($this->context->getConfigOf('section2'));
        self::assertTrue($this->context->has(Context::CONFIG_PREFIX . 'section2'));
        self::assertNull($this->context->get(Context::CONFIG_PREFIX . 'section2'));

        // test that a config is loaded only once
        self::assertEquals($config, $this->context->getConfig());
    }

    public function testLoadConfigByGetConfigWhenExceptionOccurs()
    {
        $version      = '1.1';
        $requestType  = 'rest';
        $entityClass  = 'Test\Class';
        $configExtras = [
            new TestConfigSection('section1'),
            new TestConfigSection('section2'),
            new TestConfigExtra('extra1')
        ];
        $exception = new \RuntimeException('some error');

        $this->context->setVersion($version);
        $this->context->getRequestType()->add($requestType);
        $this->context->setConfigExtras($configExtras);
        $this->context->setClassName($entityClass);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $entityClass,
                $version,
                new RequestType([$requestType]),
                $configExtras
            )
            ->willThrowException($exception);

        // test that a config is not loaded yet
        self::assertFalse($this->context->hasConfig());
        self::assertFalse($this->context->hasConfigOf('section1'));
        self::assertFalse($this->context->hasConfigOf('section2'));

        try {
            $this->context->getConfig(); // load config
        } catch (\RuntimeException $e) {
            self::assertSame($exception, $e);
        }
        self::assertTrue($this->context->hasConfig());
        self::assertTrue($this->context->has(Context::CONFIG_PREFIX . ConfigUtil::DEFINITION));
        self::assertNull($this->context->get(Context::CONFIG_PREFIX . ConfigUtil::DEFINITION));

        self::assertTrue($this->context->hasConfigOf('section1'));
        self::assertNull($this->context->getConfigOf('section1'));
        self::assertTrue($this->context->has(Context::CONFIG_PREFIX . 'section1'));
        self::assertNull($this->context->get(Context::CONFIG_PREFIX . 'section1'));

        self::assertTrue($this->context->hasConfigOf('section2'));
        self::assertNull($this->context->getConfigOf('section2'));
        self::assertTrue($this->context->has(Context::CONFIG_PREFIX . 'section2'));
        self::assertNull($this->context->get(Context::CONFIG_PREFIX . 'section2'));

        // test that a config is loaded only once
        self::assertNull($this->context->getConfig());
    }

    public function testLoadConfigByGetConfigOf()
    {
        $version      = '1.1';
        $requestType  = 'rest';
        $entityClass  = 'Test\Class';
        $configExtras = [
            new TestConfigSection('section1'),
            new TestConfigSection('section2')
        ];

        $config = new EntityDefinitionConfig();
        $config->setExcludeAll();
        $section1Config = ['test'];

        $this->context->setVersion($version);
        $this->context->getRequestType()->add($requestType);
        $this->context->setConfigExtras($configExtras);
        $this->context->setClassName($entityClass);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $entityClass,
                $version,
                new RequestType([$requestType]),
                $configExtras
            )
            ->willReturn(
                $this->getConfig(
                    [
                        ConfigUtil::DEFINITION => $config,
                        'section1'             => $section1Config
                    ]
                )
            );

        // test that a config is not loaded yet
        self::assertFalse($this->context->hasConfig());
        self::assertFalse($this->context->hasConfigOf('section1'));
        self::assertFalse($this->context->hasConfigOf('section2'));

        self::assertEquals($section1Config, $this->context->getConfigOf('section1')); // load config
        self::assertTrue($this->context->hasConfigOf('section1'));
        self::assertTrue($this->context->has(Context::CONFIG_PREFIX . 'section1'));
        self::assertEquals($section1Config, $this->context->get(Context::CONFIG_PREFIX . 'section1'));

        self::assertTrue($this->context->hasConfig());
        self::assertEquals($config, $this->context->getConfig());
        self::assertTrue($this->context->has(Context::CONFIG_PREFIX . ConfigUtil::DEFINITION));
        self::assertEquals($config, $this->context->get(Context::CONFIG_PREFIX . ConfigUtil::DEFINITION));

        self::assertTrue($this->context->hasConfigOf('section2'));
        self::assertNull($this->context->getConfigOf('section2'));
        self::assertTrue($this->context->has(Context::CONFIG_PREFIX . 'section2'));
        self::assertNull($this->context->get(Context::CONFIG_PREFIX . 'section2'));

        // test that a config is loaded only once
        self::assertEquals($config, $this->context->getConfig());
    }

    public function testLoadConfigByGetConfigOfWhenExceptionOccurs()
    {
        $version      = '1.1';
        $requestType  = 'rest';
        $entityClass  = 'Test\Class';
        $configExtras = [
            new TestConfigSection('section1'),
            new TestConfigSection('section2')
        ];
        $exception = new \RuntimeException('some error');

        $this->context->setVersion($version);
        $this->context->getRequestType()->add($requestType);
        $this->context->setConfigExtras($configExtras);
        $this->context->setClassName($entityClass);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $entityClass,
                $version,
                new RequestType([$requestType]),
                $configExtras
            )
            ->willThrowException($exception);

        // test that a config is not loaded yet
        self::assertFalse($this->context->hasConfig());
        self::assertFalse($this->context->hasConfigOf('section1'));
        self::assertFalse($this->context->hasConfigOf('section2'));

        try {
            $this->context->getConfigOf('section1'); // load config
        } catch (\RuntimeException $e) {
            self::assertSame($exception, $e);
        }
        self::assertTrue($this->context->hasConfigOf('section1'));
        self::assertTrue($this->context->has(Context::CONFIG_PREFIX . 'section1'));
        self::assertNull($this->context->get(Context::CONFIG_PREFIX . 'section1'));

        self::assertTrue($this->context->hasConfig());
        self::assertNull($this->context->getConfig());
        self::assertTrue($this->context->has(Context::CONFIG_PREFIX . ConfigUtil::DEFINITION));
        self::assertNull($this->context->get(Context::CONFIG_PREFIX . ConfigUtil::DEFINITION));

        self::assertTrue($this->context->hasConfigOf('section2'));
        self::assertNull($this->context->getConfigOf('section2'));
        self::assertTrue($this->context->has(Context::CONFIG_PREFIX . 'section2'));
        self::assertNull($this->context->get(Context::CONFIG_PREFIX . 'section2'));

        // test that a config is loaded only once
        self::assertNull($this->context->getConfig());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage A class name must be set in the context before a configuration is loaded.
     */
    public function testLoadConfigNoClassName()
    {
        $this->context->getConfig();
    }

    public function testConfigWhenItIsSetExplicitly()
    {
        $config = new EntityDefinitionConfig();
        $config->setExcludeAll();

        $this->context->setConfigExtras([new TestConfigSection('section1')]);
        $this->context->setClassName('Test\Class');

        $this->configProvider->expects(self::never())
            ->method('getConfig');

        $this->context->setConfig($config);

        self::assertTrue($this->context->hasConfig());
        self::assertEquals($config, $this->context->getConfig());
        self::assertTrue($this->context->has(Context::CONFIG_PREFIX . ConfigUtil::DEFINITION));
        self::assertEquals($config, $this->context->get(Context::CONFIG_PREFIX . ConfigUtil::DEFINITION));

        self::assertTrue($this->context->hasConfigOf('section1'));
        self::assertNull($this->context->getConfigOf('section1'));
        self::assertTrue($this->context->has(Context::CONFIG_PREFIX . 'section1'));
        self::assertNull($this->context->get(Context::CONFIG_PREFIX . 'section1'));

        // test remove config
        $this->context->setConfig(null);
        self::assertTrue($this->context->hasConfig());
        self::assertNull($this->context->getConfig());
        self::assertTrue($this->context->hasConfigOf('section1'));
        self::assertNull($this->context->getConfigOf('section1'));
        self::assertTrue($this->context->has(Context::CONFIG_PREFIX . 'section1'));
        self::assertNull($this->context->get(Context::CONFIG_PREFIX . 'section1'));
    }

    public function testConfigWhenItIsSetExplicitlyForSection()
    {
        $section1Config = ['test'];

        $configExtras = [
            new TestConfigSection('section1'),
            new TestConfigSection('section2')
        ];

        $this->context->setConfigExtras($configExtras);
        $this->context->setClassName('Test\Class');

        $this->configProvider->expects(self::never())
            ->method('getConfig');

        $this->context->setConfigOf('section1', $section1Config);

        self::assertTrue($this->context->hasConfigOf('section1'));
        self::assertEquals($section1Config, $this->context->getConfigOf('section1'));
        self::assertTrue($this->context->has(Context::CONFIG_PREFIX . 'section1'));
        self::assertEquals($section1Config, $this->context->get(Context::CONFIG_PREFIX . 'section1'));

        self::assertTrue($this->context->hasConfigOf('section2'));
        self::assertNull($this->context->getConfigOf('section2'));
        self::assertTrue($this->context->has(Context::CONFIG_PREFIX . 'section2'));
        self::assertNull($this->context->get(Context::CONFIG_PREFIX . 'section2'));

        self::assertTrue($this->context->hasConfig());
        self::assertNull($this->context->getConfig());
        self::assertTrue($this->context->has(Context::CONFIG_PREFIX . ConfigUtil::DEFINITION));
        self::assertNull($this->context->get(Context::CONFIG_PREFIX . ConfigUtil::DEFINITION));
    }

    public function testHasConfigOfUndefinedSection()
    {
        $this->context->setConfigExtras([new TestConfigSection('section1')]);
        $this->context->setClassName('Test\Class');

        $this->configProvider->expects(self::never())
            ->method('getConfig');

        self::assertFalse($this->context->hasConfigOf('undefined'));
    }

    public function testGetConfigOfUndefinedSection()
    {
        $this->context->setConfigExtras([new TestConfigSection('section1')]);
        $this->context->setClassName('Test\Class');

        $this->configProvider->expects(self::never())
            ->method('getConfig');

        self::assertNull($this->context->getConfigOf('undefined'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetConfigOfUndefinedSection()
    {
        $this->context->setConfigExtras([new TestConfigSection('section1')]);
        $this->context->setClassName('Test\Class');

        $this->configProvider->expects(self::never())
            ->method('getConfig');

        $this->context->setConfigOf('undefined', []);
    }

    /**
     * @dataProvider configSectionProvider
     */
    public function testLoadKnownSectionConfigByGetConfigOf($configSection)
    {
        $mainConfig    = new EntityDefinitionConfig();
        $sectionConfig = [];

        $mainConfig->addField('field1');
        $mainConfig->addField('field2');
        $sectionConfig[ConfigUtil::FIELDS]['field1'] = null;

        $config = $this->getConfig(
            [
                ConfigUtil::DEFINITION => $mainConfig,
                $configSection         => $sectionConfig
            ]
        );

        $this->context->setClassName('Test\Class');
        $this->context->setVersion('1.2');
        // set "known" sections
        $this->context->setConfigExtras([new FiltersConfigExtra(), new SortersConfigExtra()]);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);

        // test that a config is not loaded yet
        self::assertFalse($this->context->hasConfig());
        foreach ($this->context->getConfigExtras() as $configExtra) {
            if ($configExtra instanceof ConfigExtraSectionInterface) {
                self::assertFalse($this->context->{'hasConfigOf' . lcfirst($configExtra->getName())}());
            }
        }

        $suffix = lcfirst($configSection);
        self::assertEquals($sectionConfig, $this->context->{'getConfigOf' . $suffix}()); // load config
        self::assertTrue($this->context->{'hasConfigOf' . $suffix}());

        self::assertTrue($this->context->hasConfig());
        self::assertEquals($mainConfig, $this->context->getConfig());

        foreach ($this->context->getConfigExtras() as $configExtra) {
            if ($configExtra instanceof ConfigExtraSectionInterface && $configExtra->getName() !== $configSection) {
                self::assertTrue($this->context->{'hasConfigOf' . lcfirst($configExtra->getName())}());
                self::assertNull($this->context->{'getConfigOf' . lcfirst($configExtra->getName())}());
            }
        }
    }

    /**
     * @dataProvider configSectionProvider
     */
    public function testConfigWhenIsSetExplicitlyForKnownSection($configSection, $sectionConfig)
    {
        $this->context->setClassName('Test\Class');
        // set "known" sections
        $this->context->setConfigExtras([new FiltersConfigExtra(), new SortersConfigExtra()]);

        $this->configProvider->expects(self::never())
            ->method('getConfig');

        $suffix = lcfirst($configSection);
        $this->context->{'setConfigOf' . $suffix}($sectionConfig);

        self::assertTrue($this->context->{'hasConfigOf' . $suffix}());
        self::assertEquals($sectionConfig, $this->context->{'getConfigOf' . $suffix}());

        foreach ($this->context->getConfigExtras() as $configExtra) {
            if ($configExtra instanceof ConfigExtraSectionInterface && $configExtra->getName() !== $configSection) {
                self::assertTrue($this->context->{'hasConfigOf' . lcfirst($configExtra->getName())}());
                self::assertNull($this->context->{'getConfigOf' . lcfirst($configExtra->getName())}());
            }
        }

        self::assertTrue($this->context->hasConfig());
        self::assertNull($this->context->getConfig());
    }

    public function configSectionProvider()
    {
        return [
            [FiltersConfigExtra::NAME, new FiltersConfig()],
            [SortersConfigExtra::NAME, new SortersConfig()]
        ];
    }

    public function testFilters()
    {
        $testFilter = $this->createMock(FilterInterface::class);

        self::assertNotNull($this->context->getFilters());

        $this->context->getFilters()->set('test', $testFilter);
        self::assertSame($testFilter, $this->context->getFilters()->get('test'));
    }

    public function testDefaultAccessorForFilterValues()
    {
        self::assertNotNull($this->context->getFilterValues());
        self::assertFalse($this->context->getFilterValues()->has('test'));
        self::assertNull($this->context->getFilterValues()->get('test'));
    }

    public function testFilterValues()
    {
        $accessor = $this->createMock(FilterValueAccessorInterface::class);
        $this->context->setFilterValues($accessor);

        self::assertSame($accessor, $this->context->getFilterValues());
    }

    public function testMasterRequest()
    {
        self::assertFalse($this->context->isMasterRequest());
        self::assertFalse($this->context->get('masterRequest'));

        $this->context->setMasterRequest(true);
        self::assertTrue($this->context->isMasterRequest());
        self::assertTrue($this->context->get('masterRequest'));
    }

    public function testCorsRequest()
    {
        self::assertFalse($this->context->isCorsRequest());
        self::assertFalse($this->context->get('cors'));

        $this->context->setCorsRequest(true);
        self::assertTrue($this->context->isCorsRequest());
        self::assertTrue($this->context->get('cors'));
    }

    public function testHateoas()
    {
        self::assertFalse($this->context->isHateoasEnabled());
        self::assertFalse($this->context->get('hateoas'));

        $this->context->setHateoas(true);
        self::assertTrue($this->context->isHateoasEnabled());
        self::assertTrue($this->context->get('hateoas'));
    }

    public function testInfoRecords()
    {
        self::assertNull($this->context->getInfoRecords());

        $infoRecords = ['' => ['key' => 'value']];
        $this->context->setInfoRecords($infoRecords);
        self::assertEquals($infoRecords, $this->context->getInfoRecords());

        $this->context->setInfoRecords(null);
        self::assertNull($this->context->getInfoRecords());
    }

    public function testConfigExtras()
    {
        self::assertSame([], $this->context->getConfigExtras());

        $configExtra = new TestConfigExtra('test');

        $configExtras = [$configExtra];
        $this->context->setConfigExtras($configExtras);
        self::assertEquals($configExtras, $this->context->getConfigExtras());

        self::assertTrue($this->context->hasConfigExtra('test'));
        self::assertSame($configExtra, $this->context->getConfigExtra('test'));
        self::assertFalse($this->context->hasConfigExtra('another'));
        self::assertNull($this->context->getConfigExtra('another'));

        $anotherConfigExtra = new TestConfigExtra('another');
        $configExtras[]     = $anotherConfigExtra;
        $this->context->addConfigExtra($anotherConfigExtra);
        self::assertEquals($configExtras, $this->context->getConfigExtras());

        unset($configExtras[0]);
        $configExtras = array_values($configExtras);
        $this->context->removeConfigExtra('test');
        self::assertEquals($configExtras, $this->context->getConfigExtras());

        // test remove of non existing extra
        $this->context->removeConfigExtra('test');
        self::assertEquals($configExtras, $this->context->getConfigExtras());

        $this->context->setConfigExtras([]);
        self::assertSame([], $this->context->getConfigExtras());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected an array of "Oro\Bundle\ApiBundle\Config\ConfigExtraInterface".
     */
    public function testSetInvalidConfigExtras()
    {
        $this->context->setConfigExtras(['test']);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The "test" config extra already exists.
     */
    public function testAddDuplicateConfigExtra()
    {
        $configExtras = [new TestConfigExtra('test')];
        $this->context->setConfigExtras($configExtras);

        $this->context->addConfigExtra(new TestConfigExtra('test'));
    }

    public function testLoadMetadata()
    {
        $version      = '1.1';
        $requestType  = 'rest';
        $entityClass  = 'Test\Class';
        $configExtras = [
            new TestConfigSection('section1'),
            new TestConfigSection('section2')
        ];

        $config         = new EntityDefinitionConfig();
        $metadata       = new EntityMetadata();
        $metadataExtras = [new TestMetadataExtra('extra1')];

        $this->context->setVersion($version);
        $this->context->getRequestType()->add($requestType);
        $this->context->setConfigExtras($configExtras);
        $this->context->setMetadataExtras($metadataExtras);
        $this->context->setClassName($entityClass);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $entityClass,
                $version,
                new RequestType([$requestType]),
                $configExtras
            )
            ->willReturn($this->getConfig([ConfigUtil::DEFINITION => $config]));
        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(
                $entityClass,
                $version,
                new RequestType([$requestType]),
                $config,
                $metadataExtras
            )
            ->willReturn($metadata);

        // test that metadata are not loaded yet
        self::assertFalse($this->context->hasMetadata());

        self::assertSame($metadata, $this->context->getMetadata()); // load metadata
        self::assertTrue($this->context->hasMetadata());
        self::assertTrue($this->context->has(Context::METADATA));
        self::assertSame($metadata, $this->context->get(Context::METADATA));

        self::assertEquals($config, $this->context->getConfig());

        // test that metadata are loaded only once
        self::assertSame($metadata, $this->context->getMetadata());
    }

    public function testLoadMetadataWhenHateoasIsEnabled()
    {
        $version = '1.1';
        $requestType = 'rest';
        $entityClass = 'Test\Class';
        $configExtras = [
            new TestConfigSection('section1'),
            new TestConfigSection('section2')
        ];

        $config = new EntityDefinitionConfig();
        $metadata = new EntityMetadata();
        $metadataExtras = [new TestMetadataExtra('extra1')];

        $this->context->setVersion($version);
        $this->context->getRequestType()->add($requestType);
        $this->context->setConfigExtras($configExtras);
        $this->context->setMetadataExtras($metadataExtras);
        $this->context->setClassName($entityClass);
        $this->context->setHateoas(true);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $entityClass,
                $version,
                new RequestType([$requestType]),
                $configExtras
            )
            ->willReturn($this->getConfig([ConfigUtil::DEFINITION => $config]));
        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(
                $entityClass,
                $version,
                new RequestType([$requestType]),
                $config,
                array_merge($metadataExtras, [new HateoasMetadataExtra($this->context->getFilterValues())])
            )
            ->willReturn($metadata);

        // test that metadata are not loaded yet
        self::assertFalse($this->context->hasMetadata());

        self::assertSame($metadata, $this->context->getMetadata()); // load metadata
        self::assertTrue($this->context->hasMetadata());
        self::assertTrue($this->context->has(Context::METADATA));
        self::assertSame($metadata, $this->context->get(Context::METADATA));

        self::assertEquals($config, $this->context->getConfig());

        // test that metadata are loaded only once
        self::assertSame($metadata, $this->context->getMetadata());
    }

    public function testLoadMetadataNoClassName()
    {
        $this->metadataProvider->expects(self::never())
            ->method('getMetadata');

        self::assertNull($this->context->getMetadata());
        self::assertTrue($this->context->hasMetadata());
    }

    public function testLoadMetadataWhenExceptionOccurs()
    {
        $version      = '1.1';
        $requestType  = 'rest';
        $entityClass  = 'Test\Class';
        $configExtras = [
            new TestConfigSection('section1'),
            new TestConfigSection('section2')
        ];
        $exception = new \RuntimeException('some error');

        $config         = new EntityDefinitionConfig();
        $metadataExtras = [new TestMetadataExtra('extra1')];

        $this->context->setVersion($version);
        $this->context->getRequestType()->add($requestType);
        $this->context->setConfigExtras($configExtras);
        $this->context->setMetadataExtras($metadataExtras);
        $this->context->setClassName($entityClass);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $entityClass,
                $version,
                new RequestType([$requestType]),
                $configExtras
            )
            ->willReturn($this->getConfig([ConfigUtil::DEFINITION => $config]));
        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(
                $entityClass,
                $version,
                new RequestType([$requestType]),
                $config,
                $metadataExtras
            )
            ->willThrowException($exception);

        // test that metadata are not loaded yet
        self::assertFalse($this->context->hasMetadata());

        try {
            $this->context->getMetadata(); // load metadata
        } catch (\RuntimeException $e) {
            self::assertSame($exception, $e);
        }
        self::assertTrue($this->context->hasMetadata());
        self::assertTrue($this->context->has(Context::METADATA));
        self::assertNull($this->context->get(Context::METADATA));

        self::assertEquals($config, $this->context->getConfig());

        // test that metadata are loaded only once
        self::assertNull($this->context->getMetadata());
    }

    public function testLoadMetadataWhenExceptionOccursInLoadConfig()
    {
        $version = '1.1';
        $requestType = 'rest';
        $entityClass = 'Test\Class';
        $configExtras = [
            new TestConfigSection('section1'),
            new TestConfigSection('section2')
        ];
        $exception = new \RuntimeException('some error');

        $metadataExtras = [new TestMetadataExtra('extra1')];

        $this->context->setVersion($version);
        $this->context->getRequestType()->add($requestType);
        $this->context->setConfigExtras($configExtras);
        $this->context->setMetadataExtras($metadataExtras);
        $this->context->setClassName($entityClass);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $entityClass,
                $version,
                new RequestType([$requestType]),
                $configExtras
            )
            ->willThrowException($exception);
        $this->metadataProvider->expects(self::never())
            ->method('getMetadata');

        try {
            $this->context->getConfig(); // load config
        } catch (\RuntimeException $e) {
            self::assertSame($exception, $e);
        }

        // test that metadata are not loaded yet
        self::assertFalse($this->context->hasMetadata());

        // load metadata
        self::assertNull($this->context->getMetadata());

        self::assertTrue($this->context->hasMetadata());
        self::assertTrue($this->context->has(Context::METADATA));
        self::assertNull($this->context->get(Context::METADATA));

        // test that metadata are loaded only once
        self::assertNull($this->context->getMetadata());
    }

    public function testMetadataWhenItIsSetExplicitly()
    {
        $metadata = new EntityMetadata();

        $this->context->setClassName('Test\Class');

        $this->configProvider->expects(self::never())
            ->method('getConfig');
        $this->metadataProvider->expects(self::never())
            ->method('getMetadata');

        $this->context->setMetadata($metadata);

        self::assertTrue($this->context->hasMetadata());
        self::assertSame($metadata, $this->context->getMetadata());
        self::assertTrue($this->context->has(Context::METADATA));
        self::assertSame($metadata, $this->context->get(Context::METADATA));

        // test remove metadata
        $this->context->setMetadata(null);
        self::assertFalse($this->context->hasMetadata());
    }

    public function testMetadataExtras()
    {
        self::assertSame([], $this->context->getMetadataExtras());

        $metadataExtras = [new TestMetadataExtra('test')];
        $this->context->setMetadataExtras($metadataExtras);
        self::assertEquals($metadataExtras, $this->context->getMetadataExtras());

        self::assertTrue($this->context->hasMetadataExtra('test'));
        self::assertFalse($this->context->hasMetadataExtra('another'));

        self::assertSame($metadataExtras[0], $this->context->getMetadataExtra('test'));
        self::assertNull($this->context->getMetadataExtra('another'));

        $anotherMetadataExtra = new TestMetadataExtra('another');
        $metadataExtras[] = $anotherMetadataExtra;
        $this->context->addMetadataExtra($anotherMetadataExtra);
        self::assertEquals($metadataExtras, $this->context->getMetadataExtras());

        unset($metadataExtras[0]);
        $metadataExtras = array_values($metadataExtras);
        $this->context->removeMetadataExtra('test');
        self::assertEquals($metadataExtras, $this->context->getMetadataExtras());

        // test remove of non existing extra
        $this->context->removeMetadataExtra('test');
        self::assertEquals($metadataExtras, $this->context->getMetadataExtras());

        $this->context->setMetadataExtras([]);
        self::assertSame([], $this->context->getMetadataExtras());
    }

    public function testMetadataExtrasWhenActionExistsInContext()
    {
        $action = 'test_action';
        $this->context->setAction($action);

        self::assertEquals(
            [new ActionMetadataExtra($action)],
            $this->context->getMetadataExtras()
        );

        // test that ActionMetadataExtra is not added twice
        self::assertEquals(
            [new ActionMetadataExtra($action)],
            $this->context->getMetadataExtras()
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The "action" metadata extra already exists.
     */
    public function testActionMetadataExtrasCannotBeOverridden()
    {
        $action = 'test_action';
        $this->context->setAction($action);
        $this->context->addMetadataExtra(new ActionMetadataExtra('other_action'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected an array of "Oro\Bundle\ApiBundle\Metadata\MetadataExtraInterface".
     */
    public function testSetInvalidMetadataExtras()
    {
        $this->context->setMetadataExtras(['test']);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The "test" metadata extra already exists.
     */
    public function testAddDuplicateMetadataExtra()
    {
        $metadataExtras = [new TestMetadataExtra('test')];
        $this->context->setMetadataExtras($metadataExtras);

        $this->context->addMetadataExtra(new TestMetadataExtra('test'));
    }

    public function testHasIdentifierFieldsShouldCauseMetadataLoading()
    {
        $entityClass = 'Test\Class';
        $metadata = new EntityMetadata();
        $metadata->setIdentifierFieldNames(['id']);

        $this->context->setVersion('1.1');
        $this->context->getRequestType()->add('rest');
        $this->context->setClassName($entityClass);
        $this->context->setConfig(new EntityDefinitionConfig());

        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with($entityClass)
            ->willReturn($metadata);

        self::assertTrue($this->context->hasIdentifierFields());
    }

    public function testHasIdentifierFieldsWithoutMetadata()
    {
        $this->context->setMetadata(null);

        self::assertFalse($this->context->hasIdentifierFields());
    }

    public function testHasIdentifierFieldsWithoutIdInMetadata()
    {
        $metadata = new EntityMetadata();

        $this->context->setMetadata($metadata);

        self::assertFalse($this->context->hasIdentifierFields());
    }

    public function testHasIdentifierFieldsWithIdInMetadata()
    {
        $metadata = new EntityMetadata();
        $metadata->setIdentifierFieldNames(['id']);

        $this->context->setMetadata($metadata);

        self::assertTrue($this->context->hasIdentifierFields());
    }

    public function testQuery()
    {
        $query = new \stdClass();

        self::assertFalse($this->context->hasQuery());
        self::assertNull($this->context->getQuery());

        $this->context->setQuery($query);
        self::assertTrue($this->context->hasQuery());
        self::assertSame($query, $this->context->getQuery());
        self::assertSame($query, $this->context->get(Context::QUERY));

        $this->context->setQuery(null);
        self::assertFalse($this->context->hasQuery());
        self::assertNull($this->context->getQuery());
    }

    public function testCriteria()
    {
        self::assertNull($this->context->getCriteria());

        $criteria = $this->createMock(Criteria::class);

        $this->context->setCriteria($criteria);
        self::assertSame($criteria, $this->context->getCriteria());
        self::assertSame($criteria, $this->context->get(Context::CRITERIA));
    }

    /**
     * @param array $data
     *
     * @return Config
     */
    protected function getConfig(array $data = [])
    {
        $result = new Config();
        foreach ($data as $sectionName => $config) {
            $result->set($sectionName, $config);
        }

        return $result;
    }
}
