<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;
use Oro\Bundle\AttachmentBundle\Layout\DataProvider\FileApplicationsDataProvider;
use Oro\Bundle\AttachmentBundle\Provider\FileApplicationsProvider;
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
}
