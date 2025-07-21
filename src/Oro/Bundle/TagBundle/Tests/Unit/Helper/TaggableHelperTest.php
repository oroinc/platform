<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\Helper;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Oro\Bundle\TagBundle\Tests\Unit\Fixtures\Taggable;
use Oro\Bundle\TagBundle\Tests\Unit\Fixtures\TestEntity;
use Oro\Bundle\TagBundle\Tests\Unit\Stub\NotTaggableEntityStub;
use Oro\Bundle\TagBundle\Tests\Unit\Stub\TaggableEntityStub;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TaggableHelperTest extends TestCase
{
    private ConfigProvider&MockObject $configProvider;
    private TaggableHelper $helper;

    #[\Override]
    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);

        $this->helper = new TaggableHelper($this->configProvider);
    }

    /**
     * @dataProvider getEntityIdDataProvider
     */
    public function testGetEntityId(object $object, int $expectedId): void
    {
        $this->assertEquals($expectedId, TaggableHelper::getEntityId($object));
    }

    /**
     * @dataProvider isImplementsTaggableDataProvider
     */
    public function testIsImplementsTaggable(object $object, bool $result): void
    {
        $this->assertEquals($result, TaggableHelper::isImplementsTaggable($object));
    }

    /**
     * @dataProvider isTaggableDataProvider
     */
    public function testIsTaggable(
        object $object,
        bool $result,
        bool $needSetConfig = false,
        bool $hasConfig = false,
        bool $isEnabled = false
    ): void {
        if ($needSetConfig) {
            $this->setConfigProvider($object, $hasConfig, $isEnabled);
        }
        $this->assertEquals($result, $this->helper->isTaggable($object));
    }

    public function isTaggableDataProvider(): array
    {
        return [
            'implements Taggable' => [new Taggable(), true],
            'enabled in config'   => [new \stdClass(), true, true, true, true],
            'has no config'       => [new \stdClass(), false, true, false],
            'disabled in config'  => [new \stdClass(), false, true, true, false]
        ];
    }

    public function testGetTaggableEntities(): void
    {
        $this->configProvider->expects($this->once())
            ->method('getConfigs')
            ->willReturn([
                $this->getEntityConfig(TaggableEntityStub::class, []),
                $this->getEntityConfig(NotTaggableEntityStub::class, []),
                $this->getEntityConfig(User::class, ['enabled' => true])
            ]);

        $this->assertEquals(
            [
                TaggableEntityStub::class,
                User::class
            ],
            $this->helper->getTaggableEntities()
        );
    }

    /**
     * @dataProvider isTaggableDataProvider
     */
    public function testIsEnableGridColumn(
        object $object,
        bool $expected,
        bool $needSetConfig = false,
        bool $hasConfig = false,
        bool $isEnableGridColumn = false
    ): void {
        if ($needSetConfig) {
            $this->setConfigProvider($object, $hasConfig, $isEnableGridColumn);
        }
        $this->assertEquals($expected, $this->helper->isEnableGridColumn($object));
    }

    /**
     * @dataProvider isTaggableDataProvider
     */
    public function testIsEnableGridFilter(
        object $object,
        bool $expected,
        bool $needSetConfig = false,
        bool $hasConfig = false,
        bool $isEnableGridFilter = false
    ): void {
        if ($needSetConfig) {
            $this->setConfigProvider($object, $hasConfig, $isEnableGridFilter);
        }
        $this->assertEquals($expected, $this->helper->isEnableGridColumn($object));
    }

    /**
     * @dataProvider shouldRenderDefaultDataProvider
     */
    public function testShouldRenderDefault(object $object, bool $expected, bool $shouldRenderDefault): void
    {
        $this->setConfigProvider($object, true, $shouldRenderDefault);
        $this->assertEquals($expected, $this->helper->shouldRenderDefault($object));
    }

    public function shouldRenderDefaultDataProvider(): array
    {
        return [
            'not implements Taggable' => [new \stdClass(), false, false],
            'enable rendering' => [new Taggable(), true, true],
            'disable rendering' => [new Taggable(), false, false],
        ];
    }

    public function isImplementsTaggableDataProvider(): array
    {
        return [
            'implements Taggable'     => [new Taggable(), true],
            'not implements Taggable' => [new \stdClass(), false]
        ];
    }

    public function getEntityIdDataProvider(): array
    {
        return [
            'from Taggable interface method' => [new Taggable(['id' => 100]), 100],
            'from getId method'              => [new TestEntity(200), 200]
        ];
    }

    private function getEntityConfig(string $entityClass, array $values): Config
    {
        $entityConfig = new Config(new EntityConfigId('tag', $entityClass));
        $entityConfig->setValues($values);

        return $entityConfig;
    }

    private function setConfigProvider(object|string $object, bool $hasConfig, bool $isEnabled)
    {
        $class = is_object($object) ? get_class($object) : $object;

        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($class)
            ->willReturn($hasConfig);

        if ($hasConfig) {
            $config = $this->createMock(ConfigInterface::class);
            $config->expects($this->once())
                ->method('is')
                ->willReturn($isEnabled);

            $this->configProvider->expects($this->once())
                ->method('getConfig')
                ->with($class)
                ->willReturn($config);
        }
    }
}
