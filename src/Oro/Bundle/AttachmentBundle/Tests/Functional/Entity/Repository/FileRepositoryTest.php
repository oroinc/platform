<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\Repository\FileRepository;
use Oro\Bundle\AttachmentBundle\Tests\Functional\DataFixtures\LoadFileData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class FileRepositoryTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadFileData::class]);
    }

    private function getRepository(): FileRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(File::class);
    }

    private function getFile(string $reference): File
    {
        return $this->getReference($reference);
    }

    private function getFileIds(array $files): array
    {
        $ids = array_map(function (File $file) {
            return $file->getId();
        }, $files);
        sort($ids);

        return $ids;
    }

    public function testFindForEntity(): void
    {
        $foundFiles = $this->getRepository()->findForEntityField(\stdClass::class, 1);
        $expectedFiles = [$this->getFile(LoadFileData::FILE_1), $this->getFile(LoadFileData::FILE_3)];
        $this->assertSame(
            $this->getFileIds($expectedFiles),
            $this->getFileIds($foundFiles)
        );
    }

    public function testFindForEntityField(): void
    {
        $foundFiles = $this->getRepository()->findForEntityField(\stdClass::class, 1, 'fieldA');
        $expectedFiles = [$this->getFile(LoadFileData::FILE_1)];
        $this->assertSame(
            $this->getFileIds($expectedFiles),
            $this->getFileIds($foundFiles)
        );
    }
}
