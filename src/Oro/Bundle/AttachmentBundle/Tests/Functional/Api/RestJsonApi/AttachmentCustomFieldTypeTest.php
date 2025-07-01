<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Tests\Functional\Api\DataFixtures\LoadAttachmentOwnerData;
use Oro\Bundle\AttachmentBundle\Tests\Functional\Environment\Entity\TestAttachmentOwner;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class AttachmentCustomFieldTypeTest extends RestJsonApiTestCase
{
    private const string BLANK_FILE_CONTENT = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAAA'
        . '1BMVEUAAACnej3aAAAAAXRSTlMAQObYZgAAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII=';

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadUser::class, LoadAttachmentOwnerData::class]);
    }

    public function testGet(): void
    {
        $entityType = $this->getEntityType(TestAttachmentOwner::class);

        $response = $this->get(
            ['entity' => $entityType, 'id' => '<toString(@entity1->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $entityType,
                    'id' => '<toString(@entity1->id)>',
                    'attributes' => [
                        'name' => 'Entity 1'
                    ],
                    'relationships' => [
                        'test_file' => [
                            'data' => ['type' => 'files', 'id' => '<toString(@file_1->id)>']
                        ],
                        'test_image' => [
                            'data' => ['type' => 'files', 'id' => '<toString(@image_1->id)>']
                        ],
                        'test_multi_files' => [
                            'data' => [
                                ['type' => 'files', 'id' => '<toString(@file_3->id)>', 'meta' => ['sortOrder' => 1]],
                                ['type' => 'files', 'id' => '<toString(@file_2->id)>', 'meta' => ['sortOrder' => 2]]
                            ]
                        ],
                        'test_multi_images' => [
                            'data' => [
                                ['type' => 'files', 'id' => '<toString(@image_3->id)>', 'meta' => ['sortOrder' => 1]],
                                ['type' => 'files', 'id' => '<toString(@image_2->id)>', 'meta' => ['sortOrder' => 2]]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGetWithIncludeFilter(): void
    {
        $entityType = $this->getEntityType(TestAttachmentOwner::class);

        $response = $this->get(
            ['entity' => $entityType, 'id' => '<toString(@entity1->id)>'],
            [
                'include' => 'test_file,test_image,test_multi_files,test_multi_images',
                'fields[files]' => 'mimeType,originalFilename,parentFieldName,owner,parent'
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $entityType,
                    'id' => '<toString(@entity1->id)>',
                    'attributes' => [
                        'name' => 'Entity 1'
                    ],
                    'relationships' => [
                        'test_file' => [
                            'data' => ['type' => 'files', 'id' => '<toString(@file_1->id)>']
                        ],
                        'test_image' => [
                            'data' => ['type' => 'files', 'id' => '<toString(@image_1->id)>']
                        ],
                        'test_multi_files' => [
                            'data' => [
                                ['type' => 'files', 'id' => '<toString(@file_3->id)>', 'meta' => ['sortOrder' => 1]],
                                ['type' => 'files', 'id' => '<toString(@file_2->id)>', 'meta' => ['sortOrder' => 2]]
                            ]
                        ],
                        'test_multi_images' => [
                            'data' => [
                                ['type' => 'files', 'id' => '<toString(@image_3->id)>', 'meta' => ['sortOrder' => 1]],
                                ['type' => 'files', 'id' => '<toString(@image_2->id)>', 'meta' => ['sortOrder' => 2]]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'files',
                        'id' => '<toString(@file_1->id)>',
                        'attributes' => [
                            'mimeType' => 'text/plain',
                            'originalFilename' => 'file_1.txt',
                            'parentFieldName' => 'test_file'
                        ],
                        'relationships' => [
                            'owner' => [
                                'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                            ],
                            'parent' => [
                                'data' => ['type' => $entityType, 'id' => '<toString(@entity1->id)>']
                            ]
                        ]
                    ],
                    [
                        'type' => 'files',
                        'id' => '<toString(@image_1->id)>',
                        'attributes' => [
                            'mimeType' => 'image/jpeg',
                            'originalFilename' => 'image_1.jpg',
                            'parentFieldName' => 'test_image'
                        ],
                        'relationships' => [
                            'owner' => [
                                'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                            ],
                            'parent' => [
                                'data' => ['type' => $entityType, 'id' => '<toString(@entity1->id)>']
                            ]
                        ]
                    ],
                    [
                        'type' => 'files',
                        'id' => '<toString(@file_3->id)>',
                        'attributes' => [
                            'mimeType' => 'text/plain',
                            'originalFilename' => 'file_3.txt',
                            'parentFieldName' => 'test_multi_files'
                        ],
                        'relationships' => [
                            'owner' => [
                                'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                            ],
                            'parent' => [
                                'data' => ['type' => $entityType, 'id' => '<toString(@entity1->id)>']
                            ]
                        ]
                    ],
                    [
                        'type' => 'files',
                        'id' => '<toString(@file_2->id)>',
                        'attributes' => [
                            'mimeType' => 'text/plain',
                            'originalFilename' => 'file_2.txt',
                            'parentFieldName' => 'test_multi_files'
                        ],
                        'relationships' => [
                            'owner' => [
                                'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                            ],
                            'parent' => [
                                'data' => ['type' => $entityType, 'id' => '<toString(@entity1->id)>']
                            ]
                        ]
                    ],
                    [
                        'type' => 'files',
                        'id' => '<toString(@image_3->id)>',
                        'attributes' => [
                            'mimeType' => 'image/jpeg',
                            'originalFilename' => 'image_3.jpg',
                            'parentFieldName' => 'test_multi_images'
                        ],
                        'relationships' => [
                            'owner' => [
                                'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                            ],
                            'parent' => [
                                'data' => ['type' => $entityType, 'id' => '<toString(@entity1->id)>']
                            ]
                        ]
                    ],
                    [
                        'type' => 'files',
                        'id' => '<toString(@image_2->id)>',
                        'attributes' => [
                            'mimeType' => 'image/jpeg',
                            'originalFilename' => 'image_2.jpg',
                            'parentFieldName' => 'test_multi_images'
                        ],
                        'relationships' => [
                            'owner' => [
                                'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                            ],
                            'parent' => [
                                'data' => ['type' => $entityType, 'id' => '<toString(@entity1->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testCreateWithFile(): void
    {
        $entityType = $this->getEntityType(TestAttachmentOwner::class);

        $response = $this->post(
            ['entity' => $entityType],
            [
                'data' => [
                    'type' => $entityType,
                    'attributes' => [
                        'name' => 'New Entity',
                    ],
                    'relationships' => [
                        'test_file' => [
                            'data' => ['type' => 'files', 'id' => 'new_file']
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'files',
                        'id' => 'new_file',
                        'attributes' => [
                            'mimeType' => 'image/png',
                            'originalFilename' => 'blank.png',
                            'content' => self::BLANK_FILE_CONTENT
                        ]
                    ]
                ]
            ]
        );

        $expectedData = [
            'data' => [
                'type' => $entityType,
                'id' => 'new',
                'attributes' => [
                    'name' => 'New Entity',
                ],
                'relationships' => [
                    'test_file' => [
                        'data' => ['type' => 'files', 'id' => 'new']
                    ]
                ]
            ],
            'included' => [
                [
                    'type' => 'files',
                    'id' => 'new',
                    'attributes' => [
                        'mimeType' => 'image/png',
                        'originalFilename' => 'blank.png',
                        'fileSize' => 95,
                        'parentFieldName' => 'test_file',
                        'externalUrl' => null,
                        'content' => self::BLANK_FILE_CONTENT
                    ],
                    'relationships' => [
                        'owner' => [
                            'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                        ],
                        'parent' => [
                            'data' => ['type' => $entityType, 'id' => 'new']
                        ]
                    ]
                ]
            ]
        ];
        $expectedData = $this->updateResponseContent($expectedData, $response);
        $this->assertResponseContains($expectedData, $response);
    }

    public function testCreateWithImage(): void
    {
        $entityType = $this->getEntityType(TestAttachmentOwner::class);

        $response = $this->post(
            ['entity' => $entityType],
            [
                'data' => [
                    'type' => $entityType,
                    'attributes' => [
                        'name' => 'New Entity',
                    ],
                    'relationships' => [
                        'test_image' => [
                            'data' => ['type' => 'files', 'id' => 'new_image']
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'files',
                        'id' => 'new_image',
                        'attributes' => [
                            'mimeType' => 'image/png',
                            'originalFilename' => 'blank.png',
                            'content' => self::BLANK_FILE_CONTENT
                        ]
                    ]
                ]
            ]
        );

        $expectedData = [
            'data' => [
                'type' => $entityType,
                'id' => 'new',
                'attributes' => [
                    'name' => 'New Entity',
                ],
                'relationships' => [
                    'test_image' => [
                        'data' => ['type' => 'files', 'id' => 'new']
                    ]
                ]
            ],
            'included' => [
                [
                    'type' => 'files',
                    'id' => 'new',
                    'attributes' => [
                        'mimeType' => 'image/png',
                        'originalFilename' => 'blank.png',
                        'fileSize' => 95,
                        'parentFieldName' => 'test_image',
                        'externalUrl' => null,
                        'content' => self::BLANK_FILE_CONTENT
                    ],
                    'relationships' => [
                        'owner' => [
                            'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                        ],
                        'parent' => [
                            'data' => ['type' => $entityType, 'id' => 'new']
                        ]
                    ]
                ]
            ]
        ];
        $expectedData = $this->updateResponseContent($expectedData, $response);
        $this->assertResponseContains($expectedData, $response);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCreateWithMultiFiles(): void
    {
        $entityType = $this->getEntityType(TestAttachmentOwner::class);

        $response = $this->post(
            ['entity' => $entityType],
            [
                'data' => [
                    'type' => $entityType,
                    'attributes' => [
                        'name' => 'New Entity',
                    ],
                    'relationships' => [
                        'test_multi_files' => [
                            'data' => [
                                ['type' => 'files', 'id' => 'new_file_2', 'meta' => ['sortOrder' => 2]],
                                ['type' => 'files', 'id' => 'new_file_1', 'meta' => ['sortOrder' => 1]]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'files',
                        'id' => 'new_file_1',
                        'attributes' => [
                            'mimeType' => 'image/png',
                            'originalFilename' => 'blank.png',
                            'content' => self::BLANK_FILE_CONTENT
                        ]
                    ],
                    [
                        'type' => 'files',
                        'id' => 'new_file_2',
                        'attributes' => [
                            'mimeType' => 'image/png',
                            'originalFilename' => 'blank.png',
                            'content' => self::BLANK_FILE_CONTENT
                        ]
                    ]
                ]
            ]
        );

        $expectedData = [
            'data' => [
                'type' => $entityType,
                'id' => 'new',
                'attributes' => [
                    'name' => 'New Entity',
                ],
                'relationships' => [
                    'test_multi_files' => [
                        'data' => [
                            ['type' => 'files', 'id' => 'new', 'meta' => ['sortOrder' => 1]],
                            ['type' => 'files', 'id' => 'new', 'meta' => ['sortOrder' => 2]]
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type' => 'files',
                    'id' => 'new',
                    'attributes' => [
                        'mimeType' => 'image/png',
                        'originalFilename' => 'blank.png',
                        'fileSize' => 95,
                        'parentFieldName' => 'test_multi_files',
                        'externalUrl' => null,
                        'content' => self::BLANK_FILE_CONTENT
                    ],
                    'relationships' => [
                        'owner' => [
                            'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                        ],
                        'parent' => [
                            'data' => ['type' => $entityType, 'id' => 'new']
                        ]
                    ]
                ],
                [
                    'type' => 'files',
                    'id' => 'new',
                    'attributes' => [
                        'mimeType' => 'image/png',
                        'originalFilename' => 'blank.png',
                        'fileSize' => 95,
                        'parentFieldName' => 'test_multi_files',
                        'externalUrl' => null,
                        'content' => self::BLANK_FILE_CONTENT
                    ],
                    'relationships' => [
                        'owner' => [
                            'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                        ],
                        'parent' => [
                            'data' => ['type' => $entityType, 'id' => 'new']
                        ]
                    ]
                ]
            ]
        ];
        $expectedData = $this->updateResponseContent($expectedData, $response);
        $this->assertResponseContains($expectedData, $response);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCreateWithMultiImages(): void
    {
        $entityType = $this->getEntityType(TestAttachmentOwner::class);

        $response = $this->post(
            ['entity' => $entityType],
            [
                'data' => [
                    'type' => $entityType,
                    'attributes' => [
                        'name' => 'New Entity',
                    ],
                    'relationships' => [
                        'test_multi_images' => [
                            'data' => [
                                ['type' => 'files', 'id' => 'new_file_2', 'meta' => ['sortOrder' => 2]],
                                ['type' => 'files', 'id' => 'new_file_1', 'meta' => ['sortOrder' => 1]]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'files',
                        'id' => 'new_file_1',
                        'attributes' => [
                            'mimeType' => 'image/png',
                            'originalFilename' => 'blank.png',
                            'content' => self::BLANK_FILE_CONTENT
                        ]
                    ],
                    [
                        'type' => 'files',
                        'id' => 'new_file_2',
                        'attributes' => [
                            'mimeType' => 'image/png',
                            'originalFilename' => 'blank.png',
                            'content' => self::BLANK_FILE_CONTENT
                        ]
                    ]
                ]
            ]
        );

        $expectedData = [
            'data' => [
                'type' => $entityType,
                'id' => 'new',
                'attributes' => [
                    'name' => 'New Entity',
                ],
                'relationships' => [
                    'test_multi_images' => [
                        'data' => [
                            ['type' => 'files', 'id' => 'new', 'meta' => ['sortOrder' => 1]],
                            ['type' => 'files', 'id' => 'new', 'meta' => ['sortOrder' => 2]]
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type' => 'files',
                    'id' => 'new',
                    'attributes' => [
                        'mimeType' => 'image/png',
                        'originalFilename' => 'blank.png',
                        'fileSize' => 95,
                        'parentFieldName' => 'test_multi_images',
                        'externalUrl' => null,
                        'content' => self::BLANK_FILE_CONTENT
                    ],
                    'relationships' => [
                        'owner' => [
                            'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                        ],
                        'parent' => [
                            'data' => ['type' => $entityType, 'id' => 'new']
                        ]
                    ]
                ],
                [
                    'type' => 'files',
                    'id' => 'new',
                    'attributes' => [
                        'mimeType' => 'image/png',
                        'originalFilename' => 'blank.png',
                        'fileSize' => 95,
                        'parentFieldName' => 'test_multi_images',
                        'externalUrl' => null,
                        'content' => self::BLANK_FILE_CONTENT
                    ],
                    'relationships' => [
                        'owner' => [
                            'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                        ],
                        'parent' => [
                            'data' => ['type' => $entityType, 'id' => 'new']
                        ]
                    ]
                ]
            ]
        ];
        $expectedData = $this->updateResponseContent($expectedData, $response);
        $this->assertResponseContains($expectedData, $response);
    }

    public function testTryToCreateWithFileWhenFileAlreadyExistsAndBelongsToAnotherEntity(): void
    {
        $entityType = $this->getEntityType(TestAttachmentOwner::class);

        $response = $this->post(
            ['entity' => $entityType],
            [
                'data' => [
                    'type' => $entityType,
                    'attributes' => [
                        'name' => 'New Entity',
                    ],
                    'relationships' => [
                        'test_file' => [
                            'data' => ['type' => 'files', 'id' => '<toString(@file_2->id)>']
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'form constraint',
                'detail' => 'This file is already used in another entity.',
                'source' => ['pointer' => '/data/relationships/test_file/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithImageWhenImageAlreadyExistsAndBelongsToAnotherEntity(): void
    {
        $entityType = $this->getEntityType(TestAttachmentOwner::class);

        $response = $this->post(
            ['entity' => $entityType],
            [
                'data' => [
                    'type' => $entityType,
                    'attributes' => [
                        'name' => 'New Entity',
                    ],
                    'relationships' => [
                        'test_image' => [
                            'data' => ['type' => 'files', 'id' => '<toString(@image_2->id)>']
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'form constraint',
                'detail' => 'This file is already used in another entity.',
                'source' => ['pointer' => '/data/relationships/test_image/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithMultiFilesWhenFileAlreadyExistsAndBelongsToAnotherEntity(): void
    {
        $entityType = $this->getEntityType(TestAttachmentOwner::class);

        $response = $this->post(
            ['entity' => $entityType],
            [
                'data' => [
                    'type' => $entityType,
                    'attributes' => [
                        'name' => 'New Entity',
                    ],
                    'relationships' => [
                        'test_multi_files' => [
                            'data' => [
                                ['type' => 'files', 'id' => '<toString(@file_2->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'form constraint',
                'detail' => 'This file is already used in another entity.',
                'source' => ['pointer' => '/data/relationships/test_multi_files/data/0']
            ],
            $response
        );
    }

    public function testTryToCreateWithMultiImagesWhenImageAlreadyExistsAndBelongsToAnotherEntity(): void
    {
        $entityType = $this->getEntityType(TestAttachmentOwner::class);

        $response = $this->post(
            ['entity' => $entityType],
            [
                'data' => [
                    'type' => $entityType,
                    'attributes' => [
                        'name' => 'New Entity',
                    ],
                    'relationships' => [
                        'test_multi_images' => [
                            'data' => [
                                ['type' => 'files', 'id' => '<toString(@image_2->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'form constraint',
                'detail' => 'This file is already used in another entity.',
                'source' => ['pointer' => '/data/relationships/test_multi_images/data/0']
            ],
            $response
        );
    }

    public function testUpdateWithFile(): void
    {
        $entityType = $this->getEntityType(TestAttachmentOwner::class);
        $entityId = $this->getReference('entity1')->id;

        $response = $this->patch(
            ['entity' => $entityType, 'id' => (string)$entityId],
            [
                'data' => [
                    'type' => $entityType,
                    'id' => (string)$entityId,
                    'relationships' => [
                        'test_file' => [
                            'data' => ['type' => 'files', 'id' => 'new_file']
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'files',
                        'id' => 'new_file',
                        'attributes' => [
                            'mimeType' => 'image/png',
                            'originalFilename' => 'blank.png',
                            'content' => self::BLANK_FILE_CONTENT
                        ]
                    ]
                ]
            ]
        );

        $expectedData = [
            'data' => [
                'type' => $entityType,
                'id' => (string)$entityId,
                'relationships' => [
                    'test_file' => [
                        'data' => ['type' => 'files', 'id' => 'new']
                    ]
                ]
            ],
            'included' => [
                [
                    'type' => 'files',
                    'id' => 'new',
                    'attributes' => [
                        'mimeType' => 'image/png',
                        'originalFilename' => 'blank.png',
                        'fileSize' => 95,
                        'parentFieldName' => 'test_file',
                        'externalUrl' => null,
                        'content' => self::BLANK_FILE_CONTENT
                    ],
                    'relationships' => [
                        'owner' => [
                            'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                        ],
                        'parent' => [
                            'data' => ['type' => $entityType, 'id' => 'new']
                        ]
                    ]
                ]
            ]
        ];
        $expectedData = $this->updateResponseContent($expectedData, $response);
        $this->assertResponseContains($expectedData, $response);
    }

    public function testUpdateWithImage(): void
    {
        $entityType = $this->getEntityType(TestAttachmentOwner::class);
        $entityId = $this->getReference('entity1')->id;

        $response = $this->patch(
            ['entity' => $entityType, 'id' => (string)$entityId],
            [
                'data' => [
                    'type' => $entityType,
                    'id' => (string)$entityId,
                    'relationships' => [
                        'test_image' => [
                            'data' => ['type' => 'files', 'id' => 'new_file']
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'files',
                        'id' => 'new_file',
                        'attributes' => [
                            'mimeType' => 'image/png',
                            'originalFilename' => 'blank.png',
                            'content' => self::BLANK_FILE_CONTENT
                        ]
                    ]
                ]
            ]
        );

        $expectedData = [
            'data' => [
                'type' => $entityType,
                'id' => (string)$entityId,
                'relationships' => [
                    'test_image' => [
                        'data' => ['type' => 'files', 'id' => 'new']
                    ]
                ]
            ],
            'included' => [
                [
                    'type' => 'files',
                    'id' => 'new',
                    'attributes' => [
                        'mimeType' => 'image/png',
                        'originalFilename' => 'blank.png',
                        'fileSize' => 95,
                        'parentFieldName' => 'test_image',
                        'externalUrl' => null,
                        'content' => self::BLANK_FILE_CONTENT
                    ],
                    'relationships' => [
                        'owner' => [
                            'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                        ],
                        'parent' => [
                            'data' => ['type' => $entityType, 'id' => 'new']
                        ]
                    ]
                ]
            ]
        ];
        $expectedData = $this->updateResponseContent($expectedData, $response);
        $this->assertResponseContains($expectedData, $response);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testUpdateWithMultiFiles(): void
    {
        $entityType = $this->getEntityType(TestAttachmentOwner::class);
        $entityId = $this->getReference('entity1')->id;

        $response = $this->patch(
            ['entity' => $entityType, 'id' => (string)$entityId],
            [
                'data' => [
                    'type' => $entityType,
                    'id' => (string)$entityId,
                    'relationships' => [
                        'test_multi_files' => [
                            'data' => [
                                ['type' => 'files', 'id' => '<toString(@file_2->id)>', 'meta' => ['sortOrder' => 2]],
                                ['type' => 'files', 'id' => 'new_file', 'meta' => ['sortOrder' => 1]]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'files',
                        'id' => 'new_file',
                        'attributes' => [
                            'mimeType' => 'image/png',
                            'originalFilename' => 'blank.png',
                            'content' => self::BLANK_FILE_CONTENT
                        ]
                    ]
                ]
            ]
        );

        $expectedData = [
            'data' => [
                'type' => $entityType,
                'id' => (string)$entityId,
                'relationships' => [
                    'test_multi_files' => [
                        'data' => [
                            ['type' => 'files', 'id' => 'new', 'meta' => ['sortOrder' => 1]],
                            ['type' => 'files', 'id' => '<toString(@file_2->id)>', 'meta' => ['sortOrder' => 2]]
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type' => 'files',
                    'id' => 'new',
                    'attributes' => [
                        'mimeType' => 'image/png',
                        'originalFilename' => 'blank.png',
                        'fileSize' => 95,
                        'parentFieldName' => 'test_multi_files',
                        'externalUrl' => null,
                        'content' => self::BLANK_FILE_CONTENT
                    ],
                    'relationships' => [
                        'owner' => [
                            'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                        ],
                        'parent' => [
                            'data' => ['type' => $entityType, 'id' => 'new']
                        ]
                    ]
                ]
            ]
        ];
        $expectedData = $this->updateResponseContent($expectedData, $response);
        $this->assertResponseContains($expectedData, $response);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testUpdateWithMultiImages(): void
    {
        $entityType = $this->getEntityType(TestAttachmentOwner::class);
        $entityId = $this->getReference('entity1')->id;

        $response = $this->patch(
            ['entity' => $entityType, 'id' => (string)$entityId],
            [
                'data' => [
                    'type' => $entityType,
                    'id' => (string)$entityId,
                    'relationships' => [
                        'test_multi_images' => [
                            'data' => [
                                ['type' => 'files', 'id' => '<toString(@image_2->id)>', 'meta' => ['sortOrder' => 2]],
                                ['type' => 'files', 'id' => 'new_file', 'meta' => ['sortOrder' => 1]]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'files',
                        'id' => 'new_file',
                        'attributes' => [
                            'mimeType' => 'image/png',
                            'originalFilename' => 'blank.png',
                            'content' => self::BLANK_FILE_CONTENT
                        ]
                    ]
                ]
            ]
        );

        $expectedData = [
            'data' => [
                'type' => $entityType,
                'id' => (string)$entityId,
                'relationships' => [
                    'test_multi_images' => [
                        'data' => [
                            ['type' => 'files', 'id' => 'new', 'meta' => ['sortOrder' => 1]],
                            ['type' => 'files', 'id' => '<toString(@image_2->id)>', 'meta' => ['sortOrder' => 2]]
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type' => 'files',
                    'id' => 'new',
                    'attributes' => [
                        'mimeType' => 'image/png',
                        'originalFilename' => 'blank.png',
                        'fileSize' => 95,
                        'parentFieldName' => 'test_multi_images',
                        'externalUrl' => null,
                        'content' => self::BLANK_FILE_CONTENT
                    ],
                    'relationships' => [
                        'owner' => [
                            'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                        ],
                        'parent' => [
                            'data' => ['type' => $entityType, 'id' => 'new']
                        ]
                    ]
                ]
            ]
        ];
        $expectedData = $this->updateResponseContent($expectedData, $response);
        $this->assertResponseContains($expectedData, $response);
    }

    public function testUpdateWithFileToNull(): void
    {
        $entityType = $this->getEntityType(TestAttachmentOwner::class);
        $entityId = $this->getReference('entity1')->id;
        $fileId = $this->getReference('file_1')->getId();

        $response = $this->patch(
            ['entity' => $entityType, 'id' => (string)$entityId],
            [
                'data' => [
                    'type' => $entityType,
                    'id' => (string)$entityId,
                    'relationships' => [
                        'test_file' => [
                            'data' => null
                        ]
                    ]
                ]
            ]
        );

        $expectedData = [
            'data' => [
                'type' => $entityType,
                'id' => (string)$entityId,
                'relationships' => [
                    'test_file' => [
                        'data' => null
                    ]
                ]
            ]
        ];
        $expectedData = $this->updateResponseContent($expectedData, $response);
        $this->assertResponseContains($expectedData, $response);

        self::assertTrue(null === $this->getEntityManager()->find(File::class, $fileId));
    }

    public function testUpdateWithImageToNull(): void
    {
        $entityType = $this->getEntityType(TestAttachmentOwner::class);
        $entityId = $this->getReference('entity1')->id;
        $imageId = $this->getReference('image_1')->getId();

        $response = $this->patch(
            ['entity' => $entityType, 'id' => (string)$entityId],
            [
                'data' => [
                    'type' => $entityType,
                    'id' => (string)$entityId,
                    'relationships' => [
                        'test_image' => [
                            'data' => null
                        ]
                    ]
                ]
            ]
        );

        $expectedData = [
            'data' => [
                'type' => $entityType,
                'id' => (string)$entityId,
                'relationships' => [
                    'test_image' => [
                        'data' => null
                    ]
                ]
            ]
        ];
        $expectedData = $this->updateResponseContent($expectedData, $response);
        $this->assertResponseContains($expectedData, $response);

        self::assertTrue(null === $this->getEntityManager()->find(File::class, $imageId));
    }

    public function testUpdateWithMultiFilesWhenSomeFilesAreRemoved(): void
    {
        $entityType = $this->getEntityType(TestAttachmentOwner::class);
        $entityId = $this->getReference('entity1')->id;
        $fileId = $this->getReference('file_3')->getId();

        $response = $this->patch(
            ['entity' => $entityType, 'id' => (string)$entityId],
            [
                'data' => [
                    'type' => $entityType,
                    'id' => (string)$entityId,
                    'relationships' => [
                        'test_multi_files' => [
                            'data' => [
                                ['type' => 'files', 'id' => '<toString(@file_2->id)>', 'meta' => ['sortOrder' => 1]]
                            ]
                        ]
                    ]
                ]
            ]
        );

        $expectedData = [
            'data' => [
                'type' => $entityType,
                'id' => (string)$entityId,
                'relationships' => [
                    'test_multi_files' => [
                        'data' => [
                            ['type' => 'files', 'id' => '<toString(@file_2->id)>', 'meta' => ['sortOrder' => 1]]
                        ]
                    ]
                ]
            ]
        ];
        $expectedData = $this->updateResponseContent($expectedData, $response);
        $this->assertResponseContains($expectedData, $response);

        self::assertTrue(null === $this->getEntityManager()->find(File::class, $fileId));
    }

    public function testUpdateWithMultiImagesWhenSomeImagesAreRemoved(): void
    {
        $entityType = $this->getEntityType(TestAttachmentOwner::class);
        $entityId = $this->getReference('entity1')->id;
        $imageId = $this->getReference('image_3')->getId();

        $response = $this->patch(
            ['entity' => $entityType, 'id' => (string)$entityId],
            [
                'data' => [
                    'type' => $entityType,
                    'id' => (string)$entityId,
                    'relationships' => [
                        'test_multi_images' => [
                            'data' => [
                                ['type' => 'files', 'id' => '<toString(@image_2->id)>', 'meta' => ['sortOrder' => 1]]
                            ]
                        ]
                    ]
                ]
            ]
        );

        $expectedData = [
            'data' => [
                'type' => $entityType,
                'id' => (string)$entityId,
                'relationships' => [
                    'test_multi_images' => [
                        'data' => [
                            ['type' => 'files', 'id' => '<toString(@image_2->id)>', 'meta' => ['sortOrder' => 1]]
                        ]
                    ]
                ]
            ]
        ];
        $expectedData = $this->updateResponseContent($expectedData, $response);
        $this->assertResponseContains($expectedData, $response);

        self::assertTrue(null === $this->getEntityManager()->find(File::class, $imageId));
    }

    public function testTryToUpdateWithFileWhenFileAlreadyExistsAndBelongsToAnotherEntity(): void
    {
        $entityType = $this->getEntityType(TestAttachmentOwner::class);
        $entityId = $this->getReference('entity1')->id;

        $response = $this->patch(
            ['entity' => $entityType, 'id' => (string)$entityId],
            [
                'data' => [
                    'type' => $entityType,
                    'id' => (string)$entityId,
                    'relationships' => [
                        'test_file' => [
                            'data' => ['type' => 'files', 'id' => '<toString(@file_2->id)>']
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'form constraint',
                'detail' => 'This file is already used in another entity.',
                'source' => ['pointer' => '/data/relationships/test_file/data']
            ],
            $response
        );
    }

    public function testTryToUpdateWithImageWhenImageAlreadyExistsAndBelongsToAnotherEntity(): void
    {
        $entityType = $this->getEntityType(TestAttachmentOwner::class);
        $entityId = $this->getReference('entity1')->id;

        $response = $this->patch(
            ['entity' => $entityType, 'id' => (string)$entityId],
            [
                'data' => [
                    'type' => $entityType,
                    'id' => (string)$entityId,
                    'relationships' => [
                        'test_image' => [
                            'data' => ['type' => 'files', 'id' => '<toString(@file_2->id)>']
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'form constraint',
                'detail' => 'This file is already used in another entity.',
                'source' => ['pointer' => '/data/relationships/test_image/data']
            ],
            $response
        );
    }

    public function testTryToUpdateWithMultiFilesWhenFileAlreadyExistsAndBelongsToAnotherEntity(): void
    {
        $entityType = $this->getEntityType(TestAttachmentOwner::class);
        $entityId = $this->getReference('entity1')->id;

        $response = $this->patch(
            ['entity' => $entityType, 'id' => (string)$entityId],
            [
                'data' => [
                    'type' => $entityType,
                    'id' => (string)$entityId,
                    'relationships' => [
                        'test_multi_files' => [
                            'data' => [
                                ['type' => 'files', 'id' => '<toString(@file_1->id)>', 'meta' => ['sortOrder' => 1]]
                            ]
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'form constraint',
                'detail' => 'This file is already used in another entity.',
                'source' => ['pointer' => '/data/relationships/test_multi_files/data/0']
            ],
            $response
        );
    }

    public function testTryToUpdateWithMultiImagesWhenImageAlreadyExistsAndBelongsToAnotherEntity(): void
    {
        $entityType = $this->getEntityType(TestAttachmentOwner::class);
        $entityId = $this->getReference('entity1')->id;

        $response = $this->patch(
            ['entity' => $entityType, 'id' => (string)$entityId],
            [
                'data' => [
                    'type' => $entityType,
                    'id' => (string)$entityId,
                    'relationships' => [
                        'test_multi_images' => [
                            'data' => [
                                ['type' => 'files', 'id' => '<toString(@image_1->id)>', 'meta' => ['sortOrder' => 1]]
                            ]
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'form constraint',
                'detail' => 'This file is already used in another entity.',
                'source' => ['pointer' => '/data/relationships/test_multi_images/data/0']
            ],
            $response
        );
    }

    public function testTryToUpdateWithoutIncludedFileForFile(): void
    {
        $entityType = $this->getEntityType(TestAttachmentOwner::class);
        $entityId = $this->getReference('entity1')->id;

        $response = $this->patch(
            ['entity' => $entityType, 'id' => (string)$entityId],
            [
                'data' => [
                    'type' => $entityType,
                    'id' => (string)$entityId,
                    'relationships' => [
                        'test_file' => [
                            'data' => ['type' => 'files', 'id' => 'new_file']
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'entity identifier constraint',
                'detail' => 'Expected integer value. Given "new_file".',
                'source' => ['pointer' => '/data/relationships/test_file/data/id']
            ],
            $response
        );
    }

    public function testTryToUpdateWithoutIncludedFileForMultiFiles(): void
    {
        $entityType = $this->getEntityType(TestAttachmentOwner::class);
        $entityId = $this->getReference('entity1')->id;

        $response = $this->patch(
            ['entity' => $entityType, 'id' => (string)$entityId],
            [
                'data' => [
                    'type' => $entityType,
                    'id' => (string)$entityId,
                    'relationships' => [
                        'test_multi_files' => [
                            'data' => [
                                ['type' => 'files', 'id' => '<toString(@file_2->id)>'],
                                ['type' => 'files', 'id' => 'new_file', 'meta' => ['sortOrder' => 1]]
                            ]
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'entity identifier constraint',
                'detail' => 'Expected integer value. Given "new_file".',
                'source' => ['pointer' => '/data/relationships/test_multi_files/data/1/id']
            ],
            $response
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testUpdateWithNoSortOrderForMultiFiles(): void
    {
        $entityType = $this->getEntityType(TestAttachmentOwner::class);
        $entityId = $this->getReference('entity1')->id;

        $response = $this->patch(
            ['entity' => $entityType, 'id' => (string)$entityId],
            [
                'data' => [
                    'type' => $entityType,
                    'id' => (string)$entityId,
                    'relationships' => [
                        'test_multi_files' => [
                            'data' => [
                                ['type' => 'files', 'id' => '<toString(@file_2->id)>'],
                                ['type' => 'files', 'id' => '<toString(@file_3->id)>'],
                                ['type' => 'files', 'id' => 'new_file']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'files',
                        'id' => 'new_file',
                        'attributes' => [
                            'mimeType' => 'image/png',
                            'originalFilename' => 'blank.png',
                            'content' => self::BLANK_FILE_CONTENT
                        ]
                    ]
                ]
            ]
        );

        $expectedData = [
            'data' => [
                'type' => $entityType,
                'id' => (string)$entityId,
                'relationships' => [
                    'test_multi_files' => [
                        'data' => [
                            ['type' => 'files', 'id' => 'new', 'meta' => ['sortOrder' => 0]],
                            ['type' => 'files', 'id' => '<toString(@file_3->id)>', 'meta' => ['sortOrder' => 1]],
                            ['type' => 'files', 'id' => '<toString(@file_2->id)>', 'meta' => ['sortOrder' => 2]]
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type' => 'files',
                    'id' => 'new',
                    'attributes' => [
                        'mimeType' => 'image/png',
                        'originalFilename' => 'blank.png',
                        'fileSize' => 95,
                        'parentFieldName' => 'test_multi_files',
                        'externalUrl' => null,
                        'content' => self::BLANK_FILE_CONTENT
                    ],
                    'relationships' => [
                        'owner' => [
                            'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                        ],
                        'parent' => [
                            'data' => ['type' => $entityType, 'id' => 'new']
                        ]
                    ]
                ]
            ]
        ];
        $expectedData = $this->updateResponseContent($expectedData, $response);
        $this->assertResponseContains($expectedData, $response);
    }

    public function testTryToUpdateWithInvalidSortOrderForMultiFiles(): void
    {
        $entityType = $this->getEntityType(TestAttachmentOwner::class);
        $entityId = $this->getReference('entity1')->id;

        $response = $this->patch(
            ['entity' => $entityType, 'id' => (string)$entityId],
            [
                'data' => [
                    'type' => $entityType,
                    'id' => (string)$entityId,
                    'relationships' => [
                        'test_multi_files' => [
                            'data' => [
                                ['type' => 'files', 'id' => '<toString(@file_2->id)>'],
                                ['type' => 'files', 'id' => 'new_file', 'meta' => ['sortOrder' => 'a1']]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'files',
                        'id' => 'new_file',
                        'attributes' => [
                            'mimeType' => 'image/png',
                            'originalFilename' => 'blank.png',
                            'content' => self::BLANK_FILE_CONTENT
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'type constraint',
                'detail' => 'The sort order should be an integer.',
                'source' => ['pointer' => '/data/relationships/test_multi_files/data/1']
            ],
            $response
        );
    }

    public function testTryToUpdateWithDecimalSortOrderForMultiFiles(): void
    {
        $entityType = $this->getEntityType(TestAttachmentOwner::class);
        $entityId = $this->getReference('entity1')->id;

        $response = $this->patch(
            ['entity' => $entityType, 'id' => (string)$entityId],
            [
                'data' => [
                    'type' => $entityType,
                    'id' => (string)$entityId,
                    'relationships' => [
                        'test_multi_files' => [
                            'data' => [
                                ['type' => 'files', 'id' => '<toString(@file_2->id)>'],
                                ['type' => 'files', 'id' => 'new_file', 'meta' => ['sortOrder' => 1.1]]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'files',
                        'id' => 'new_file',
                        'attributes' => [
                            'mimeType' => 'image/png',
                            'originalFilename' => 'blank.png',
                            'content' => self::BLANK_FILE_CONTENT
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'type constraint',
                'detail' => 'The sort order should be an integer.',
                'source' => ['pointer' => '/data/relationships/test_multi_files/data/1']
            ],
            $response
        );
    }

    public function testTryToUpdateWithNullSortOrderForMultiFiles(): void
    {
        $entityType = $this->getEntityType(TestAttachmentOwner::class);
        $entityId = $this->getReference('entity1')->id;

        $response = $this->patch(
            ['entity' => $entityType, 'id' => (string)$entityId],
            [
                'data' => [
                    'type' => $entityType,
                    'id' => (string)$entityId,
                    'relationships' => [
                        'test_multi_files' => [
                            'data' => [
                                ['type' => 'files', 'id' => '<toString(@file_2->id)>'],
                                ['type' => 'files', 'id' => 'new_file', 'meta' => ['sortOrder' => null]]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'files',
                        'id' => 'new_file',
                        'attributes' => [
                            'mimeType' => 'image/png',
                            'originalFilename' => 'blank.png',
                            'content' => self::BLANK_FILE_CONTENT
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'type constraint',
                'detail' => 'The sort order should be an integer.',
                'source' => ['pointer' => '/data/relationships/test_multi_files/data/1']
            ],
            $response
        );
    }

    public function testTryToUpdateWithNegativeIntegerSortOrderForMultiFiles(): void
    {
        $entityType = $this->getEntityType(TestAttachmentOwner::class);
        $entityId = $this->getReference('entity1')->id;

        $response = $this->patch(
            ['entity' => $entityType, 'id' => (string)$entityId],
            [
                'data' => [
                    'type' => $entityType,
                    'id' => (string)$entityId,
                    'relationships' => [
                        'test_multi_files' => [
                            'data' => [
                                ['type' => 'files', 'id' => '<toString(@file_2->id)>'],
                                ['type' => 'files', 'id' => 'new_file', 'meta' => ['sortOrder' => -1]]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'files',
                        'id' => 'new_file',
                        'attributes' => [
                            'mimeType' => 'image/png',
                            'originalFilename' => 'blank.png',
                            'content' => self::BLANK_FILE_CONTENT
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'range constraint',
                'detail' => 'The sort order should be between 0 and 2147483647.',
                'source' => ['pointer' => '/data/relationships/test_multi_files/data/1']
            ],
            $response
        );
    }

    public function testGetRelationshipForFile(): void
    {
        $entityType = $this->getEntityType(TestAttachmentOwner::class);

        $response = $this->getRelationship(
            ['entity' => $entityType, 'id' => '<toString(@entity1->id)>', 'association' => 'test_file']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'files', 'id' => '<toString(@file_1->id)>']],
            $response
        );
    }

    public function testGetSubresourceForFile(): void
    {
        $entityType = $this->getEntityType(TestAttachmentOwner::class);
        $entityId = $this->getReference('entity1')->id;

        $response = $this->getSubresource(
            ['entity' => $entityType, 'id' => (string)$entityId, 'association' => 'test_file']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'files',
                    'id' => '<toString(@file_1->id)>',
                    'attributes' => [
                        'originalFilename' => 'file_1.txt'
                    ],
                    'relationships' => [
                        'parent' => [
                            'data' => ['type' => $entityType, 'id' => (string)$entityId]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToUpdateRelationshipForFile(): void
    {
        $entityType = $this->getEntityType(TestAttachmentOwner::class);

        $response = $this->patchRelationship(
            ['entity' => $entityType, 'id' => '<toString(@entity1->id)>', 'association' => 'test_file'],
            ['data' => ['type' => 'files', 'id' => '<toString(@file_1->id)>']],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetRelationshipForImage(): void
    {
        $entityType = $this->getEntityType(TestAttachmentOwner::class);

        $response = $this->getRelationship(
            ['entity' => $entityType, 'id' => '<toString(@entity1->id)>', 'association' => 'test_image']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'files', 'id' => '<toString(@image_1->id)>']],
            $response
        );
    }

    public function testGetSubresourceForImage(): void
    {
        $entityType = $this->getEntityType(TestAttachmentOwner::class);
        $entityId = $this->getReference('entity1')->id;

        $response = $this->getSubresource(
            ['entity' => $entityType, 'id' => (string)$entityId, 'association' => 'test_image']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'files',
                    'id' => '<toString(@image_1->id)>',
                    'attributes' => [
                        'originalFilename' => 'image_1.jpg'
                    ],
                    'relationships' => [
                        'parent' => [
                            'data' => ['type' => $entityType, 'id' => (string)$entityId]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToUpdateRelationshipForImage(): void
    {
        $entityType = $this->getEntityType(TestAttachmentOwner::class);

        $response = $this->patchRelationship(
            ['entity' => $entityType, 'id' => '<toString(@entity1->id)>', 'association' => 'test_image'],
            ['data' => ['type' => 'files', 'id' => '<toString(@image_1->id)>']],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetRelationshipForMultiFiles(): void
    {
        $entityType = $this->getEntityType(TestAttachmentOwner::class);

        $response = $this->getRelationship(
            ['entity' => $entityType, 'id' => '<toString(@entity1->id)>', 'association' => 'test_multi_files']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'files', 'id' => '<toString(@file_3->id)>', 'meta' => ['sortOrder' => 1]],
                    ['type' => 'files', 'id' => '<toString(@file_2->id)>', 'meta' => ['sortOrder' => 2]]
                ]
            ],
            $response
        );
    }

    public function testGetSubresourceForMultiFiles(): void
    {
        $entityType = $this->getEntityType(TestAttachmentOwner::class);
        $entityId = $this->getReference('entity1')->id;

        $response = $this->getSubresource(
            ['entity' => $entityType, 'id' => (string)$entityId, 'association' => 'test_multi_files']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'files',
                        'id' => '<toString(@file_3->id)>',
                        'meta' => ['sortOrder' => 1],
                        'attributes' => [
                            'originalFilename' => 'file_3.txt'
                        ],
                        'relationships' => [
                            'parent' => [
                                'data' => ['type' => $entityType, 'id' => (string)$entityId]
                            ]
                        ]
                    ],
                    [
                        'type' => 'files',
                        'id' => '<toString(@file_2->id)>',
                        'meta' => ['sortOrder' => 2],
                        'attributes' => [
                            'originalFilename' => 'file_2.txt'
                        ],
                        'relationships' => [
                            'parent' => [
                                'data' => ['type' => $entityType, 'id' => (string)$entityId]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );

        $responseData = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('test_multi_files', $responseData['data'][0]['relationships']);
    }

    public function testTryToUpdateRelationshipForMultiFiles(): void
    {
        $entityType = $this->getEntityType(TestAttachmentOwner::class);

        $response = $this->patchRelationship(
            ['entity' => $entityType, 'id' => '<toString(@entity1->id)>', 'association' => 'test_multi_files'],
            ['data' => [['type' => 'files', 'id' => '<toString(@file_3->id)>', 'meta' => ['sortOrder' => 1]]]],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToAddRelationshipForMultiFiles(): void
    {
        $entityType = $this->getEntityType(TestAttachmentOwner::class);

        $response = $this->postRelationship(
            ['entity' => $entityType, 'id' => '<toString(@entity1->id)>', 'association' => 'test_multi_files'],
            ['data' => [['type' => 'files', 'id' => '<toString(@file_3->id)>', 'meta' => ['sortOrder' => 1]]]],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteRelationshipForMultiFiles(): void
    {
        $entityType = $this->getEntityType(TestAttachmentOwner::class);

        $response = $this->deleteRelationship(
            ['entity' => $entityType, 'id' => '<toString(@entity1->id)>', 'association' => 'test_multi_files'],
            ['data' => [['type' => 'files', 'id' => '<toString(@file_3->id)>', 'meta' => ['sortOrder' => 1]]]],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetRelationshipForMultiImages(): void
    {
        $entityType = $this->getEntityType(TestAttachmentOwner::class);

        $response = $this->getRelationship(
            ['entity' => $entityType, 'id' => '<toString(@entity1->id)>', 'association' => 'test_multi_images']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'files', 'id' => '<toString(@image_3->id)>', 'meta' => ['sortOrder' => 1]],
                    ['type' => 'files', 'id' => '<toString(@image_2->id)>', 'meta' => ['sortOrder' => 2]]
                ]
            ],
            $response
        );
    }

    public function testGetSubresourceForMultiImages(): void
    {
        $entityType = $this->getEntityType(TestAttachmentOwner::class);
        $entityId = $this->getReference('entity1')->id;

        $response = $this->getSubresource(
            ['entity' => $entityType, 'id' => (string)$entityId, 'association' => 'test_multi_images']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'files',
                        'id' => '<toString(@image_3->id)>',
                        'meta' => ['sortOrder' => 1],
                        'attributes' => [
                            'originalFilename' => 'image_3.jpg'
                        ],
                        'relationships' => [
                            'parent' => [
                                'data' => ['type' => $entityType, 'id' => (string)$entityId]
                            ]
                        ]
                    ],
                    [
                        'type' => 'files',
                        'id' => '<toString(@image_2->id)>',
                        'meta' => ['sortOrder' => 2],
                        'attributes' => [
                            'originalFilename' => 'image_2.jpg'
                        ],
                        'relationships' => [
                            'parent' => [
                                'data' => ['type' => $entityType, 'id' => (string)$entityId]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );

        $responseData = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('test_multi_images', $responseData['data'][0]['relationships']);
    }

    public function testTryToUpdateRelationshipForMultiImages(): void
    {
        $entityType = $this->getEntityType(TestAttachmentOwner::class);

        $response = $this->patchRelationship(
            ['entity' => $entityType, 'id' => '<toString(@entity1->id)>', 'association' => 'test_multi_images'],
            ['data' => [['type' => 'files', 'id' => '<toString(@image_3->id)>', 'meta' => ['sortOrder' => 1]]]],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToAddRelationshipForMultiImages(): void
    {
        $entityType = $this->getEntityType(TestAttachmentOwner::class);

        $response = $this->postRelationship(
            ['entity' => $entityType, 'id' => '<toString(@entity1->id)>', 'association' => 'test_multi_images'],
            ['data' => [['type' => 'files', 'id' => '<toString(@image_3->id)>', 'meta' => ['sortOrder' => 1]]]],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteRelationshipForMultiImages(): void
    {
        $entityType = $this->getEntityType(TestAttachmentOwner::class);

        $response = $this->deleteRelationship(
            ['entity' => $entityType, 'id' => '<toString(@entity1->id)>', 'association' => 'test_multi_images'],
            ['data' => [['type' => 'files', 'id' => '<toString(@image_3->id)>', 'meta' => ['sortOrder' => 1]]]],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
