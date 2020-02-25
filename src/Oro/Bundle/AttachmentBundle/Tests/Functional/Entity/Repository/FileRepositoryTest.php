<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\Repository\FileRepository;
use Oro\Bundle\AttachmentBundle\Tests\Functional\DataFixtures\LoadFileData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class FileRepositoryTest extends WebTestCase
{
    /**
     * @var FileRepository
     */
    protected $repository;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([LoadFileData::class]);

        $this->repository = $this->getContainer()->get('doctrine')->getRepository(File::class);
    }

    public function testDeleteByFileIds()
    {
        /** @var File $file1 */
        $file1 = $this->getReference('file-1');
        /** @var File $file2 */
        $file2 = $this->getReference('file-2');
        /** @var File $file3 */
        $file3 = $this->getReference('file-3');
        /** @var File $file4 */
        $file4 = $this->getReference('file-4');

        $this->repository->deleteByFileIds([
            $file1->getId(),
            $file3->getId(),
        ]);

        $filesAfterDelete = $this->repository->findAll();

        $this->assertContains($file2, $filesAfterDelete);
        $this->assertContains($file4, $filesAfterDelete);
        $this->assertNotContains($file1, $filesAfterDelete);
        $this->assertNotContains($file3, $filesAfterDelete);
    }
}
