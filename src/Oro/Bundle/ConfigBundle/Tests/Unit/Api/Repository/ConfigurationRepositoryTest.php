<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Api\Repository;

use Oro\Bundle\ConfigBundle\Api\Model\ConfigurationOption;
use Oro\Bundle\ConfigBundle\Api\Model\ConfigurationSection;
use Oro\Bundle\ConfigBundle\Api\Repository\ConfigurationRepository;

class ConfigurationRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var ConfigurationRepository */
    protected $configRepository;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigApiManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configRepository = new ConfigurationRepository($this->configManager);
    }

    public function testGetScopes()
    {
        $scopes = ['user', 'global'];

        $this->configManager->expects($this->once())
            ->method('getScopes')
            ->willReturn($scopes);

        $this->assertEquals($scopes, $this->configRepository->getScopes());
    }

    public function testGetSectionIds()
    {
        $this->configManager->expects($this->once())
            ->method('getSections')
            ->willReturn(['section', 'section/sub-section']);

        $this->assertEquals(
            ['section', 'section.sub-section'],
            $this->configRepository->getSectionIds()
        );
    }

    public function testGetUnknownSection()
    {
        $this->configManager->expects($this->once())
            ->method('hasSection')
            ->with('section')
            ->willReturn(false);

        $this->assertNull($this->configRepository->getSection('section', 'scope'));
    }

    public function testGetSection()
    {
        $scope = 'scope';
        $sectionId = 'section.sub-section';
        $decodedSectionId = 'section/sub-section';
        $datetime = new \DateTime();

        $this->configManager->expects($this->once())
            ->method('hasSection')
            ->with($decodedSectionId)
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getData')
            ->with($decodedSectionId, $scope)
            ->willReturn(
                [
                    [
                        'key'       => 'key1',
                        'type'      => 'string',
                        'value'     => 'test_value',
                        'createdAt' => $datetime,
                        'updatedAt' => $datetime,
                    ]
                ]
            );

        $expectedResult = new ConfigurationSection($sectionId);
        $expectedConfigOption = new ConfigurationOption($scope, 'key1');
        $expectedConfigOption->setDataType('string');
        $expectedConfigOption->setValue('test_value');
        $expectedConfigOption->setCreatedAt($datetime);
        $expectedConfigOption->setUpdatedAt($datetime);
        $expectedResult->setOptions([$expectedConfigOption]);
        $this->assertEquals(
            $expectedResult,
            $this->configRepository->getSection($sectionId, $scope)
        );
    }

    public function testGetSectionOptions()
    {
        $scope = 'scope';
        $sectionId = 'section.sub-section';
        $decodedSectionId = 'section/sub-section';
        $datetime = new \DateTime();

        $this->configManager->expects($this->once())
            ->method('getData')
            ->with($decodedSectionId, $scope)
            ->willReturn(
                [
                    [
                        'key'       => 'key1',
                        'type'      => 'string',
                        'value'     => 'test_value',
                        'createdAt' => $datetime,
                        'updatedAt' => $datetime,
                    ]
                ]
            );

        $expectedConfigOption = new ConfigurationOption($scope, 'key1');
        $expectedConfigOption->setDataType('string');
        $expectedConfigOption->setValue('test_value');
        $expectedConfigOption->setCreatedAt($datetime);
        $expectedConfigOption->setUpdatedAt($datetime);
        $this->assertEquals(
            [$expectedConfigOption],
            $this->configRepository->getSectionOptions($sectionId, $scope)
        );
    }

    public function testGetSectionOption()
    {
        $optionKey = 'key1';
        $scope = 'scope';
        $sectionId = 'section.sub-section';
        $decodedSectionId = 'section/sub-section';
        $datetime = new \DateTime();

        $this->configManager->expects($this->once())
            ->method('getDataItem')
            ->with($optionKey, $decodedSectionId, $scope)
            ->willReturn(
                [
                    'key'       => $optionKey,
                    'type'      => 'string',
                    'value'     => 'test_value',
                    'createdAt' => $datetime,
                    'updatedAt' => $datetime,
                ]
            );

        $expectedConfigOption = new ConfigurationOption($scope, $optionKey);
        $expectedConfigOption->setDataType('string');
        $expectedConfigOption->setValue('test_value');
        $expectedConfigOption->setCreatedAt($datetime);
        $expectedConfigOption->setUpdatedAt($datetime);
        $this->assertEquals(
            $expectedConfigOption,
            $this->configRepository->getSectionOption($optionKey, $sectionId, $scope)
        );
    }
}
