<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\Helper;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Oro\Bundle\TagBundle\Tests\Unit\Fixtures\Taggable;
use Oro\Bundle\TagBundle\Tests\Unit\Fixtures\TestEntity;

class TaggableHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var TaggableHelper */
    private $helper;

    /** @var  \PHPUnit\Framework\MockObject\MockObject|ConfigProvider */
    private $configProvider;

    protected function setUp()
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);

        $this->helper = new TaggableHelper($this->configProvider);
    }

    /**
     * @dataProvider getEntityIdDataProvider
     *
     * @param object $object
     * @param bool   $expectedId
     */
    public function testGetEntityId($object, $expectedId)
    {
        $this->assertEquals($expectedId, TaggableHelper::getEntityId($object));
    }

    /**
     * @dataProvider isImplementsTaggableDataProvider
     *
     * @param object $object
     * @param bool   $result
     */
    public function testIsImplementsTaggable($object, $result)
    {
        $this->assertEquals($result, TaggableHelper::isImplementsTaggable($object));
    }

    /**
     * @dataProvider isTaggableDataProvider
     *
     * @param object $object
     * @param bool   $result
     * @param bool   $needSetConfig
     * @param bool   $hasConfig
     * @param bool   $isEnabled
     */
    public function testIsTaggable($object, $result, $needSetConfig = false, $hasConfig = false, $isEnabled = false)
    {
        if ($needSetConfig) {
            $this->setConfigProvider($object, $hasConfig, $isEnabled);
        }
        $this->assertEquals($result, $this->helper->isTaggable($object));
    }

    /** @return array */
    public function isTaggableDataProvider()
    {
        return [
            'implements Taggable' => [new Taggable(), true],
            'enabled in config'   => [new \stdClass(), true, true, true, true],
            'has no config'       => [new \stdClass(), false, true, false],
            'disabled in config'  => [new \stdClass(), false, true, true, false]
        ];
    }


    /**
     * @dataProvider isTaggableDataProvider
     *
     * @param object     $object
     * @param bool       $expected
     * @param bool|false $needSetConfig
     * @param bool|false $hasConfig
     * @param bool|false $isEnableGridColumn
     */
    public function testIsEnableGridColumn(
        $object,
        $expected,
        $needSetConfig = false,
        $hasConfig = false,
        $isEnableGridColumn = false
    ) {
        if ($needSetConfig) {
            $this->setConfigProvider($object, $hasConfig, $isEnableGridColumn);
        }
        $this->assertEquals($expected, $this->helper->isEnableGridColumn($object));
    }

    /**
     * @dataProvider isTaggableDataProvider
     *
     * @param object     $object
     * @param bool       $expected
     * @param bool|false $needSetConfig
     * @param bool|false $hasConfig
     * @param bool|false $isEnableGridFilter
     */
    public function testIsEnableGridFilter(
        $object,
        $expected,
        $needSetConfig = false,
        $hasConfig = false,
        $isEnableGridFilter = false
    ) {
        if ($needSetConfig) {
            $this->setConfigProvider($object, $hasConfig, $isEnableGridFilter);
        }
        $this->assertEquals($expected, $this->helper->isEnableGridColumn($object));
    }

    /**
     * @dataProvider shouldRenderDefaultDataProvider
     *
     * @param object     $object
     * @param bool       $expected
     * @param bool|false $shouldRenderDefault
     */
    public function testShouldRenderDefault($object, $expected, $shouldRenderDefault)
    {
        $this->setConfigProvider($object, true, $shouldRenderDefault);
        $this->assertEquals($expected, $this->helper->shouldRenderDefault($object));
    }

    public function shouldRenderDefaultDataProvider()
    {
        return [
            'not implements Taggable' => [new \stdClass(), false, false],
            'enable rendering' => [new Taggable(), true, true],
            'disable rendering' => [new Taggable(), false, false],
        ];
    }

    /** @return array */
    public function isImplementsTaggableDataProvider()
    {
        return [
            'implements Taggable'     => [new Taggable(), true],
            'not implements Taggable' => [new \stdClass(), false]
        ];
    }

    /** @return array */
    public function getEntityIdDataProvider()
    {
        return [
            'from Taggable interface method' => [new Taggable(['id' => 100]), 100],
            'from getId method'              => [new TestEntity(200), 200]
        ];
    }

    private function setConfigProvider($object, $hasConfig, $isEnabled)
    {
        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($object)
            ->willReturn($hasConfig);

        if ($hasConfig) {
            $config = $this->createMock(ConfigInterface::class);
            $config->expects($this->once())
                ->method('is')
                ->willReturn($isEnabled);

            $this->configProvider->expects($this->once())
                ->method('getConfig')
                ->with($object)
                ->willReturn($config);
        }
    }
}
