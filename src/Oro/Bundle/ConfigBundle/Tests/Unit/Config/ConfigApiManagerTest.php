<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Config;

use Oro\Bundle\ConfigBundle\Config\ApiTree\SectionDefinition;
use Oro\Bundle\ConfigBundle\Config\ApiTree\VariableDefinition;
use Oro\Bundle\ConfigBundle\Config\ConfigApiManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Config\DataTransformerInterface;
use Oro\Bundle\ConfigBundle\Exception\ItemNotFoundException;
use Oro\Bundle\ConfigBundle\Provider\ProviderInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ConfigApiManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ConfigApiManager */
    private $manager;

    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ProviderInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->manager = new ConfigApiManager($this->configProvider);
        $this->manager->addConfigManager('user', $this->configManager);
    }

    public function testGetConfigManager(): void
    {
        self::assertSame($this->configManager, $this->manager->getConfigManager('user'));
        self::assertNull($this->manager->getConfigManager('unknown'));
    }

    public function testGetScopes(): void
    {
        self::assertEquals(['user'], $this->manager->getScopes());
    }

    public function testGetSections(): void
    {
        $apiTree = new SectionDefinition('');
        $testSection = new SectionDefinition('test_section');
        $apiTree->addSubSection($testSection);
        $testSection->addVariable(new VariableDefinition('acme.item1', 'string'));
        $fooSection = new SectionDefinition('foo');
        $testSection->addSubSection($fooSection);
        $fooSection->addVariable(new VariableDefinition('acme.item2', 'string'));
        $barSection = new SectionDefinition('bar');
        $testSection->addSubSection($barSection);
        $barSection->addVariable(new VariableDefinition('acme.item2', 'string'));
        $section2 = new SectionDefinition('section2');
        $apiTree->addSubSection($section2);
        $section2->addVariable(new VariableDefinition('acme.item3', 'string'));

        $this->configProvider->expects(self::once())
            ->method('getApiTree')
            ->with(null)
            ->willReturn($apiTree);

        self::assertSame(
            [
                'section2',
                'test_section',
                'test_section/bar',
                'test_section/foo',
            ],
            $this->manager->getSections()
        );
    }

    public function testHasSectionForKnownSection(): void
    {
        $path = 'test_section';

        $this->configProvider->expects(self::once())
            ->method('getApiTree')
            ->with($path)
            ->willReturn(new SectionDefinition($path));

        self::assertTrue($this->manager->hasSection($path));
    }

    public function testHasSectionForUnknownSection(): void
    {
        $path = 'test_section';

        $this->configProvider->expects(self::once())
            ->method('getApiTree')
            ->with($path)
            ->willThrowException(new ItemNotFoundException());

        self::assertFalse($this->manager->hasSection($path));
    }

    public function testHasSectionForUnknownSectionWhenConfigProviderThrowsItemNotFoundException(): void
    {
        $path = 'test_section';

        $this->configProvider->expects(self::once())
            ->method('getApiTree')
            ->with($path)
            ->willThrowException(new ItemNotFoundException());

        self::assertFalse($this->manager->hasSection($path));
    }

    public function testGetDataItemKeys(): void
    {
        $apiTree = new SectionDefinition('');
        $apiTree->addVariable(new VariableDefinition('acme.item1', 'string'));
        $subSection1 = new SectionDefinition('sub_section1');
        $apiTree->addSubSection($subSection1);
        $subSection1->addVariable(new VariableDefinition('acme.item2', 'string'));
        $subSection11 = new SectionDefinition('sub_section11');
        $subSection1->addSubSection($subSection11);
        $subSection11->addVariable(new VariableDefinition('acme.item2', 'string'));
        $subSection11->addVariable(new VariableDefinition('acme.item3', 'string'));

        $this->configProvider->expects(self::once())
            ->method('getApiTree')
            ->with(self::isNull())
            ->willReturn($apiTree);

        self::assertEquals(
            ['acme.item1', 'acme.item2', 'acme.item3'],
            $this->manager->getDataItemKeys()
        );
    }

    public function testGetData(): void
    {
        $path = 'section1/section11';

        $apiTree = new SectionDefinition('section11');
        $apiTree->addVariable(new VariableDefinition('acme.item1', 'string'));
        $subSection1 = new SectionDefinition('sub_section1');
        $apiTree->addSubSection($subSection1);
        $subSection1->addVariable(new VariableDefinition('acme.item2', 'integer'));
        $subSection11 = new SectionDefinition('sub_section11');
        $subSection1->addSubSection($subSection11);
        $subSection11->addVariable(new VariableDefinition('acme.item2', 'integer'));
        $subSection11->addVariable(new VariableDefinition('acme.item3', 'array'));
        $subSection11->addVariable(new VariableDefinition('acme.item4', 'boolean'));
        $subSection11->addVariable(new VariableDefinition('acme.item5', 'boolean'));
        $subSection11->addVariable(new VariableDefinition('acme.item6', 'integer'));

        $this->configProvider->expects(self::once())
            ->method('getApiTree')
            ->with($path)
            ->willReturn($apiTree);
        $this->configManager->expects(self::any())
            ->method('get')
            ->willReturnMap([
                ['acme.item1', false, false, null, 'val1'],
                ['acme.item2', false, false, null, 123],
                ['acme.item3', false, false, null, ['val1' => 1, 'val2' => true]],
                ['acme.item4', false, false, null, false],
                ['acme.item5', false, false, null, ''],
                ['acme.item6', false, false, null, '123'],
            ]);
        $datetime = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->configManager->expects(self::any())
            ->method('getInfo')
            ->willReturn(['createdAt' => $datetime, 'updatedAt' => $datetime]);

        self::assertEquals(
            [
                [
                    'key' => 'acme.item1',
                    'type' => 'string',
                    'value' => 'val1',
                    'createdAt' => $datetime,
                    'updatedAt' => $datetime,
                ],
                [
                    'key' => 'acme.item2',
                    'type' => 'integer',
                    'value' => 123,
                    'createdAt' => $datetime,
                    'updatedAt' => $datetime,
                ],
                [
                    'key' => 'acme.item3',
                    'type' => 'array',
                    'value' => ['val1' => 1, 'val2' => true],
                    'createdAt' => $datetime,
                    'updatedAt' => $datetime,
                ],
                [
                    'key' => 'acme.item4',
                    'type' => 'boolean',
                    'value' => false,
                    'createdAt' => $datetime,
                    'updatedAt' => $datetime,
                ],
                [
                    'key' => 'acme.item5',
                    'type' => 'boolean',
                    'value' => false,
                    'createdAt' => $datetime,
                    'updatedAt' => $datetime,
                ],
                [
                    'key' => 'acme.item6',
                    'type' => 'integer',
                    'value' => 123,
                    'createdAt' => $datetime,
                    'updatedAt' => $datetime,
                ]
            ],
            $this->manager->getData($path)
        );
    }

    public function testGetDataWithScopeId(): void
    {
        $path = 'section1/section11';
        $scopeId = 123;

        $apiTree = new SectionDefinition('section11');
        $apiTree->addVariable(new VariableDefinition('acme.item1', 'string'));

        $this->configProvider->expects(self::once())
            ->method('getApiTree')
            ->with($path)
            ->willReturn($apiTree);
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('acme.item1', false, false, $scopeId)
            ->willReturn('val1');
        $datetime = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->configManager->expects(self::once())
            ->method('getInfo')
            ->with('acme.item1', $scopeId)
            ->willReturn(['createdAt' => $datetime, 'updatedAt' => $datetime]);

        self::assertEquals(
            [
                [
                    'key' => 'acme.item1',
                    'type' => 'string',
                    'value' => 'val1',
                    'createdAt' => $datetime,
                    'updatedAt' => $datetime,
                ]
            ],
            $this->manager->getData($path, 'user', $scopeId)
        );
    }

    public function testGetDataItemForUnknownVariable(): void
    {
        $path = 'test_section';

        $this->configProvider->expects(self::once())
            ->method('getApiTree')
            ->with($path)
            ->willReturn(new SectionDefinition($path));

        self::assertNull($this->manager->getDataItem('unknown', $path));
    }

    public function testGetDataItemForKnownVariableWithoutDataTransformer(): void
    {
        $path = 'test_section';
        $key = 'test_variable';
        $dataType = 'string';
        $value = 'test_value';
        $datetime = new \DateTime('now', new \DateTimeZone('UTC'));
        $apiTree = new SectionDefinition($path);
        $apiTree->addVariable(new VariableDefinition($key, $dataType));

        $this->configProvider->expects(self::once())
            ->method('getApiTree')
            ->with($path)
            ->willReturn($apiTree);
        $this->configManager->expects(self::once())
            ->method('get')
            ->with($key, false, false, null)
            ->willReturn($value);
        $this->configManager->expects(self::once())
            ->method('getInfo')
            ->with($key, null)
            ->willReturn(['createdAt' => $datetime, 'updatedAt' => $datetime]);

        self::assertEquals(
            [
                'key'       => $key,
                'type'      => $dataType,
                'value'     => $value,
                'createdAt' => $datetime,
                'updatedAt' => $datetime,
            ],
            $this->manager->getDataItem($key, $path)
        );
    }

    public function testGetDataItemForKnownVariableWithDataTransformer(): void
    {
        $path = 'test_section';
        $key = 'test_variable';
        $dataType = 'string';
        $value = 'test_value';
        $transformedValue = 'transformed_test_value';
        $datetime = new \DateTime('now', new \DateTimeZone('UTC'));
        $apiTree = new SectionDefinition($path);
        $apiTree->addVariable(new VariableDefinition($key, $dataType));
        $dataTransformer = $this->createMock(DataTransformerInterface::class);

        $this->configProvider->expects(self::once())
            ->method('getApiTree')
            ->with($path)
            ->willReturn($apiTree);
        $this->configManager->expects(self::once())
            ->method('get')
            ->with($key, false, false, null)
            ->willReturn($value);
        $this->configManager->expects(self::once())
            ->method('getInfo')
            ->with($key, null)
            ->willReturn(['createdAt' => $datetime, 'updatedAt' => $datetime]);
        $this->configProvider->expects(self::once())
            ->method('getDataTransformer')
            ->with($key)
            ->willReturn($dataTransformer);
        $dataTransformer->expects(self::once())
            ->method('transform')
            ->with($value)
            ->willReturn($transformedValue);

        self::assertEquals(
            [
                'key'       => $key,
                'type'      => $dataType,
                'value'     => $transformedValue,
                'createdAt' => $datetime,
                'updatedAt' => $datetime,
            ],
            $this->manager->getDataItem($key, $path)
        );
    }

    public function testGetDataItemWithScopeId(): void
    {
        $scopeId = 123;
        $path = 'test_section';
        $key = 'test_variable';
        $dataType = 'string';
        $value = 'test_value';
        $datetime = new \DateTime('now', new \DateTimeZone('UTC'));
        $apiTree = new SectionDefinition($path);
        $apiTree->addVariable(new VariableDefinition($key, $dataType));

        $this->configProvider->expects(self::once())
            ->method('getApiTree')
            ->with($path)
            ->willReturn($apiTree);
        $this->configManager->expects(self::once())
            ->method('get')
            ->with($key, false, false, $scopeId)
            ->willReturn($value);
        $this->configManager->expects(self::once())
            ->method('getInfo')
            ->with($key, $scopeId)
            ->willReturn(['createdAt' => $datetime, 'updatedAt' => $datetime]);

        self::assertEquals(
            [
                'key'       => $key,
                'type'      => $dataType,
                'value'     => $value,
                'createdAt' => $datetime,
                'updatedAt' => $datetime,
            ],
            $this->manager->getDataItem($key, $path, 'user', $scopeId)
        );
    }

    public function testGetDataItemSectionsForUnknownVariable(): void
    {
        $this->configProvider->expects(self::once())
            ->method('getApiTree')
            ->willReturn(new SectionDefinition(''));

        self::assertEquals([], $this->manager->getDataItemSections('unknown'));
    }

    public function testGetDataItemSections(): void
    {
        $apiTree = new SectionDefinition('');

        $section1 = new SectionDefinition('foo');
        $section1->addVariable(new VariableDefinition('variable1', 'string'));
        $apiTree->addSubSection($section1);

        $section2 = new SectionDefinition('buz');
        $section2->addVariable(new VariableDefinition('variable2', 'string'));
        $section2->addVariable(new VariableDefinition('variable3', 'string'));
        $apiTree->addSubSection($section2);

        $section3 = new SectionDefinition('bar');
        $section3->addVariable(new VariableDefinition('variable4', 'string'));
        $apiTree->addSubSection($section3);
        $section31 = new SectionDefinition('bat');
        $section31->addVariable(new VariableDefinition('variable2', 'string'));
        $section3->addSubSection($section31);

        $this->configProvider->expects(self::any())
            ->method('getApiTree')
            ->willReturn($apiTree);

        self::assertEquals(['foo'], $this->manager->getDataItemSections('variable1'));
        self::assertEquals(['bar/bat', 'buz'], $this->manager->getDataItemSections('variable2'));
        self::assertEquals(['buz'], $this->manager->getDataItemSections('variable3'));
        self::assertEquals(['bar'], $this->manager->getDataItemSections('variable4'));
        self::assertEquals([], $this->manager->getDataItemSections('unknown'));
    }
}
