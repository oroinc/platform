<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;

class FileTest extends RestJsonApiTestCase
{
    private static $blankFileContent = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAAA'
    . '1BMVEUAAACnej3aAAAAAXRSTlMAQObYZgAAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII=';

    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures([LoadUser::class]);
    }

    /**
     * @return int The file entity id
     */
    public function testPost()
    {
        $response = $this->post(
            ['entity' => 'files'],
            [
                'data' => [
                    'type'       => 'files',
                    'attributes' => [
                        'mimeType'         => 'image/png',
                        'originalFilename' => 'blank.png',
                        'fileSize'         => 95,
                        'content'          => self::$blankFileContent
                    ]
                ]
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'files',
                    'attributes'    => [
                        'mimeType'         => 'image/png',
                        'originalFilename' => 'blank.png',
                        'fileSize'         => 95,
                        'content'          => self::$blankFileContent
                    ],
                    'relationships' => [
                        'owner' => [
                            'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                        ]
                    ]
                ]
            ],
            $response
        );

        $fileId = $this->getResourceId($response);
        self::assertNotEmpty($fileId);

        $fileId = (int)$fileId;

        // test that the entity was created
        $entity = $this->getEntityManager()->find(File::class, $fileId);
        self::assertNotNull($entity);

        // clear entity manager to not affect dependent tests
        $this->getEntityManager()->clear();

        return $fileId;
    }

    /**
     * @depends testPost
     *
     * @param int $fileId
     */
    public function testGet($fileId)
    {
        $response = $this->get(
            ['entity' => 'files', 'id' => (string)$fileId]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'files',
                    'id'            => (string)$fileId,
                    'attributes'    => [
                        'mimeType'         => 'image/png',
                        'originalFilename' => 'blank.png',
                        'fileSize'         => 95,
                        'content'          => self::$blankFileContent
                    ],
                    'relationships' => [
                        'owner' => [
                            'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                        ]
                    ]
                ]
            ],
            $response
        );
    }
}
