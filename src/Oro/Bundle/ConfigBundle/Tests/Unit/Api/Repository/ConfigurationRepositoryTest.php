<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Api\Repository;

use Oro\Bundle\ConfigBundle\Api\Model\ConfigurationOption;
use Oro\Bundle\ConfigBundle\Api\Model\ConfigurationSection;
use Oro\Bundle\ConfigBundle\Api\Repository\ConfigurationRepository;
use Oro\Bundle\ConfigBundle\Config\ConfigApiManager;

class ConfigurationRepositoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigApiManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ConfigurationRepository */
    private $configRepository;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigApiManager::class);

        $this->configRepository = new ConfigurationRepository($this->configManager);
    }

    public function testGetSectionIds(): void
    {
        $this->configManager->expects(self::once())
            ->method('getSections')
            ->willReturn(['section', 'section/sub-section']);

        self::assertEquals(
            ['section', 'section.sub-section'],
            $this->configRepository->getSectionIds()
        );
    }

    public function testGetUnknownSection(): void
    {
        $sectionId = 'section';

        $this->configManager->expects(self::once())
            ->method('hasSection')
            ->with($sectionId)
            ->willReturn(false);

        self::assertNull($this->configRepository->getSection($sectionId, 'scope', 123));
    }

    public function testGetSection(): void
    {
        $scope = 'scope';
        $sectionId = 'section.sub-section';
        $decodedSectionId = 'section/sub-section';
        $datetime = new \DateTime();

        $this->configManager->expects(self::once())
            ->method('hasSection')
            ->with($decodedSectionId)
            ->willReturn(true);
        $this->configManager->expects(self::once())
            ->method('getData')
            ->with($decodedSectionId, $scope)
            ->willReturn(
                [
                    [
                        'key'       => 'key1',
                        'type'      => 'string',
                        'value'     => 'test_value',
                        'createdAt' => $datetime,
                        'updatedAt' => $datetime
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
        self::assertEquals(
            $expectedResult,
            $this->configRepository->getSection($sectionId, $scope)
        );
    }

    public function testGetSectionOptions(): void
    {
        $scope = 'scope';
        $sectionId = 'section.sub-section';
        $decodedSectionId = 'section/sub-section';
        $datetime = new \DateTime();

        $this->configManager->expects(self::once())
            ->method('getData')
            ->with($decodedSectionId, $scope)
            ->willReturn(
                [
                    [
                        'key'       => 'key1',
                        'type'      => 'string',
                        'value'     => 'test_value',
                        'createdAt' => $datetime,
                        'updatedAt' => $datetime
                    ]
                ]
            );

        $expectedConfigOption = new ConfigurationOption($scope, 'key1');
        $expectedConfigOption->setDataType('string');
        $expectedConfigOption->setValue('test_value');
        $expectedConfigOption->setCreatedAt($datetime);
        $expectedConfigOption->setUpdatedAt($datetime);
        self::assertEquals(
            [$expectedConfigOption],
            $this->configRepository->getSectionOptions($sectionId, $scope)
        );
    }

    public function testGetSectionOption(): void
    {
        $optionKey = 'key1';
        $scope = 'scope';
        $sectionId = 'section.sub-section';
        $decodedSectionId = 'section/sub-section';
        $datetime = new \DateTime();

        $this->configManager->expects(self::once())
            ->method('getDataItem')
            ->with($optionKey, $decodedSectionId, $scope)
            ->willReturn(
                [
                    'key'       => $optionKey,
                    'type'      => 'string',
                    'value'     => 'test_value',
                    'createdAt' => $datetime,
                    'updatedAt' => $datetime
                ]
            );

        $expectedConfigOption = new ConfigurationOption($scope, $optionKey);
        $expectedConfigOption->setDataType('string');
        $expectedConfigOption->setValue('test_value');
        $expectedConfigOption->setCreatedAt($datetime);
        $expectedConfigOption->setUpdatedAt($datetime);
        self::assertEquals(
            $expectedConfigOption,
            $this->configRepository->getSectionOption($optionKey, $sectionId, $scope)
        );
    }

    public function testGetOptionKeys(): void
    {
        $optionKeys = ['key1', 'key2'];

        $this->configManager->expects(self::once())
            ->method('getDataItemKeys')
            ->willReturn($optionKeys);

        self::assertEquals($optionKeys, $this->configRepository->getOptionKeys());
    }

    public function testGetUnknownOption(): void
    {
        $optionKey = 'key1';

        $this->configManager->expects(self::once())
            ->method('getDataItemSections')
            ->with($optionKey)
            ->willReturn([]);

        self::assertNull($this->configRepository->getOption($optionKey, 'scope'));
    }

    public function testGetOption(): void
    {
        $optionKey = 'key1';
        $scope = 'scope';
        $sectionId = 'section.sub-section';
        $datetime = new \DateTime();

        $this->configManager->expects(self::once())
            ->method('getDataItemSections')
            ->with($optionKey)
            ->willReturn([$sectionId, 'another_section']);
        $this->configManager->expects(self::once())
            ->method('getDataItem')
            ->with($optionKey, $sectionId, $scope)
            ->willReturn(
                [
                    'key'       => $optionKey,
                    'type'      => 'string',
                    'value'     => 'test_value',
                    'createdAt' => $datetime,
                    'updatedAt' => $datetime
                ]
            );

        $expectedConfigOption = new ConfigurationOption($scope, $optionKey);
        $expectedConfigOption->setDataType('string');
        $expectedConfigOption->setValue('test_value');
        $expectedConfigOption->setCreatedAt($datetime);
        $expectedConfigOption->setUpdatedAt($datetime);
        self::assertEquals(
            $expectedConfigOption,
            $this->configRepository->getOption($optionKey, $scope)
        );
    }
}
