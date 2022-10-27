<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\AttachmentEntityConfigProviderInterface;
use Oro\Bundle\AttachmentBundle\Provider\FileApplicationsProvider;
use Oro\Bundle\EntityConfigBundle\Config\Config;

class FileApplicationsProviderTest extends \PHPUnit\Framework\TestCase
{
    private const PARENT_ENTITY_CLASS = \stdClass::class;
    private const PARENT_ENTITY_ID = 1;
    private const PARENT_ENTITY_FIELD_NAME = 'sampleField';

    /** @var AttachmentEntityConfigProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $attachmentEntityConfigProvider;

    /** @var FileApplicationsProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->attachmentEntityConfigProvider = $this->createMock(AttachmentEntityConfigProviderInterface::class);
        $this->provider = new FileApplicationsProvider($this->attachmentEntityConfigProvider);
    }

    public function testGetFileApplicationsWhenNoParentData(): void
    {
        $file = new File();

        self::assertEquals(
            [CurrentApplicationProviderInterface::DEFAULT_APPLICATION],
            $this->provider->getFileApplications($file)
        );
    }

    public function testGetFileApplications(): void
    {
        $this->attachmentEntityConfigProvider
            ->expects(self::once())
            ->method('getFieldConfig')
            ->with(self::PARENT_ENTITY_CLASS, self::PARENT_ENTITY_FIELD_NAME)
            ->willReturn($config = $this->createMock(Config::class));

        $config
            ->expects(self::once())
            ->method('get')
            ->with('file_applications', false, [CurrentApplicationProviderInterface::DEFAULT_APPLICATION])
            ->willReturn($fileApplications = ['sample_app1', 'sample_app2']);

        self::assertEquals(
            $fileApplications,
            $this->provider->getFileApplications($this->getFile())
        );
    }

    public function testGetFileApplicationsForField(): void
    {
        $applications = ['sample_app1', 'sample_app2'];

        $config = $this->createMock(Config::class);
        $config->expects($this->once())
            ->method('get')
            ->with('file_applications', false, [CurrentApplicationProviderInterface::DEFAULT_APPLICATION])
            ->willReturn($applications);

        $this->attachmentEntityConfigProvider->expects($this->once())
            ->method('getFieldConfig')
            ->with(self::PARENT_ENTITY_CLASS, self::PARENT_ENTITY_FIELD_NAME)
            ->willReturn($config);

        $this->assertEquals(
            $applications,
            $this->provider->getFileApplicationsForField(self::PARENT_ENTITY_CLASS, self::PARENT_ENTITY_FIELD_NAME)
        );
    }

    public function testGetFileApplicationsForFieldWithoutConfig(): void
    {
        $config = $this->createMock(Config::class);
        $config->expects($this->never())
            ->method('get');

        $this->attachmentEntityConfigProvider->expects($this->once())
            ->method('getFieldConfig')
            ->with(self::PARENT_ENTITY_CLASS, self::PARENT_ENTITY_FIELD_NAME)
            ->willReturn(null);

        $this->assertEquals(
            [CurrentApplicationProviderInterface::DEFAULT_APPLICATION],
            $this->provider->getFileApplicationsForField(self::PARENT_ENTITY_CLASS, self::PARENT_ENTITY_FIELD_NAME)
        );
    }

    private function getFile(): File
    {
        $file = new File();
        $file->setParentEntityClass(self::PARENT_ENTITY_CLASS);
        $file->setParentEntityId(self::PARENT_ENTITY_ID);
        $file->setParentEntityFieldName(self::PARENT_ENTITY_FIELD_NAME);

        return $file;
    }

    public function testGetFileApplicationsWhenNoFieldConfig(): void
    {
        $this->attachmentEntityConfigProvider
            ->expects(self::once())
            ->method('getFieldConfig')
            ->with(self::PARENT_ENTITY_CLASS, self::PARENT_ENTITY_FIELD_NAME)
            ->willReturn(null);

        self::assertEquals(
            [CurrentApplicationProviderInterface::DEFAULT_APPLICATION],
            $this->provider->getFileApplications($this->getFile())
        );
    }
}
