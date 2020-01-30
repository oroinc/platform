<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\Provider\AttachmentEntityConfigProviderInterface;
use Oro\Bundle\AttachmentBundle\Provider\MultipleFileConstraintsProvider;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class MultipleFileConstraintsProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var AttachmentEntityConfigProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $attachmentEntityConfigProvider;

    /** @var MultipleFileConstraintsProvider */
    private $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->attachmentEntityConfigProvider = $this->createMock(AttachmentEntityConfigProviderInterface::class);

        $this->provider = new MultipleFileConstraintsProvider(
            $this->attachmentEntityConfigProvider
        );
    }

    public function testGetMaxNumberOfFiles()
    {
        self::assertEquals(0, $this->provider->getMaxNumberOfFiles());
    }

    public function testGetMaxNumberOfFilesForEntity()
    {
        $this->attachmentEntityConfigProvider
            ->method('getEntityConfig')
            ->with($entityClass = \stdClass::class)
            ->willReturn($entityFieldConfig = $this->createMock(ConfigInterface::class));

        $entityFieldConfig
            ->expects(self::once())
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
        $this->attachmentEntityConfigProvider
            ->method('getFieldConfig')
            ->with($entityClass = \stdClass::class, $fieldName = 'sampleField')
            ->willReturn($entityFieldConfig = $this->createMock(ConfigInterface::class));

        $entityFieldConfig
            ->expects(self::once())
            ->method('get')
            ->with('max_number_of_files')
            ->willReturn(10);

        self::assertEquals(
            10,
            $this->provider->getMaxNumberOfFilesForEntityField($entityClass, $fieldName)
        );
    }
}
