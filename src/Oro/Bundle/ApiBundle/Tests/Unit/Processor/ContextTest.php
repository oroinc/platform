<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class ContextTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $metadataProvider;

    /** @var Context */
    protected $context;

    protected function setUp()
    {
        $this->configProvider   = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\MetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();

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

        $this->assertFalse($headers->has($key1));
        $this->assertFalse(isset($headers[$key1]));
        $this->assertNull($headers->get($key1));
        $this->assertNull($headers[$key1]);

        $headers->set($key1, $value1);
        $this->assertTrue($headers->has($key1));
        $this->assertTrue(isset($headers[$key1]));
        $this->assertEquals($value1, $headers->get($key1));
        $this->assertEquals($value1, $headers[$key1]);

        $this->assertTrue($headers->has(strtoupper($key1)));
        $this->assertTrue(isset($headers[strtoupper($key1)]));
        $this->assertEquals($value1, $headers->get(strtoupper($key1)));
        $this->assertEquals($value1, $headers[strtoupper($key1)]);

        $headers->remove(strtoupper($key1));
        $this->assertFalse($headers->has($key1));
        $this->assertFalse(isset($headers[$key1]));
        $this->assertNull($headers->get($key1));
        $this->assertNull($headers[$key1]);

        $headers[strtoupper($key2)] = $value2;
        $this->assertTrue($headers->has($key2));
        $this->assertTrue(isset($headers[$key2]));
        $this->assertEquals($value2, $headers->get($key2));
        $this->assertEquals($value2, $headers[$key2]);

        unset($headers[$key2]);
        $this->assertFalse($headers->has(strtoupper($key2)));
        $this->assertFalse(isset($headers[strtoupper($key2)]));
        $this->assertNull($headers->get(strtoupper($key2)));
        $this->assertNull($headers[strtoupper($key2)]);

        $headers->set(strtoupper($key1), null);
        $this->assertTrue($headers->has($key1));
        $this->assertTrue(isset($headers[$key1]));
        $this->assertNull($headers->get($key1));
        $this->assertNull($headers[$key1]);

        $this->assertEquals(1, count($headers));
        $this->assertEquals([$key1 => null], $headers->toArray());

        $headers->clear();
        $this->assertEquals(0, count($headers));
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

        $this->assertFalse($headers->has($key1));
        $this->assertFalse(isset($headers[$key1]));
        $this->assertNull($headers->get($key1));
        $this->assertNull($headers[$key1]);

        $headers->set($key1, $value1);
        $this->assertTrue($headers->has($key1));
        $this->assertTrue(isset($headers[$key1]));
        $this->assertEquals($value1, $headers->get($key1));
        $this->assertEquals($value1, $headers[$key1]);

        $this->assertFalse($headers->has(strtoupper($key1)));
        $this->assertFalse(isset($headers[strtoupper($key1)]));
        $this->assertNull($headers->get(strtoupper($key1)));
        $this->assertNull($headers[strtoupper($key1)]);
        $headers->remove(strtoupper($key1));
        $this->assertTrue($headers->has($key1));
        unset($headers[strtoupper($key1)]);
        $this->assertTrue($headers->has($key1));

        $headers->remove($key1);
        $this->assertFalse($headers->has($key1));
        $this->assertFalse(isset($headers[$key1]));
        $this->assertNull($headers->get($key1));
        $this->assertNull($headers[$key1]);

        $headers[$key2] = $value2;
        $this->assertTrue($headers->has($key2));
        $this->assertTrue(isset($headers[$key2]));
        $this->assertEquals($value2, $headers->get($key2));
        $this->assertEquals($value2, $headers[$key2]);

        unset($headers[$key2]);
        $this->assertFalse($headers->has($key2));
        $this->assertFalse(isset($headers[$key2]));
        $this->assertNull($headers->get($key2));
        $this->assertNull($headers[$key2]);

        $headers->set($key1, null);
        $this->assertTrue($headers->has($key1));
        $this->assertTrue(isset($headers[$key1]));
        $this->assertNull($headers->get($key1));
        $this->assertNull($headers[$key1]);

        $this->assertEquals(1, count($headers));
        $this->assertEquals([$key1 => null], $headers->toArray());

        $headers->clear();
        $this->assertEquals(0, count($headers));
    }

    public function testVersion()
    {
        $this->assertNull($this->context->getVersion());

        $this->context->setVersion('test');
        $this->assertEquals('test', $this->context->getVersion());
        $this->assertEquals('test', $this->context->get(Context::VERSION));
    }

    public function testClassName()
    {
        $this->assertNull($this->context->getClassName());

        $this->context->setClassName('test');
        $this->assertEquals('test', $this->context->getClassName());
        $this->assertEquals('test', $this->context->get(Context::CLASS_NAME));
    }

    public function testLoadConfigByGetConfig()
    {
        $version        = '1.1';
        $requestType    = 'rest';
        $entityClass    = 'Test\Class';
        $configSections = ['section1', 'section2'];
        $configExtras   = ['extra1'];

        $config         = ConfigUtil::getInitialConfig();
        $section1Config = ['test'];

        $this->context->setVersion($version);
        $this->context->setRequestType($requestType);
        $this->context->setConfigSections($configSections);
        $this->context->setConfigExtras($configExtras);
        $this->context->setClassName($entityClass);

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                $entityClass,
                $version,
                $requestType,
                array_merge($configSections, $configExtras)
            )
            ->willReturn(
                [
                    ConfigUtil::DEFINITION => $config,
                    'section1'             => $section1Config
                ]
            );

        // test that a config is not loaded yet
        $this->assertFalse($this->context->hasConfig());
        $this->assertFalse($this->context->hasConfigOf('section1'));
        $this->assertFalse($this->context->hasConfigOf('section2'));

        $this->assertEquals($config, $this->context->getConfig()); // load config
        $this->assertTrue($this->context->hasConfig());
        $this->assertTrue($this->context->has(Context::CONFIG_PREFIX . ConfigUtil::DEFINITION));
        $this->assertEquals($config, $this->context->get(Context::CONFIG_PREFIX . ConfigUtil::DEFINITION));

        $this->assertTrue($this->context->hasConfigOf('section1'));
        $this->assertEquals($section1Config, $this->context->getConfigOf('section1'));
        $this->assertTrue($this->context->has(Context::CONFIG_PREFIX . 'section1'));
        $this->assertEquals($section1Config, $this->context->get(Context::CONFIG_PREFIX . 'section1'));

        $this->assertTrue($this->context->hasConfigOf('section2'));
        $this->assertNull($this->context->getConfigOf('section2'));
        $this->assertTrue($this->context->has(Context::CONFIG_PREFIX . 'section2'));
        $this->assertNull($this->context->get(Context::CONFIG_PREFIX . 'section2'));

        // test that a config is loaded only once
        $this->assertEquals($config, $this->context->getConfig());
    }

    public function testLoadConfigByGetConfigOf()
    {
        $version        = '1.1';
        $requestType    = 'rest';
        $entityClass    = 'Test\Class';
        $configSections = ['section1', 'section2'];

        $config         = ConfigUtil::getInitialConfig();
        $section1Config = ['test'];

        $this->context->setVersion($version);
        $this->context->setRequestType($requestType);
        $this->context->setConfigSections($configSections);
        $this->context->setClassName($entityClass);

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                $entityClass,
                $version,
                $requestType,
                $configSections
            )
            ->willReturn(
                [
                    ConfigUtil::DEFINITION => $config,
                    'section1'             => $section1Config
                ]
            );

        // test that a config is not loaded yet
        $this->assertFalse($this->context->hasConfig());
        $this->assertFalse($this->context->hasConfigOf('section1'));
        $this->assertFalse($this->context->hasConfigOf('section2'));

        $this->assertEquals($section1Config, $this->context->getConfigOf('section1')); // load config
        $this->assertTrue($this->context->hasConfigOf('section1'));
        $this->assertTrue($this->context->has(Context::CONFIG_PREFIX . 'section1'));
        $this->assertEquals($section1Config, $this->context->get(Context::CONFIG_PREFIX . 'section1'));

        $this->assertTrue($this->context->hasConfig());
        $this->assertEquals($config, $this->context->getConfig());
        $this->assertTrue($this->context->has(Context::CONFIG_PREFIX . ConfigUtil::DEFINITION));
        $this->assertEquals($config, $this->context->get(Context::CONFIG_PREFIX . ConfigUtil::DEFINITION));

        $this->assertTrue($this->context->hasConfigOf('section2'));
        $this->assertNull($this->context->getConfigOf('section2'));
        $this->assertTrue($this->context->has(Context::CONFIG_PREFIX . 'section2'));
        $this->assertNull($this->context->get(Context::CONFIG_PREFIX . 'section2'));

        // test that a config is loaded only once
        $this->assertEquals($config, $this->context->getConfig());
    }

    public function testLoadConfigNoClassName()
    {
        $this->context->setConfigSections(['section1']);

        $this->configProvider->expects($this->never())
            ->method('getConfig');

        // test that a config is not loaded yet
        $this->assertFalse($this->context->hasConfig());
        $this->assertFalse($this->context->hasConfigOf('section1'));

        $this->assertNull($this->context->getConfig()); // load config
        $this->assertTrue($this->context->hasConfig());
        $this->assertTrue($this->context->has(Context::CONFIG_PREFIX . ConfigUtil::DEFINITION));
        $this->assertNull($this->context->get(Context::CONFIG_PREFIX . ConfigUtil::DEFINITION));

        $this->assertTrue($this->context->hasConfigOf('section1'));
        $this->assertNull($this->context->getConfigOf('section1'));
        $this->assertTrue($this->context->has(Context::CONFIG_PREFIX . 'section1'));
        $this->assertNull($this->context->get(Context::CONFIG_PREFIX . 'section1'));
    }

    public function testConfigWhenItIsSetExplicitly()
    {
        $config = ConfigUtil::getInitialConfig();

        $this->context->setConfigSections(['section1']);
        $this->context->setClassName('Test\Class');

        $this->configProvider->expects($this->never())
            ->method('getConfig');

        $this->context->setConfig($config);

        $this->assertTrue($this->context->hasConfig());
        $this->assertEquals($config, $this->context->getConfig());
        $this->assertTrue($this->context->has(Context::CONFIG_PREFIX . ConfigUtil::DEFINITION));
        $this->assertEquals($config, $this->context->get(Context::CONFIG_PREFIX . ConfigUtil::DEFINITION));

        $this->assertTrue($this->context->hasConfigOf('section1'));
        $this->assertNull($this->context->getConfigOf('section1'));
        $this->assertTrue($this->context->has(Context::CONFIG_PREFIX . 'section1'));
        $this->assertNull($this->context->get(Context::CONFIG_PREFIX . 'section1'));
    }

    public function testConfigWhenItIsSetExplicitlyForSection()
    {
        $section1Config = ['test'];

        $this->context->setConfigSections(['section1', 'section2']);
        $this->context->setClassName('Test\Class');

        $this->configProvider->expects($this->never())
            ->method('getConfig');

        $this->context->setConfigOf('section1', $section1Config);

        $this->assertTrue($this->context->hasConfigOf('section1'));
        $this->assertEquals($section1Config, $this->context->getConfigOf('section1'));
        $this->assertTrue($this->context->has(Context::CONFIG_PREFIX . 'section1'));
        $this->assertEquals($section1Config, $this->context->get(Context::CONFIG_PREFIX . 'section1'));

        $this->assertTrue($this->context->hasConfigOf('section2'));
        $this->assertNull($this->context->getConfigOf('section2'));
        $this->assertTrue($this->context->has(Context::CONFIG_PREFIX . 'section2'));
        $this->assertNull($this->context->get(Context::CONFIG_PREFIX . 'section2'));

        $this->assertTrue($this->context->hasConfig());
        $this->assertNull($this->context->getConfig());
        $this->assertTrue($this->context->has(Context::CONFIG_PREFIX . ConfigUtil::DEFINITION));
        $this->assertNull($this->context->get(Context::CONFIG_PREFIX . ConfigUtil::DEFINITION));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetConfigOfUndefinedSection()
    {
        $this->context->setConfigSections(['section1']);
        $this->context->setClassName('Test\Class');

        $this->configProvider->expects($this->never())
            ->method('getConfig');

        $this->context->getConfigOf('undefined');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetConfigOfUndefinedSection()
    {
        $this->context->setConfigSections(['section1']);
        $this->context->setClassName('Test\Class');

        $this->configProvider->expects($this->never())
            ->method('getConfig');

        $this->context->setConfigOf('undefined', []);
    }

    public function testConfigSections()
    {
        $this->assertSame([], $this->context->getConfigSections());
        $this->assertNull($this->context->get(Context::CONFIG_SECTIONS));

        $this->context->setConfigSections(['test']);
        $this->assertEquals(['test'], $this->context->getConfigSections());
        $this->assertEquals(['test'], $this->context->get(Context::CONFIG_SECTIONS));

        $this->assertTrue($this->context->hasConfigSection('test'));
        $this->assertFalse($this->context->hasConfigSection('another'));

        $this->context->addConfigSection('another');
        $this->assertEquals(['test', 'another'], $this->context->getConfigSections());
        $this->assertEquals(['test', 'another'], $this->context->get(Context::CONFIG_SECTIONS));

        // test add of already existing section
        $this->context->addConfigSection('another');
        $this->assertEquals(['test', 'another'], $this->context->getConfigSections());
        $this->assertEquals(['test', 'another'], $this->context->get(Context::CONFIG_SECTIONS));

        $this->context->removeConfigSection('test');
        $this->assertEquals(['another'], $this->context->getConfigSections());
        $this->assertEquals(['another'], $this->context->get(Context::CONFIG_SECTIONS));

        // test remove of non existing section
        $this->context->removeConfigSection('test');
        $this->assertEquals(['another'], $this->context->getConfigSections());
        $this->assertEquals(['another'], $this->context->get(Context::CONFIG_SECTIONS));

        $this->context->setConfigSections([]);
        $this->assertSame([], $this->context->getConfigSections());
        $this->assertNull($this->context->get(Context::CONFIG_SECTIONS));
    }

    public function testConfigExtras()
    {
        $this->assertSame([], $this->context->getConfigExtras());
        $this->assertNull($this->context->get(Context::CONFIG_EXTRAS));

        $this->context->setConfigExtras(['test']);
        $this->assertEquals(['test'], $this->context->getConfigExtras());
        $this->assertEquals(['test'], $this->context->get(Context::CONFIG_EXTRAS));

        $this->assertTrue($this->context->hasConfigExtra('test'));
        $this->assertFalse($this->context->hasConfigExtra('another'));

        $this->context->addConfigExtra('another');
        $this->assertEquals(['test', 'another'], $this->context->getConfigExtras());
        $this->assertEquals(['test', 'another'], $this->context->get(Context::CONFIG_EXTRAS));

        // test add of already existing extra
        $this->context->addConfigExtra('another');
        $this->assertEquals(['test', 'another'], $this->context->getConfigExtras());
        $this->assertEquals(['test', 'another'], $this->context->get(Context::CONFIG_EXTRAS));

        $this->context->removeConfigExtra('test');
        $this->assertEquals(['another'], $this->context->getConfigExtras());
        $this->assertEquals(['another'], $this->context->get(Context::CONFIG_EXTRAS));

        // test remove of non existing extra
        $this->context->removeConfigExtra('test');
        $this->assertEquals(['another'], $this->context->getConfigExtras());
        $this->assertEquals(['another'], $this->context->get(Context::CONFIG_EXTRAS));

        $this->context->setConfigExtras([]);
        $this->assertSame([], $this->context->getConfigExtras());
        $this->assertNull($this->context->get(Context::CONFIG_EXTRAS));
    }

    public function testLoadMetadata()
    {
        $version        = '1.1';
        $requestType    = 'rest';
        $entityClass    = 'Test\Class';
        $configSections = ['section1', 'section2'];

        $config         = ConfigUtil::getInitialConfig();
        $metadata       = new EntityMetadata();
        $metadataExtras = ['extra1'];

        $this->context->setVersion($version);
        $this->context->setRequestType($requestType);
        $this->context->setConfigSections($configSections);
        $this->context->setMetadataExtras($metadataExtras);
        $this->context->setClassName($entityClass);

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                $entityClass,
                $version,
                $requestType,
                $configSections
            )
            ->willReturn([ConfigUtil::DEFINITION => $config]);
        $this->metadataProvider->expects($this->once())
            ->method('getMetadata')
            ->with(
                $entityClass,
                $version,
                $requestType,
                $metadataExtras,
                $config
            )
            ->willReturn($metadata);

        // test that metadata are not loaded yet
        $this->assertFalse($this->context->hasMetadata());

        $this->assertSame($metadata, $this->context->getMetadata()); // load metadata
        $this->assertTrue($this->context->hasMetadata());
        $this->assertTrue($this->context->has(Context::METADATA));
        $this->assertSame($metadata, $this->context->get(Context::METADATA));

        $this->assertEquals($config, $this->context->getConfig());

        // test that metadata are loaded only once
        $this->assertSame($metadata, $this->context->getMetadata());
    }

    public function testLoadMetadataNoClassName()
    {
        $this->configProvider->expects($this->never())
            ->method('getConfig');
        $this->metadataProvider->expects($this->never())
            ->method('getMetadata');

        // test that metadata are not loaded yet
        $this->assertFalse($this->context->hasMetadata());

        $this->assertNull($this->context->getMetadata()); // load metadata
        $this->assertTrue($this->context->hasMetadata());
        $this->assertTrue($this->context->has(Context::METADATA));
        $this->assertNull($this->context->get(Context::METADATA));
    }

    public function testMetadataWhenItIsSetExplicitly()
    {
        $metadata = new EntityMetadata();

        $this->context->setClassName('Test\Class');

        $this->configProvider->expects($this->never())
            ->method('getConfig');
        $this->metadataProvider->expects($this->never())
            ->method('getMetadata');

        $this->context->setMetadata($metadata);

        $this->assertTrue($this->context->hasMetadata());
        $this->assertSame($metadata, $this->context->getMetadata());
        $this->assertTrue($this->context->has(Context::METADATA));
        $this->assertSame($metadata, $this->context->get(Context::METADATA));
    }

    public function testMetadataExtras()
    {
        $this->assertSame([], $this->context->getMetadataExtras());
        $this->assertNull($this->context->get(Context::METADATA_EXTRAS));

        $this->context->setMetadataExtras(['test']);
        $this->assertEquals(['test'], $this->context->getMetadataExtras());
        $this->assertEquals(['test'], $this->context->get(Context::METADATA_EXTRAS));

        $this->assertTrue($this->context->hasMetadataExtra('test'));
        $this->assertFalse($this->context->hasMetadataExtra('another'));

        $this->context->addMetadataExtra('another');
        $this->assertEquals(['test', 'another'], $this->context->getMetadataExtras());
        $this->assertEquals(['test', 'another'], $this->context->get(Context::METADATA_EXTRAS));

        // test add of already existing extra
        $this->context->addMetadataExtra('another');
        $this->assertEquals(['test', 'another'], $this->context->getMetadataExtras());
        $this->assertEquals(['test', 'another'], $this->context->get(Context::METADATA_EXTRAS));

        $this->context->removeMetadataExtra('test');
        $this->assertEquals(['another'], $this->context->getMetadataExtras());
        $this->assertEquals(['another'], $this->context->get(Context::METADATA_EXTRAS));

        // test remove of non existing extra
        $this->context->removeMetadataExtra('test');
        $this->assertEquals(['another'], $this->context->getMetadataExtras());
        $this->assertEquals(['another'], $this->context->get(Context::METADATA_EXTRAS));

        $this->context->setMetadataExtras([]);
        $this->assertSame([], $this->context->getMetadataExtras());
        $this->assertNull($this->context->get(Context::METADATA_EXTRAS));
    }

    public function testQuery()
    {
        $this->assertFalse($this->context->hasQuery());
        $this->assertNull($this->context->getQuery());

        $query = new \stdClass();

        $this->context->setQuery($query);
        $this->assertTrue($this->context->hasQuery());
        $this->assertSame($query, $this->context->getQuery());
        $this->assertSame($query, $this->context->get(Context::QUERY));

        $this->context->setQuery(null);
        $this->assertTrue($this->context->hasQuery());
    }

    public function testCriteria()
    {
        $this->assertNull($this->context->getCriteria());

        $criteria = $this->getMockBuilder('Oro\Bundle\ApiBundle\Collection\Criteria')
            ->disableOriginalConstructor()
            ->getMock();

        $this->context->setCriteria($criteria);
        $this->assertSame($criteria, $this->context->getCriteria());
        $this->assertSame($criteria, $this->context->get(Context::CRITERIA));
    }
}
