<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\Provider\AttachmentEntityConfigProviderInterface;
use Oro\Bundle\AttachmentBundle\Provider\MultipleFileConstraintsProvider;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class MultipleFileConstraintsProviderTest extends TestCase
{
    private AttachmentEntityConfigProviderInterface&MockObject $attachmentEntityConfigProvider;
    private MultipleFileConstraintsProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->attachmentEntityConfigProvider = $this->createMock(AttachmentEntityConfigProviderInterface::class);

        $this->provider = new MultipleFileConstraintsProvider(
            $this->attachmentEntityConfigProvider
        );
    }

    public function testGetMaxNumberOfFiles(): void
    {
        self::assertEquals(0, $this->provider->getMaxNumberOfFiles());
    }

    public function testGetMaxNumberOfFilesForEntity(): void
    {
        $entityClass = \stdClass::class;
        $entityFieldConfig = $this->createMock(ConfigInterface::class);
        $this->attachmentEntityConfigProvider->expects(self::once())
            ->method('getEntityConfig')
            ->with($entityClass)
            ->willReturn($entityFieldConfig);
        $entityFieldConfig->expects(self::once())
            ->method('get')
            ->with('max_number_of_files')
            ->willReturn(10);

        self::assertEquals(
            10,
            $this->provider->getMaxNumberOfFilesForEntity($entityClass)
        );
    }

    public function testGetMaxSizeForEntityField(): void
    {
        $entityClass = \stdClass::class;
        $fieldName = 'sampleField';
        $entityFieldConfig = $this->createMock(ConfigInterface::class);
        $this->attachmentEntityConfigProvider->expects(self::once())
            ->method('getFieldConfig')
            ->with($entityClass, $fieldName)
            ->willReturn($entityFieldConfig);
        $entityFieldConfig->expects(self::once())
            ->method('get')
            ->with('max_number_of_files')
            ->willReturn(10);

        self::assertEquals(
            10,
            $this->provider->getMaxNumberOfFilesForEntityField($entityClass, $fieldName)
        );
    }
}
