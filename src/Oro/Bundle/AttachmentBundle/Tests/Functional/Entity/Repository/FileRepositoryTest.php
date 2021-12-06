<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\Repository\FileRepository;
use Oro\Bundle\AttachmentBundle\Tests\Functional\DataFixtures\LoadFileData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class FileRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadFileData::class]);
    }

    private function getRepository(): FileRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(File::class);
    }

    public function testFindForEntityField(): void
    {
        $this->assertEquals(
            [
                $this->getReference(LoadFileData::FILE_1)
            ],
            $this->getRepository()->findForEntityField(\stdClass::class, 1, 'fieldA')
        );
    }
}
