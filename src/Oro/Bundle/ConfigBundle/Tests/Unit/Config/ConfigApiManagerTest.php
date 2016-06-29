<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Config;

use Oro\Bundle\ConfigBundle\Config\ApiTree\SectionDefinition;
use Oro\Bundle\ConfigBundle\Config\ApiTree\VariableDefinition;
use Oro\Bundle\ConfigBundle\Config\ConfigApiManager;
use Oro\Bundle\ConfigBundle\Exception\ItemNotFoundException;

class ConfigApiManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var ConfigApiManager */
    protected $manager;

    protected function setUp()
    {
        $this->configProvider = $this->getMock('Oro\Bundle\ConfigBundle\Provider\ProviderInterface');
        $this->configManager  = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = new ConfigApiManager($this->configProvider);
        $this->manager->addConfigManager('user', $this->configManager);
    }

    public function testGetConfigManager()
    {
        $this->assertSame($this->configManager, $this->manager->getConfigManager('user'));
        $this->assertNull($this->manager->getConfigManager('unknown'));
    }

    public function testGetScopes()
    {
        $this->assertEquals(['user'], $this->manager->getScopes());
    }

    public function testGetSections()
    {
        $apiTree     = new SectionDefinition('');
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

        $this->configProvider->expects($this->once())
            ->method('getApiTree')
            ->with(null)
            ->will($this->returnValue($apiTree));
        $this->assertSame(
            [
                'section2',
                'test_section',
                'test_section/bar',
                'test_section/foo',
            ],
            $this->manager->getSections()
        );
    }

    public function testHasSectionForKnownSection()
    {
        $path = 'test_section';

        $this->configProvider->expects($this->once())
            ->method('getApiTree')
            ->with($path)
            ->willReturn(new SectionDefinition($path));

        $this->assertTrue($this->manager->hasSection($path));
    }

    public function testHasSectionForUnknownSection()
    {
        $path = 'test_section';

        $this->configProvider->expects($this->once())
            ->method('getApiTree')
            ->with($path)
            ->willReturn(null);

        $this->assertFalse($this->manager->hasSection($path));
    }

    public function testHasSectionForUnknownSectionWhenConfigProviderThrowsItemNotFoundException()
    {
        $path = 'test_section';

        $this->configProvider->expects($this->once())
            ->method('getApiTree')
            ->with($path)
            ->willThrowException(new ItemNotFoundException());

        $this->assertFalse($this->manager->hasSection($path));
    }

    public function testGetData()
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

        $this->configProvider->expects($this->once())
            ->method('getApiTree')
            ->with($path)
            ->will($this->returnValue($apiTree));
        $this->configManager->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValueMap(
                    [
                        ['acme.item1', false, false, 'val1'],
                        ['acme.item2', false, false, 123],
                        ['acme.item3', false, false, ['val1' => 1, 'val2' => true]],
                        ['acme.item4', false, false, false],
                        ['acme.item5', false, false, ""],
                        ['acme.item6', false, false, "123"],
                    ]
                )
            );
        $datetime = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->configManager->expects($this->any())
            ->method('getInfo')
            ->will(
                $this->returnValue(
                    [
                        'createdAt' => $datetime,
                        'updatedAt' => $datetime,
                    ]
                )
            );

        $this->assertSame(
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
                ],
            ],
            $this->manager->getData($path)
        );
    }

    public function testGetDataItemForUnknownVariable()
    {
        $path = 'test_section';

        $this->configProvider->expects($this->once())
            ->method('getApiTree')
            ->with($path)
            ->willReturn(new SectionDefinition($path));

        $this->assertNull($this->manager->getDataItem('unknown', $path));
    }

    public function testGetDataItemForKnownVariableWithoutDataTransformer()
    {
        $path = 'test_section';
        $key = 'test_variable';
        $dataType = 'string';
        $value = 'test_value';
        $datetime = new \DateTime('now', new \DateTimeZone('UTC'));
        $apiTree = new SectionDefinition($path);
        $apiTree->addVariable(new VariableDefinition($key, $dataType));

        $this->configProvider->expects($this->once())
            ->method('getApiTree')
            ->with($path)
            ->willReturn($apiTree);
        $this->configManager->expects($this->once())
            ->method('get')
            ->with($key)
            ->willReturn($value);
        $this->configManager->expects($this->once())
            ->method('getInfo')
            ->will(
                $this->returnValue(
                    [
                        'createdAt' => $datetime,
                        'updatedAt' => $datetime,
                    ]
                )
            );

        $this->assertEquals(
            [
                'key'       => $key,
                'type'      => $dataType,
                'value'     => $value,
                'createdAt' => $datetime,
                'updatedAt' => $datetime,
            ],
            $this->manager->getDataItem($key, $path, 'user')
        );
    }

    public function testGetDataItemForKnownVariableWithDataTransformer()
    {
        $path = 'test_section';
        $key = 'test_variable';
        $dataType = 'string';
        $value = 'test_value';
        $transformedValue = 'transformed_test_value';
        $datetime = new \DateTime('now', new \DateTimeZone('UTC'));
        $apiTree = new SectionDefinition($path);
        $apiTree->addVariable(new VariableDefinition($key, $dataType));
        $dataTransformer = $this->getMock('Oro\Bundle\ConfigBundle\Config\DataTransformerInterface');

        $this->configProvider->expects($this->once())
            ->method('getApiTree')
            ->with($path)
            ->willReturn($apiTree);
        $this->configManager->expects($this->once())
            ->method('get')
            ->with($key)
            ->willReturn($value);
        $this->configManager->expects($this->once())
            ->method('getInfo')
            ->will(
                $this->returnValue(
                    [
                        'createdAt' => $datetime,
                        'updatedAt' => $datetime,
                    ]
                )
            );
        $this->configProvider->expects($this->once())
            ->method('getDataTransformer')
            ->with($key)
            ->willReturn($dataTransformer);
        $dataTransformer->expects($this->once())
            ->method('transform')
            ->with($value)
            ->willReturn($transformedValue);

        $this->assertEquals(
            [
                'key'       => $key,
                'type'      => $dataType,
                'value'     => $transformedValue,
                'createdAt' => $datetime,
                'updatedAt' => $datetime,
            ],
            $this->manager->getDataItem($key, $path, 'user')
        );
    }
}
