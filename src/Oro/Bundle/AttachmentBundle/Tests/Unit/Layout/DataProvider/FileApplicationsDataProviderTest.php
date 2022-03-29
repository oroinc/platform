<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;
use Oro\Bundle\AttachmentBundle\Layout\DataProvider\FileApplicationsDataProvider;
use Oro\Bundle\AttachmentBundle\Provider\FileApplicationsProvider;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;

class FileApplicationsDataProviderTest extends \PHPUnit\Framework\TestCase
{
    private FileApplicationsProvider|\PHPUnit\Framework\MockObject\MockObject $fileApplicationsProvider;

    private CurrentApplicationProviderInterface|\PHPUnit\Framework\MockObject\MockObject $currentApplicationProvider;

    private ConfigProvider|\PHPUnit\Framework\MockObject\MockObject $configProvider;

    private FileApplicationsDataProvider $dataProvider;

    protected function setUp(): void
    {
        $this->fileApplicationsProvider = $this->createMock(FileApplicationsProvider::class);
        $this->currentApplicationProvider = $this->createMock(CurrentApplicationProviderInterface::class);
        $this->configProvider = $this->createMock(ConfigProvider::class);

        $this->dataProvider = new FileApplicationsDataProvider(
            $this->fileApplicationsProvider,
            $this->currentApplicationProvider,
            $this->configProvider
        );
    }

    /**
     * @dataProvider getIsValidForFieldDataProvider
     */
    public function testIsValidForField(
        array $scopeOptions,
        bool $isApplicationsValid,
        bool $expectedResult
    ): void {
        $applications = ['default'];
        $className = Item::class;
        $fieldName = 'testField';

        $config = new Config(new FieldConfigId('attachment', $className, $fieldName), $scopeOptions);

        $this->configProvider
            ->expects(self::once())
            ->method('getConfig')
            ->with($className, $fieldName)
            ->willReturn($config);

        $this->fileApplicationsProvider
            ->expects(self::any())
            ->method('getFileApplicationsForField')
            ->with($className, $fieldName)
            ->willReturn($applications);

        $this->currentApplicationProvider
            ->expects(self::any())
            ->method('isApplicationsValid')
            ->with($applications)
            ->willReturn($isApplicationsValid);

        self::assertEquals($expectedResult, $this->dataProvider->isValidForField($className, $fieldName));
    }

    public function getIsValidForFieldDataProvider(): array
    {
        return [
            'not acl protected when is_stored_externally is true' => [
                'scopeOptions' => [
                    'is_stored_externally' => true,
                ],
                'isApplicationsValid' => false,
                'expectedResult' => true,
            ],

            'not acl protected when is_stored_externally is true and acl_protected is true' => [
                'scopeOptions' => [
                    'is_stored_externally' => true,
                    'acl_protected' => false,
                ],
                'isApplicationsValid' => false,
                'expectedResult' => true,
            ],
            'not acl protected when acl_protected is false' => [
                'scopeOptions' => [
                    'acl_protected' => false,
                ],
                'isApplicationsValid' => false,
                'expectedResult' => true,
            ],
            'acl protected and application not valid' => [
                'scopeOptions' => [
                    'acl_protected' => true,
                ],
                'isApplicationsValid' => false,
                'expectedResult' => false,
            ],
            'acl protected and application valid' => [
                'scopeOptions' => [
                    'acl_protected' => true,
                ],
                'isApplicationsValid' => true,
                'expectedResult' => true,
            ],
        ];
    }
}
