<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\AttachmentBundle\Tests\Functional\Environment\Entity\TestAttachmentOwner;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;

/**
 * @dbIsolationPerTest
 */
class AttachmentCustomFieldTypeTest extends RestJsonApiTestCase
{
    private $blankFileContent = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAAA'
        . '1BMVEUAAACnej3aAAAAAXRSTlMAQObYZgAAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII=';

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadUser::class]);
    }

    public function testCreate()
    {
        $entityType = $this->getEntityType(TestAttachmentOwner::class);

        $data = [
            'data' => [
                'id' => 'owner_id',
                'type' => $entityType,
                'attributes' => [
                    'name' => 'D4747501U710N',
                ],
                'relationships' => [
                    'test_file' => [
                        'data' => [
                            'type' => 'files',
                            'id' => 'image-3'
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type' => 'files',
                    'id' => 'image-3',
                    'attributes' => [
                        'mimeType'         => 'image/png',
                        'originalFilename' => 'blank.png',
                        'fileSize'         => 95,
                        'content'          => $this->blankFileContent,
                        'parentFieldName'  => 'test_file'
                    ]
                ]
            ]
        ];

        $this->post(
            ['entity' => $entityType],
            $data,
            ['ORO_ENV' => 'test']
        );
    }
}
