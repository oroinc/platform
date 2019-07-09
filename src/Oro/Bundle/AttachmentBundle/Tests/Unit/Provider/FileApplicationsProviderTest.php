<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileApplicationsProvider;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

class FileApplicationsProviderTest extends \PHPUnit\Framework\TestCase
{
    private const PARENT_ENTITY_CLASS = \stdClass::class;
    private const PARENT_ENTITY_ID = 1;
    private const PARENT_ENTITY_FIELD_NAME = 'sampleField';

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var FileApplicationsProvider */
    private $provider;

    protected function setUp()
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->provider = new FileApplicationsProvider($this->configManager);
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
        $this->configManager
            ->expects(self::once())
            ->method('getFieldConfig')
            ->with('attachment', self::PARENT_ENTITY_CLASS, self::PARENT_ENTITY_FIELD_NAME)
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

    /**
     * @return File
     */
    private function getFile(): File
    {
        $file = new File();
        $file->setParentEntityClass(self::PARENT_ENTITY_CLASS);
        $file->setParentEntityId(self::PARENT_ENTITY_ID);
        $file->setParentEntityFieldName(self::PARENT_ENTITY_FIELD_NAME);

        return $file;
    }
}
