<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\Repository\FileRepository;
use Oro\Bundle\AttachmentBundle\Tests\Functional\DataFixtures\LoadFileData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class FileRepositoryTest extends WebTestCase
{
    /** @var FileRepository */
    private $repository;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([LoadFileData::class]);

        $container = $this->getContainer();
        $this->repository = $container->get('doctrine')->getRepository(File::class);
    }

    public function testFindAllForEntityByOneUuid(): void
    {
        /** @var File $fileA */
        $fileA = $this->getReference(LoadFileData::FILE_1);

        /** @var File $fileC */
        $fileC = $this->getReference(LoadFileData::FILE_3);

        $this->assertEquals(
            [
                $fileA->getUuid() => $fileA,
                $fileC->getUuid() => $fileC,
            ],
            $this->repository->findAllForEntityByOneUuid($fileA->getUuid())
        );
    }

    public function testFindForEntityField(): void
    {
        $this->assertEquals(
            [
                $this->getReference(LoadFileData::FILE_1)
            ],
            $this->repository->findForEntityField(\stdClass::class, 1, 'fieldA')
        );
    }
}
