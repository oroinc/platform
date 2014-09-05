<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Config;

use Oro\Bundle\ConfigBundle\Config\ApiTree\SectionDefinition;
use Oro\Bundle\ConfigBundle\Config\ApiTree\VariableDefinition;
use Oro\Bundle\ConfigBundle\Config\ConfigApiManager;

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

        $this->manager = new ConfigApiManager($this->configProvider, $this->configManager);
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
                    ]
                )
            );

        $this->assertSame(
            [
                ['key' => 'acme.item1', 'type' => 'string', 'value' => 'val1'],
                ['key' => 'acme.item2', 'type' => 'integer', 'value' => 123],
                ['key' => 'acme.item3', 'type' => 'array', 'value' => ['val1' => 1, 'val2' => true]],
            ],
            $this->manager->getData($path)
        );
    }
}
