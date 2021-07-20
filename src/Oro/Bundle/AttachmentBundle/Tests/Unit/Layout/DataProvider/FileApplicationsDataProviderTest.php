<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;
use Oro\Bundle\AttachmentBundle\Layout\DataProvider\FileApplicationsDataProvider;
use Oro\Bundle\AttachmentBundle\Provider\FileApplicationsProvider;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;

class FileApplicationsDataProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var FileApplicationsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $fileApplicationsProvider;

    /** @var CurrentApplicationProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $currentApplicationProvider;

    /** @var FileApplicationsDataProvider */
    private $dataProvider;

    protected function setUp(): void
    {
        $this->fileApplicationsProvider = $this->createMock(FileApplicationsProvider::class);
        $this->currentApplicationProvider = $this->createMock(CurrentApplicationProviderInterface::class);

        $this->dataProvider = new FileApplicationsDataProvider(
            $this->fileApplicationsProvider,
            $this->currentApplicationProvider
        );
    }

    public function testIsValidForField(): void
    {
        $applications = ['default'];

        $this->fileApplicationsProvider->expects($this->once())
            ->method('getFileApplicationsForField')
            ->with(Item::class, 'testField')
            ->willReturn($applications);

        $this->currentApplicationProvider->expects($this->once())
            ->method('isApplicationsValid')
            ->with($applications)
            ->willReturn(true);

        $this->assertTrue($this->dataProvider->isValidForField(Item::class, 'testField'));
    }

    /**
     * @dataProvider getIsValidForFieldWithConfigProviderDataProvider
     */
    public function testIsValidForFieldWithConfigProvider(
        bool $isAclProtected,
        bool $isApplicationsValid,
        bool $expectedResult
    ): void {
        $applications = ['default'];
        $className = Item::class;
        $fieldName = 'testField';

        $config = $this->createMock(ConfigInterface::class);
        $config->expects($this->once())
            ->method('is')
            ->with('acl_protected')
            ->willReturn($isAclProtected);

        $configProvider = $this->createMock(ConfigProvider::class);
        $configProvider->expects($this->once())
            ->method('getConfig')
            ->with($className, $fieldName)
            ->willReturn($config);

        $this->dataProvider->setConfigProvider($configProvider);

        $this->fileApplicationsProvider->expects($this->exactly((int)$isAclProtected))
            ->method('getFileApplicationsForField')
            ->with($className, $fieldName)
            ->willReturn($applications);

        $this->currentApplicationProvider->expects($this->exactly((int)$isAclProtected))
            ->method('isApplicationsValid')
            ->with($applications)
            ->willReturn($isApplicationsValid);

        $this->assertEquals($expectedResult, $this->dataProvider->isValidForField($className, $fieldName));
    }

    public function getIsValidForFieldWithConfigProviderDataProvider(): array
    {
        return [
            'not acl protected' => [
                'isAclProtected' => false,
                'isApplicationsValid' => false,
                'expectedResult' => true,
            ],
            'acl protected and application not valid' => [
                'isAclProtected' => true,
                'isApplicationsValid' => false,
                'expectedResult' => false,
            ],
            'acl protected and application valid' => [
                'isAclProtected' => true,
                'isApplicationsValid' => true,
                'expectedResult' => true,
            ],
        ];
    }
}
