<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList;

use Oro\Bundle\ApiBundle\Processor\GetList\GetListContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class GetListContextTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $metadataProvider;

    /** @var GetListContext */
    protected $context;

    protected function setUp()
    {
        $this->configProvider   = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\MetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->context = new GetListContext($this->configProvider, $this->metadataProvider);
    }

    public function testDefaultAccessorConfigSections()
    {
        $this->assertEquals(
            [ConfigUtil::FILTERS, ConfigUtil::SORTERS],
            $this->context->getConfigSections()
        );
    }

    /**
     * @dataProvider configSectionProvider
     */
    public function testLoadConfigByGetConfigOf($configSection)
    {
        $mainConfig    = ConfigUtil::getInitialConfig();
        $sectionConfig = ConfigUtil::getInitialConfig();

        $mainConfig[ConfigUtil::FIELDS]['field1']    = null;
        $mainConfig[ConfigUtil::FIELDS]['field2']    = null;
        $sectionConfig[ConfigUtil::FIELDS]['field1'] = null;

        $config = [
            ConfigUtil::DEFINITION => $mainConfig,
            $configSection         => $sectionConfig
        ];

        $this->context->setClassName('Test\Class');

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        // test that a config is not loaded yet
        $this->assertFalse($this->context->hasConfig());
        foreach ($this->context->getConfigSections() as $section) {
            $this->assertFalse($this->context->{'hasConfigOf' . lcfirst($section)}());
        }

        $suffix = lcfirst($configSection);
        $this->assertEquals($sectionConfig, $this->context->{'getConfigOf' . $suffix}()); // load config
        $this->assertTrue($this->context->{'hasConfigOf' . $suffix}());

        $this->assertTrue($this->context->hasConfig());
        $this->assertEquals($mainConfig, $this->context->getConfig());

        foreach ($this->context->getConfigSections() as $section) {
            if ($section !== $configSection) {
                $this->assertTrue($this->context->{'hasConfigOf' . lcfirst($section)}());
                $this->assertNull($this->context->{'getConfigOf' . lcfirst($section)}());
            }
        }
    }

    /**
     * @dataProvider configSectionProvider
     */
    public function testConfigWhenIsSetExplicitlyForSection($configSection)
    {
        $sectionConfig = ConfigUtil::getInitialConfig();

        $this->context->setClassName('Test\Class');

        $this->configProvider->expects($this->never())
            ->method('getConfig');

        $suffix = lcfirst($configSection);
        $this->context->{'setConfigOf' . $suffix}($sectionConfig);

        $this->assertTrue($this->context->{'hasConfigOf' . $suffix}());
        $this->assertEquals($sectionConfig, $this->context->{'getConfigOf' . $suffix}());

        foreach ($this->context->getConfigSections() as $section) {
            if ($section !== $configSection) {
                $this->assertTrue($this->context->{'hasConfigOf' . lcfirst($section)}());
                $this->assertNull($this->context->{'getConfigOf' . lcfirst($section)}());
            }
        }

        $this->assertTrue($this->context->hasConfig());
        $this->assertNull($this->context->getConfig());
    }

    public function configSectionProvider()
    {
        return [
            [ConfigUtil::FILTERS],
            [ConfigUtil::SORTERS]
        ];
    }

    public function testFilters()
    {
        $testFilter = $this->getMock('Oro\Bundle\ApiBundle\Filter\FilterInterface');

        $this->assertNotNull($this->context->getFilters());

        $this->context->getFilters()->set('test', $testFilter);
        $this->assertSame($testFilter, $this->context->getFilters()->get('test'));
    }

    public function testDefaultAccessorForFilterValues()
    {
        $this->assertNotNull($this->context->getFilterValues());
        $this->assertFalse($this->context->getFilterValues()->has('test'));
        $this->assertNull($this->context->getFilterValues()->get('test'));
    }

    public function testFilterValues()
    {
        $accessor = $this->getMock('Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface');
        $this->context->setFilterValues($accessor);

        $this->assertSame($accessor, $this->context->getFilterValues());
    }

    public function testTotalCountCallback()
    {
        $this->assertNull($this->context->getTotalCountCallback());

        $totalCountCallback = [$this, 'calculateTotalCount'];

        $this->context->setTotalCountCallback($totalCountCallback);
        $this->assertEquals($totalCountCallback, $this->context->getTotalCountCallback());
        $this->assertEquals($totalCountCallback, $this->context->get(GetListContext::TOTAL_COUNT_CALLBACK));
    }
}
