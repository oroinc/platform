<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\AttachmentBundle\Tests\Functional\Api\DataFixtures\LoadFileData;
use Oro\Bundle\AttachmentBundle\Tests\Functional\Stub\ExternalFileFactoryStub;
use Oro\Bundle\AttachmentBundle\Tools\MimeTypesConverter;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 * @nestTransactionsWithSavepoints
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FileTest extends RestJsonApiTestCase
{
    private const string BLANK_FILE_CONTENT = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAAA'
        . '1BMVEUAAACnej3aAAAAAXRSTlMAQObYZgAAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII=';
    private const string PDF_FILE_PATH = __DIR__ . '/../../DataFixtures/files/test.pdf';
    private const string SVG_FILE_PATH = __DIR__ . '/../../DataFixtures/files/image.svg';
    private const string JPEG_FILE_PATH = __DIR__ . '/../../DataFixtures/files/image.jpg';

    private const string NOT_ALLOWED_ATTACHMENT_MIME_TYPE_MESSAGE = 'The MIME type of the file is invalid'
        . ' ("application/pdf"). Allowed MIME types are "image/*".';
    private const string NOT_ALLOWED_AVATAR_MIME_TYPE_MESSAGE = 'The MIME type of the file is invalid ("text/plain").'
        . ' Allowed MIME types are "image/gif", "image/jpeg", "image/png", "image/webp".';

    private const string NOT_ALLOWED_SYSTEM_MIME_TYPE_MESSAGE = 'The MIME type of the file is invalid ("text/html").'
        . ' Allowed MIME types are "text/plain", "application/pdf", "image/gif", "image/jpeg", "image/png",'
        . ' "image/webp".';

    private const string HTML_FILE_CONTENT = 'PHNjcmlwdD5kb2N1bWVudC50aXRsZT0nUlVOJzwvc2NyaXB0Pg==';
    private const string NOT_ALLOWED_AVATAR_HTML_MIME_TYPE_MESSAGE = 'The MIME type of the file is invalid'
        . ' ("text/html"). Allowed MIME types are "image/gif", "image/jpeg", "image/png", "image/webp".';

    private const array SYSTEM_FILE_MIME_TYPES = [
        'text/plain',
        'application/pdf',
        'image/gif',
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    private string $initialExternalFileAllowedUrlsRegExp;

    private array $initialAvatarFieldOptions = [];

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadUser::class, LoadFileData::class]);

        $this->initialExternalFileAllowedUrlsRegExp = self::getConfigManager()
            ->get('oro_attachment.external_file_allowed_urls_regexp');
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->setExternalFileAllowedUrlsRegExp($this->initialExternalFileAllowedUrlsRegExp);
        $this->setIsStoredExternally(false);
        $this->restoreAvatarFieldOptions();
        $this->restoreConfigValues();
        parent::tearDown();
    }

    private function createExternalFileWithoutParent(string $externalUrl): int
    {
        $file = new File();
        $file->setFilename('image-a.png');
        $file->setOriginalFilename('image-a.png');
        $file->setMimeType('image/png');
        $file->setExternalUrl($externalUrl);
        $entityManager = $this->getEntityManager();
        $entityManager->persist($file);
        $entityManager->flush();

        return $file->getId();
    }

    private function loadAttachmentFixtures(): void
    {
        $this->loadFixtures(['@OroAttachmentBundle/Tests/Functional/Api/DataFixtures/attachment_data.yml']);
    }

    private function setExternalFileAllowedUrlsRegExp(string $value): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_attachment.external_file_allowed_urls_regexp', $value);
        $configManager->flush();
    }

    private function setIsStoredExternally(bool $isStoredExternally): void
    {
        $entityConfigManager = self::getContainer()->get('oro_entity_config.config_manager');
        $avatarFieldConfig = $entityConfigManager->getFieldConfig('attachment', User::class, 'avatar');
        if ($avatarFieldConfig->get('is_stored_externally') !== $isStoredExternally) {
            $avatarFieldConfig->set('is_stored_externally', $isStoredExternally);
            $entityConfigManager->persist($avatarFieldConfig);
            $entityConfigManager->flush();
        }
    }

    private function setAvatarFieldOption(string $name, mixed $value): void
    {
        $entityConfigManager = self::getContainer()->get('oro_entity_config.config_manager');
        $avatarFieldConfig = $entityConfigManager->getFieldConfig('attachment', User::class, 'avatar');
        if (!\array_key_exists($name, $this->initialAvatarFieldOptions)) {
            $this->initialAvatarFieldOptions[$name] = $avatarFieldConfig->get($name);
        }
        $avatarFieldConfig->set($name, $value);
        $entityConfigManager->persist($avatarFieldConfig);
        $entityConfigManager->flush();
    }

    private function restoreAvatarFieldOptions(): void
    {
        if (!$this->initialAvatarFieldOptions) {
            return;
        }

        $entityConfigManager = self::getContainer()->get('oro_entity_config.config_manager');
        $avatarFieldConfig = $entityConfigManager->getFieldConfig('attachment', User::class, 'avatar');
        foreach ($this->initialAvatarFieldOptions as $name => $value) {
            $avatarFieldConfig->set($name, $value);
        }
        $entityConfigManager->persist($avatarFieldConfig);
        $entityConfigManager->flush();
        $this->initialAvatarFieldOptions = [];
    }

    private function getFileManager(): FileManager
    {
        return self::getContainer()->get('oro_attachment.file_manager');
    }

    public function testGet(): void
    {
        $fileId = $this->getReference('file_1')->getId();

        $response = $this->get(
            ['entity' => 'files', 'id' => (string)$fileId]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'files',
                    'id' => (string)$fileId,
                    'attributes' => [
                        'mimeType' => 'text/plain',
                        'originalFilename' => 'file_1.txt',
                        'fileSize' => 7,
                        'content' => 'ZmlsZV9hCg==',
                        'parentFieldName' => 'avatar'
                    ],
                    'relationships' => [
                        'owner' => [
                            'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                        ],
                        'parent' => [
                            'data' => ['type' => 'users', 'id' => '<toString(@user_1->id)>']
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testCreateWhenParentEntityIsNotLinkedToAnotherFile(): void
    {
        /** @var User $user */
        $user = $this->getReference('user_3');
        $userId = $user->getId();
        self::assertTrue(null === $user->getAvatar());

        $data = [
            'data' => [
                'type' => 'files',
                'attributes' => [
                    'mimeType' => 'image/png',
                    'originalFilename' => 'blank.png',
                    'fileSize' => 95,
                    'content' => self::BLANK_FILE_CONTENT,
                    'parentFieldName' => 'avatar'
                ],
                'relationships' => [
                    'parent' => [
                        'data' => ['type' => 'users', 'id' => (string)$userId]
                    ]
                ]
            ]
        ];
        $response = $this->post(['entity' => 'files'], $data);

        $expectedData = $data;
        $expectedData['data']['relationships']['owner']['data'] = [
            'type' => 'users',
            'id' => '<toString(@user->id)>'
        ];
        $this->assertResponseContains($expectedData, $response);

        $fileId = (int)$this->getResourceId($response);

        // test that the File entity was created
        $file = $this->getEntityManager()->find(File::class, $fileId);
        self::assertFalse(null === $file);

        // test that the File entity was linked to the parent entity
        $user = $this->getEntityManager()->find(User::class, $userId);
        self::assertInstanceOf(File::class, $user->getAvatar());
        self::assertEquals($file->getId(), $user->getAvatar()->getId());
    }

    public function testCreateWhenParentEntityLinkedToAnotherFile(): void
    {
        /** @var User $user */
        $user = $this->getReference('user_1');
        $userId = $user->getId();
        $previousFileId = $user->getAvatar()->getId();
        $previousFileName = $user->getAvatar()->getFilename();

        $data = [
            'data' => [
                'type' => 'files',
                'attributes' => [
                    'mimeType' => 'image/png',
                    'originalFilename' => 'blank.png',
                    'fileSize' => 95,
                    'content' => self::BLANK_FILE_CONTENT,
                    'parentFieldName' => 'avatar'
                ],
                'relationships' => [
                    'parent' => [
                        'data' => ['type' => 'users', 'id' => (string)$userId]
                    ]
                ]
            ]
        ];
        $response = $this->post(['entity' => 'files'], $data);

        $expectedData = $data;
        $expectedData['data']['relationships']['owner']['data'] = [
            'type' => 'users',
            'id' => '<toString(@user->id)>'
        ];
        $this->assertResponseContains($expectedData, $response);

        $fileId = (int)$this->getResourceId($response);

        // test that the File entity was created
        $file = $this->getEntityManager()->find(File::class, $fileId);
        self::assertFalse(null === $file);

        // test that the File entity was linked to the parent entity
        $user = $this->getEntityManager()->find(User::class, $userId);
        self::assertInstanceOf(File::class, $user->getAvatar());
        self::assertEquals($file->getId(), $user->getAvatar()->getId());

        // test that the previously linked File entity was removed
        $previousFile = $this->getEntityManager()->find(File::class, $previousFileId);
        self::assertTrue(null === $previousFile);
        // test that the previously linked file was removed from the filesystem
        self::assertFalse($this->getFileManager()->hasFile($previousFileName));
    }

    public function testTryToCreateWhenParentEntityFieldNameIsNotValid(): void
    {
        /** @var User $user */
        $user = $this->getReference('user_1');
        $userId = $user->getId();
        $previousFileId = $user->getAvatar()->getId();

        $data = [
            'data' => [
                'type' => 'files',
                'attributes' => [
                    'mimeType' => 'image/png',
                    'originalFilename' => 'blank.png',
                    'fileSize' => 95,
                    'content' => self::BLANK_FILE_CONTENT,
                    'parentFieldName' => 'notExistingField'
                ],
                'relationships' => [
                    'parent' => [
                        'data' => ['type' => 'users', 'id' => (string)$userId]
                    ]
                ]
            ]
        ];
        $response = $this->post(['entity' => 'files'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title' => 'value constraint',
                'detail' => 'Invalid parent entity field name.',
                'source' => ['pointer' => '/data/attributes/parentFieldName']
            ],
            $response
        );

        // test that the previous File entity was not unlinked from the parent entity
        $user = $this->getEntityManager()->find(User::class, $userId);
        self::assertInstanceOf(File::class, $user->getAvatar());
        self::assertEquals($previousFileId, $user->getAvatar()->getId());

        // test that the previously linked File entity was not removed
        $previousFile = $this->getEntityManager()->find(File::class, $previousFileId);
        self::assertFalse(null === $previousFile);
    }

    public function testCreateWhenParentEntityLinkedToAnotherFileWithoutParentEntityFieldName(): void
    {
        /** @var User $user */
        $user = $this->getReference('user_4');
        $userId = $user->getId();
        $previousFileId = $user->getAvatar()->getId();

        $data = [
            'data' => [
                'type' => 'files',
                'attributes' => [
                    'mimeType' => 'image/png',
                    'originalFilename' => 'blank.png',
                    'fileSize' => 95,
                    'content' => self::BLANK_FILE_CONTENT,
                    'parentFieldName' => 'avatar'
                ],
                'relationships' => [
                    'parent' => [
                        'data' => ['type' => 'users', 'id' => (string)$userId]
                    ]
                ]
            ]
        ];
        $response = $this->post(['entity' => 'files'], $data);

        $expectedData = $data;
        $expectedData['data']['relationships']['owner']['data'] = [
            'type' => 'users',
            'id' => '<toString(@user->id)>'
        ];
        $this->assertResponseContains($expectedData, $response);

        $fileId = (int)$this->getResourceId($response);

        // test that the File entity was created
        $file = $this->getEntityManager()->find(File::class, $fileId);
        self::assertFalse(null === $file);

        // test that the File entity was linked to the parent entity
        $user = $this->getEntityManager()->find(User::class, $userId);
        self::assertInstanceOf(File::class, $user->getAvatar());
        self::assertEquals($file->getId(), $user->getAvatar()->getId());

        // test that the previously linked File entity was not removed
        $previousFile = $this->getEntityManager()->find(File::class, $previousFileId);
        self::assertFalse(null === $previousFile);
    }

    public function testTryToCreateWithoutOriginalFilename(): void
    {
        $data = [
            'data' => [
                'type' => 'files',
                'attributes' => [
                    'mimeType' => 'image/png',
                    'fileSize' => 95,
                    'content' => self::BLANK_FILE_CONTENT,
                    'parentFieldName' => 'avatar'
                ],
                'relationships' => [
                    'parent' => [
                        'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                    ]
                ]
            ]
        ];
        $response = $this->post(['entity' => 'files'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title' => 'form constraint',
                'detail' => 'The "content" field should be specified together with "originalFilename" field.'
            ],
            $response
        );
    }

    public function testTryToCreateWithPathInOriginalFilename(): void
    {
        $data = [
            'data' => [
                'type' => 'files',
                'attributes' => [
                    'mimeType' => 'image/png',
                    'originalFilename' => '/path/blank.png',
                    'fileSize' => 95,
                    'content' => self::BLANK_FILE_CONTENT,
                    'parentFieldName' => 'avatar'
                ],
                'relationships' => [
                    'parent' => [
                        'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                    ]
                ]
            ]
        ];
        $response = $this->post(['entity' => 'files'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title' => 'filename without path constraint',
                'detail' => 'The file name should not have a path.',
                'source' => ['pointer' => '/data/attributes/originalFilename']
            ],
            $response
        );
    }

    public function testTryToCreateWithExtraLongOriginalFilename(): void
    {
        $data = [
            'data' => [
                'type' => 'files',
                'attributes' => [
                    'mimeType' => 'image/png',
                    'originalFilename' => 'Fuscebibendumleointemporhendreritmaurisestsemperodiovestibulumconguearcuera'
                        . 'tegeterateraesentacorcjustojrcivariusnatoquepenatibusetmagnisdisparturientmontesFuscebibend'
                        . 'umleointemporhendreritmaurisestsemperodiovestibulumconguearcuerategeterateraesentacorcjusto'
                        . 'jrcivariusnatoquepenatibusetmagnisdisparturientmontes.png',
                    'fileSize' => 95,
                    'content' => self::BLANK_FILE_CONTENT,
                    'parentFieldName' => 'avatar'
                ],
                'relationships' => [
                    'parent' => [
                        'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                    ]
                ]
            ]
        ];
        $response = $this->post(['entity' => 'files'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title' => 'length constraint',
                'detail' => 'This value is too long. It should have 255 characters or less.',
                'source' => ['pointer' => '/data/attributes/originalFilename']
            ],
            $response
        );
    }

    public function testTryToCreateWithoutParent(): void
    {
        $data = [
            'data' => [
                'type' => 'files',
                'attributes' => [
                    'mimeType' => 'image/png',
                    'originalFilename' => 'blank.png',
                    'fileSize' => 95,
                    'content' => self::BLANK_FILE_CONTENT,
                    'parentFieldName' => 'avatar'
                ]
            ]
        ];
        $response = $this->post(['entity' => 'files'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/relationships/parent/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithoutParentAndWithContentNotAllowedBySystemConfig(): void
    {
        $this->setConfigValue(
            'oro_attachment.upload_file_mime_types',
            MimeTypesConverter::convertToString(self::SYSTEM_FILE_MIME_TYPES)
        );

        $data = [
            'data' => [
                'type' => 'files',
                'attributes' => [
                    'originalFilename' => 'test.html',
                    'content' => base64_encode('<script>document.title = "test";</script>'),
                    'parentFieldName' => 'avatar'
                ]
            ]
        ];
        $response = $this->post(['entity' => 'files'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title' => 'file constraint',
                'detail' => self::NOT_ALLOWED_SYSTEM_MIME_TYPE_MESSAGE,
                'source' => ['pointer' => '/data/attributes/content']
            ],
            $response
        );
    }

    public function testTryToUpdateContentOfFileWithoutParentExceedingSystemMaxSize(): void
    {
        $this->setConfigValue('oro_attachment.maxsize', 1);
        $fileId = $this->createExternalFileWithoutParent(ExternalFileFactoryStub::IMAGE_A_TEST_URL);

        $response = $this->patch(
            ['entity' => 'files', 'id' => (string)$fileId],
            [
                'data' => [
                    'type' => 'files',
                    'id' => (string)$fileId,
                    'attributes' => [
                        'originalFilename' => 'test.txt',
                        'content' => base64_encode(str_repeat('a', 1024 * 1024 + 1))
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'file constraint',
                'detail' => 'The file is too large (1048577 bytes). Allowed maximum size is 1048576 bytes.',
                'source' => ['pointer' => '/data/attributes/content']
            ],
            $response
        );
    }

    public function testTryToUpdateExternalUrlOfFileWithoutParentNotAllowedBySystemConfig(): void
    {
        $this->setExternalFileAllowedUrlsRegExp('^http://example\\.org');
        $this->setConfigValue(
            'oro_attachment.upload_file_mime_types',
            MimeTypesConverter::convertToString(['application/pdf'])
        );
        $this->setConfigValue(
            'oro_attachment.upload_image_mime_types',
            MimeTypesConverter::convertToString(['image/gif'])
        );
        $fileId = $this->createExternalFileWithoutParent(ExternalFileFactoryStub::IMAGE_A_TEST_URL);

        $response = $this->patch(
            ['entity' => 'files', 'id' => (string)$fileId],
            [
                'data' => [
                    'type' => 'files',
                    'id' => (string)$fileId,
                    'attributes' => ['externalUrl' => ExternalFileFactoryStub::IMAGE_B_TEST_URL]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'external file mime type constraint',
                'detail' => 'The MIME type of the file is invalid ("image/png"). Allowed MIME types are'
                    . ' "application/pdf", "image/gif".',
                'source' => ['pointer' => '/data/attributes/externalUrl']
            ],
            $response
        );
    }

    public function testUpdateExternalUrlOfFileWithoutParentAllowedBySystemConfig(): void
    {
        $this->setExternalFileAllowedUrlsRegExp('^http://example\\.org');
        $fileId = $this->createExternalFileWithoutParent(ExternalFileFactoryStub::IMAGE_A_TEST_URL);

        $response = $this->patch(
            ['entity' => 'files', 'id' => (string)$fileId],
            [
                'data' => [
                    'type' => 'files',
                    'id' => (string)$fileId,
                    'attributes' => ['externalUrl' => ExternalFileFactoryStub::IMAGE_B_TEST_URL]
                ]
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'files',
                    'attributes' => ['externalUrl' => ExternalFileFactoryStub::IMAGE_B_TEST_URL]
                ]
            ],
            $response
        );
    }

    public function testTryToCreateWithoutParentFieldName(): void
    {
        $data = [
            'data' => [
                'type' => 'files',
                'attributes' => [
                    'mimeType' => 'image/png',
                    'originalFilename' => 'blank.png',
                    'fileSize' => 95,
                    'content' => self::BLANK_FILE_CONTENT
                ],
                'relationships' => [
                    'parent' => [
                        'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                    ]
                ]
            ]
        ];
        $response = $this->post(['entity' => 'files'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/parentFieldName']
            ],
            $response
        );
    }

    public function testTryToCreateWithInvalidContent(): void
    {
        $data = [
            'data' => [
                'type' => 'files',
                'attributes' => [
                    'mimeType' => 'image/png',
                    'originalFilename' => 'blank.png',
                    'fileSize' => 95,
                    'content' => '0',
                    'parentFieldName' => 'avatar'
                ],
                'relationships' => [
                    'parent' => [
                        'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                    ]
                ]
            ]
        ];
        $response = $this->post(['entity' => 'files'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title' => 'form constraint',
                'detail' => 'Cannot decode content encoded with MIME base64.'
            ],
            $response
        );
    }

    public function testTryToCreateWithContentNotAllowedForParentField(): void
    {
        $data = [
            'data' => [
                'type' => 'files',
                'attributes' => [
                    'mimeType' => 'image/png',
                    'originalFilename' => 'test.txt',
                    'content' => base64_encode('plain text content'),
                    'parentFieldName' => 'avatar'
                ],
                'relationships' => [
                    'parent' => [
                        'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                    ]
                ]
            ]
        ];
        $response = $this->post(['entity' => 'files'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title' => 'file constraint',
                'detail' => self::NOT_ALLOWED_AVATAR_MIME_TYPE_MESSAGE,
                'source' => ['pointer' => '/data/attributes/content']
            ],
            $response
        );
    }

    public function testTryToCreateUserWithIncludedAvatarWithContentNotAllowedForField(): void
    {
        $data = [
            'data' => [
                'type' => 'users',
                'attributes' => [
                    'username' => 'user_with_avatar',
                    'firstName' => 'Bob',
                    'lastName' => 'Doo',
                    'email' => 'user_with_avatar@example.com'
                ],
                'relationships' => [
                    'avatar' => ['data' => ['type' => 'files', 'id' => 'new_avatar']]
                ]
            ],
            'included' => [
                [
                    'type' => 'files',
                    'id' => 'new_avatar',
                    'attributes' => [
                        'originalFilename' => 'test.txt',
                        'content' => base64_encode('plain text content')
                    ]
                ]
            ]
        ];
        $response = $this->post(['entity' => 'users'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title' => 'file constraint',
                'detail' => self::NOT_ALLOWED_AVATAR_MIME_TYPE_MESSAGE,
                'source' => ['pointer' => '/included/0/attributes/content']
            ],
            $response
        );
    }

    public function testTryToCreateUserWithIncludedAvatarWithExternalUrlForFieldNotStoredExternally(): void
    {
        $this->setExternalFileAllowedUrlsRegExp('^http://example\\.org');

        $data = [
            'data' => [
                'type' => 'users',
                'attributes' => [
                    'username' => 'user_with_external_avatar',
                    'firstName' => 'Bob',
                    'lastName' => 'Doo',
                    'email' => 'user_with_external_avatar@example.com'
                ],
                'relationships' => [
                    'avatar' => ['data' => ['type' => 'files', 'id' => 'new_avatar']]
                ]
            ],
            'included' => [
                [
                    'type' => 'files',
                    'id' => 'new_avatar',
                    'attributes' => ['externalUrl' => ExternalFileFactoryStub::IMAGE_A_TEST_URL]
                ]
            ]
        ];
        $response = $this->post(['entity' => 'users'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title' => 'file field compatibility constraint',
                'detail' => 'The specified external file is not compatible with the field Avatar that is configured '
                    . 'to upload files.',
                'source' => ['pointer' => '/included/0/attributes/externalUrl']
            ],
            $response
        );
    }

    public function testTryToCreateUserWithIncludedAvatarWithExternalUrlWithMimeTypeNotAllowedForField(): void
    {
        $this->setIsStoredExternally(true);
        $this->setExternalFileAllowedUrlsRegExp('^http://example\\.org');

        $data = [
            'data' => [
                'type' => 'users',
                'attributes' => [
                    'username' => 'user_with_external_text',
                    'firstName' => 'Bob',
                    'lastName' => 'Doo',
                    'email' => 'user_with_external_text@example.com'
                ],
                'relationships' => [
                    'avatar' => ['data' => ['type' => 'files', 'id' => 'new_avatar']]
                ]
            ],
            'included' => [
                [
                    'type' => 'files',
                    'id' => 'new_avatar',
                    'attributes' => ['externalUrl' => ExternalFileFactoryStub::FILE_A_TEST_URL]
                ]
            ]
        ];
        $response = $this->post(['entity' => 'users'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title' => 'external file mime type constraint',
                'detail' => 'The MIME type of the file is invalid ("text/plain"). Allowed MIME types are '
                    . '"image/gif", "image/jpeg", "image/png", "image/webp".',
                'source' => ['pointer' => '/included/0/attributes/externalUrl']
            ],
            $response
        );
    }

    public function testTryToUpdateUserWithIncludedAvatarWithExternalUrlForFieldNotStoredExternally(): void
    {
        $this->setExternalFileAllowedUrlsRegExp('^http://example\\.org');
        $userId = $this->getReference('user_3')->getId();

        $data = [
            'data' => [
                'type' => 'users',
                'id' => (string)$userId,
                'relationships' => [
                    'avatar' => ['data' => ['type' => 'files', 'id' => 'new_avatar']]
                ]
            ],
            'included' => [
                [
                    'type' => 'files',
                    'id' => 'new_avatar',
                    'attributes' => ['externalUrl' => ExternalFileFactoryStub::IMAGE_A_TEST_URL]
                ]
            ]
        ];
        $response = $this->patch(['entity' => 'users', 'id' => (string)$userId], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title' => 'file field compatibility constraint',
                'detail' => 'The specified external file is not compatible with the field Avatar that is configured '
                    . 'to upload files.',
                'source' => ['pointer' => '/included/0/attributes/externalUrl']
            ],
            $response
        );

        $entityManager = $this->getEntityManager();
        $entityManager->clear();
        self::assertNull($entityManager->find(User::class, $userId)->getAvatar());
    }

    public function testTryToUpdateUserWithIncludedAvatarWithExternalUrlWithMimeTypeNotAllowedForField(): void
    {
        $this->setIsStoredExternally(true);
        $this->setExternalFileAllowedUrlsRegExp('^http://example\\.org');
        $userId = $this->getReference('user_3')->getId();

        $data = [
            'data' => [
                'type' => 'users',
                'id' => (string)$userId,
                'relationships' => [
                    'avatar' => ['data' => ['type' => 'files', 'id' => 'new_avatar']]
                ]
            ],
            'included' => [
                [
                    'type' => 'files',
                    'id' => 'new_avatar',
                    'attributes' => ['externalUrl' => ExternalFileFactoryStub::FILE_A_TEST_URL]
                ]
            ]
        ];
        $response = $this->patch(['entity' => 'users', 'id' => (string)$userId], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title' => 'external file mime type constraint',
                'detail' => 'The MIME type of the file is invalid ("text/plain"). Allowed MIME types are '
                    . '"image/gif", "image/jpeg", "image/png", "image/webp".',
                'source' => ['pointer' => '/included/0/attributes/externalUrl']
            ],
            $response
        );

        $entityManager = $this->getEntityManager();
        $entityManager->clear();
        self::assertNull($entityManager->find(User::class, $userId)->getAvatar());
    }

    public function testCreateUserWithIncludedAvatarWithContentAllowedForField(): void
    {
        $data = [
            'data' => [
                'type' => 'users',
                'attributes' => [
                    'username' => 'user_with_avatar',
                    'firstName' => 'Bob',
                    'lastName' => 'Doo',
                    'email' => 'user_with_avatar@example.com'
                ],
                'relationships' => [
                    'avatar' => ['data' => ['type' => 'files', 'id' => 'new_avatar']]
                ]
            ],
            'included' => [
                [
                    'type' => 'files',
                    'id' => 'new_avatar',
                    'attributes' => [
                        'originalFilename' => 'blank.png',
                        'content' => self::BLANK_FILE_CONTENT
                    ]
                ]
            ]
        ];
        $response = $this->post(['entity' => 'users'], $data);

        /** @var User $user */
        $user = $this->getEntityManager()->find(User::class, (int)$this->getResourceId($response));
        $avatar = $user->getAvatar();

        self::assertSame('image/png', $avatar->getMimeType());
        self::assertSame(User::class, $avatar->getParentEntityClass());
        self::assertSame($user->getId(), $avatar->getParentEntityId());
        self::assertSame('avatar', $avatar->getParentEntityFieldName());
    }

    public function testTryToCreateUserWithIncludedAvatarUsedByAnotherIncludedUser(): void
    {
        $data = [
            'data' => [
                'type' => 'users',
                'attributes' => [
                    'username' => 'user_with_avatar',
                    'firstName' => 'Bob',
                    'lastName' => 'Doo',
                    'email' => 'user_with_avatar@example.com'
                ],
                'relationships' => [
                    'avatar' => ['data' => ['type' => 'files', 'id' => 'new_avatar']]
                ]
            ],
            'included' => [
                [
                    'type' => 'files',
                    'id' => 'new_avatar',
                    'attributes' => [
                        'originalFilename' => 'blank.png',
                        'content' => self::BLANK_FILE_CONTENT
                    ]
                ],
                [
                    'type' => 'users',
                    'id' => 'another_user',
                    'attributes' => [
                        'username' => 'another_user_with_avatar',
                        'firstName' => 'Rob',
                        'lastName' => 'Doo',
                        'email' => 'another_user_with_avatar@example.com'
                    ],
                    'relationships' => [
                        'avatar' => ['data' => ['type' => 'files', 'id' => 'new_avatar']]
                    ]
                ]
            ]
        ];
        $response = $this->post(['entity' => 'users'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title' => 'form constraint',
                'detail' => 'A new file cannot be used in several fields.',
                'source' => ['pointer' => '/data/relationships/avatar/data']
            ],
            $response
        );
    }

    public function testCreateDetectsMimeTypeFromContentAndIgnoresClientValue(): void
    {
        /** @var User $user */
        $user = $this->getReference('user_3');
        $userId = $user->getId();

        $data = [
            'data' => [
                'type' => 'files',
                'attributes' => [
                    'mimeType' => 'text/html',
                    'originalFilename' => 'photo.dat',
                    'content' => self::BLANK_FILE_CONTENT,
                    'parentFieldName' => 'avatar'
                ],
                'relationships' => [
                    'parent' => [
                        'data' => ['type' => 'users', 'id' => (string)$userId]
                    ]
                ]
            ]
        ];
        $response = $this->post(['entity' => 'files'], $data);

        $this->assertResponseContains(
            ['data' => ['type' => 'files', 'attributes' => ['mimeType' => 'image/png']]],
            $response
        );
        $file = $this->getEntityManager()->find(File::class, (int)$this->getResourceId($response));
        self::assertSame('image/png', $file->getMimeType());
        self::assertSame('png', $file->getExtension());
        self::assertSame('photo.dat', $file->getOriginalFilename());
    }

    public function testTryToUpdateMimeTypeIsIgnored(): void
    {
        $fileId = $this->getReference('file_1')->getId();

        $data = [
            'data' => [
                'type' => 'files',
                'id' => (string)$fileId,
                'attributes' => ['mimeType' => 'image/png']
            ]
        ];
        $response = $this->patch(['entity' => 'files', 'id' => (string)$fileId], $data);

        $this->assertResponseContains(
            ['data' => ['type' => 'files', 'attributes' => ['mimeType' => 'text/plain']]],
            $response
        );
        $file = $this->getEntityManager()->find(File::class, $fileId);
        self::assertSame('text/plain', $file->getMimeType());
    }

    public function testTryToUpdateAvatarWithContentNotAllowedForField(): void
    {
        $fileId = $this->getReference('file_1')->getId();

        $data = [
            'data' => [
                'type' => 'files',
                'id' => (string)$fileId,
                'attributes' => [
                    'originalFilename' => 'test.txt',
                    'content' => base64_encode('plain text content')
                ]
            ]
        ];
        $response = $this->patch(['entity' => 'files', 'id' => (string)$fileId], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title' => 'file constraint',
                'detail' => self::NOT_ALLOWED_AVATAR_MIME_TYPE_MESSAGE,
                'source' => ['pointer' => '/data/attributes/content']
            ],
            $response
        );
    }

    public function testUpdateAvatarWithContentAllowedForField(): void
    {
        $fileId = $this->getReference('file_1')->getId();
        $userId = $this->getReference('user_1')->getId();

        $data = [
            'data' => [
                'type' => 'files',
                'id' => (string)$fileId,
                'attributes' => [
                    'originalFilename' => 'blank.png',
                    'content' => self::BLANK_FILE_CONTENT
                ]
            ]
        ];
        $response = $this->patch(['entity' => 'files', 'id' => (string)$fileId], $data);

        $this->assertResponseContains(
            ['data' => ['type' => 'files', 'attributes' => ['mimeType' => 'image/png']]],
            $response
        );
        /** @var User $user */
        $user = $this->getEntityManager()->find(User::class, $userId);
        self::assertSame($fileId, $user->getAvatar()->getId());
    }

    public function testTryToUpdateUserWithIncludedAvatarWithContentNotAllowedForField(): void
    {
        $userId = $this->getReference('user_1')->getId();

        $data = [
            'data' => [
                'type' => 'users',
                'id' => (string)$userId,
                'relationships' => [
                    'avatar' => ['data' => ['type' => 'files', 'id' => 'new_avatar']]
                ]
            ],
            'included' => [
                [
                    'type' => 'files',
                    'id' => 'new_avatar',
                    'attributes' => [
                        'mimeType' => 'text/html',
                        'originalFilename' => 'test.html',
                        'content' => self::HTML_FILE_CONTENT
                    ]
                ]
            ]
        ];
        $response = $this->patch(['entity' => 'users', 'id' => (string)$userId], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title' => 'file constraint',
                'detail' => self::NOT_ALLOWED_AVATAR_HTML_MIME_TYPE_MESSAGE,
                'source' => ['pointer' => '/included/0/attributes/content']
            ],
            $response
        );
    }

    public function testUpdateUserWithIncludedAvatarWithParentIsTheSameUser(): void
    {
        $userId = $this->getReference('user_3')->getId();

        $data = [
            'data' => [
                'type' => 'users',
                'id' => (string)$userId,
                'relationships' => [
                    'avatar' => ['data' => ['type' => 'files', 'id' => 'new_avatar']]
                ]
            ],
            'included' => [
                [
                    'type' => 'files',
                    'id' => 'new_avatar',
                    'attributes' => [
                        'originalFilename' => 'blank.png',
                        'content' => self::BLANK_FILE_CONTENT,
                        'parentFieldName' => 'avatar'
                    ],
                    'relationships' => [
                        'parent' => ['data' => ['type' => 'users', 'id' => (string)$userId]]
                    ]
                ]
            ]
        ];
        $this->patch(['entity' => 'users', 'id' => (string)$userId], $data);

        $em = $this->getEntityManager();
        $em->clear();
        $avatar = $em->find(User::class, $userId)->getAvatar();
        self::assertSame('image/png', $avatar->getMimeType());
        self::assertSame(User::class, $avatar->getParentEntityClass());
        self::assertSame($userId, $avatar->getParentEntityId());
        self::assertSame('avatar', $avatar->getParentEntityFieldName());
    }

    public function testTryToUpdateUserWithIncludedAvatarWithParentIsAnotherUser(): void
    {
        $userId = $this->getReference('user_3')->getId();
        $anotherUserId = $this->getReference('user_2')->getId();
        $anotherUserAvatarId = $this->getReference('user_2')->getAvatar()->getId();

        $data = [
            'data' => [
                'type' => 'users',
                'id' => (string)$userId,
                'relationships' => [
                    'avatar' => ['data' => ['type' => 'files', 'id' => 'new_avatar']]
                ]
            ],
            'included' => [
                [
                    'type' => 'files',
                    'id' => 'new_avatar',
                    'attributes' => [
                        'originalFilename' => 'blank.png',
                        'content' => self::BLANK_FILE_CONTENT,
                        'parentFieldName' => 'avatar'
                    ],
                    'relationships' => [
                        'parent' => ['data' => ['type' => 'users', 'id' => (string)$anotherUserId]]
                    ]
                ]
            ]
        ];
        $response = $this->patch(['entity' => 'users', 'id' => (string)$userId], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title' => 'form constraint',
                'detail' => 'The parent of a new file should be omitted or should be the entity and the field'
                    . ' the file is added to.',
                'source' => ['pointer' => '/data/relationships/avatar/data']
            ],
            $response
        );

        $em = $this->getEntityManager();
        $em->clear();
        self::assertSame($anotherUserAvatarId, $em->find(User::class, $anotherUserId)->getAvatar()->getId());
        self::assertNull($em->find(User::class, $userId)->getAvatar());
    }

    public function testTryToCreateUserWithIncludedAvatarWithParentIsExistingUser(): void
    {
        $existingUserId = $this->getReference('user_3')->getId();

        $data = [
            'data' => [
                'type' => 'users',
                'attributes' => [
                    'username' => 'user_with_avatar',
                    'firstName' => 'Bob',
                    'lastName' => 'Doo',
                    'email' => 'user_with_avatar@example.com'
                ],
                'relationships' => [
                    'avatar' => ['data' => ['type' => 'files', 'id' => 'new_avatar']]
                ]
            ],
            'included' => [
                [
                    'type' => 'files',
                    'id' => 'new_avatar',
                    'attributes' => [
                        'originalFilename' => 'blank.png',
                        'content' => self::BLANK_FILE_CONTENT,
                        'parentFieldName' => 'avatar'
                    ],
                    'relationships' => [
                        'parent' => ['data' => ['type' => 'users', 'id' => (string)$existingUserId]]
                    ]
                ]
            ]
        ];
        $response = $this->post(['entity' => 'users'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title' => 'form constraint',
                'detail' => 'The parent of a new file should be omitted or should be the entity and the field'
                    . ' the file is added to.',
                'source' => ['pointer' => '/data/relationships/avatar/data']
            ],
            $response
        );

        $em = $this->getEntityManager();
        $em->clear();
        self::assertNull($em->find(User::class, $existingUserId)->getAvatar());
        self::assertNull($em->getRepository(User::class)->findOneBy(['username' => 'user_with_avatar']));
    }

    public function testTryToCreateWithContentNotAllowedByMimeTypesOfParentFieldConfig(): void
    {
        $this->setAvatarFieldOption('mimetypes', MimeTypesConverter::convertToString(['image/png']));

        $data = [
            'data' => [
                'type' => 'files',
                'attributes' => [
                    'originalFilename' => 'image.jpg',
                    'content' => base64_encode(file_get_contents(self::JPEG_FILE_PATH)),
                    'parentFieldName' => 'avatar'
                ],
                'relationships' => [
                    'parent' => [
                        'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                    ]
                ]
            ]
        ];
        $response = $this->post(['entity' => 'files'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title' => 'file constraint',
                'detail' => 'The MIME type of the file is invalid ("image/jpeg"). Allowed MIME types are "image/png".',
                'source' => ['pointer' => '/data/attributes/content']
            ],
            $response
        );
    }

    public function testTryToCreateWithContentExceedingMaxSizeOfParentFieldConfig(): void
    {
        $this->setAvatarFieldOption('maxsize', 0.00005);

        $data = [
            'data' => [
                'type' => 'files',
                'attributes' => [
                    'originalFilename' => 'blank.png',
                    'content' => self::BLANK_FILE_CONTENT,
                    'parentFieldName' => 'avatar'
                ],
                'relationships' => [
                    'parent' => [
                        'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                    ]
                ]
            ]
        ];
        $response = $this->post(['entity' => 'files'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title' => 'file constraint',
                'detail' => 'The file is too large (95 bytes). Allowed maximum size is 52 bytes.',
                'source' => ['pointer' => '/data/attributes/content']
            ],
            $response
        );
    }

    public function testTryToCreateFileForAttachmentWithContentNotAllowedForTargetEntity(): void
    {
        $this->loadAttachmentFixtures();

        $response = $this->post(
            ['entity' => 'files'],
            [
                'data' => [
                    'type' => 'files',
                    'attributes' => [
                        'originalFilename' => 'test.pdf',
                        'content' => base64_encode(file_get_contents(self::PDF_FILE_PATH)),
                        'parentFieldName' => 'file'
                    ],
                    'relationships' => [
                        'parent' => [
                            'data' => ['type' => 'attachments', 'id' => '<toString(@attachment1->id)>']
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'file constraint',
                'detail' => self::NOT_ALLOWED_ATTACHMENT_MIME_TYPE_MESSAGE,
                'source' => ['pointer' => '/data/attributes/content']
            ],
            $response
        );
    }

    public function testCreateFileForAttachmentWithContentAllowedForTargetEntityOnly(): void
    {
        $this->loadAttachmentFixtures();

        $response = $this->post(
            ['entity' => 'files'],
            [
                'data' => [
                    'type' => 'files',
                    'attributes' => [
                        'originalFilename' => 'image.svg',
                        'content' => base64_encode(file_get_contents(self::SVG_FILE_PATH)),
                        'parentFieldName' => 'file'
                    ],
                    'relationships' => [
                        'parent' => [
                            'data' => ['type' => 'attachments', 'id' => '<toString(@attachment1->id)>']
                        ]
                    ]
                ]
            ]
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'files', 'attributes' => ['mimeType' => 'image/svg+xml']]],
            $response
        );
    }

    public function testTryToCreateWithExternalUrlNoExternalFilesAllowed(): void
    {
        $this->setExternalFileAllowedUrlsRegExp('');

        $data = [
            'data' => [
                'type' => 'files',
                'attributes' => [
                    'externalUrl' => ExternalFileFactoryStub::IMAGE_A_TEST_URL,
                    'parentFieldName' => 'avatar'
                ],
                'relationships' => [
                    'parent' => [
                        'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                    ]
                ]
            ]
        ];
        $response = $this->post(['entity' => 'files'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title' => 'external file url constraint',
                'detail' => 'No external files and images are allowed.'
                    . ' Allowed URLs RegExp can be configured on the following page:'
                    . ' System -> Configuration -> General Setup -> Upload Settings.',
                'source' => ['pointer' => '/data/attributes/externalUrl']
            ],
            $response
        );
    }

    public function testTryToCreateWithNotFoundExternalUrl(): void
    {
        $this->setExternalFileAllowedUrlsRegExp('^http:\/\/example\.org');

        $data = [
            'data' => [
                'type' => 'files',
                'attributes' => [
                    'externalUrl' => ExternalFileFactoryStub::MISSING_URL
                ],
                'relationships' => [
                    'parent' => [
                        'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                    ]
                ]
            ]
        ];
        $response = $this->post(['entity' => 'files'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title' => 'external file url constraint',
                'detail' => 'The specified URL is not accessible. Reason: "Not Found"',
                'source' => ['pointer' => '/data/attributes/externalUrl']
            ],
            $response
        );
    }

    public function testTryToCreateWithExternalUrlNotMatchedAllowedRegex(): void
    {
        $this->setExternalFileAllowedUrlsRegExp('^http:\/\/valid\.domain');

        $data = [
            'data' => [
                'type' => 'files',
                'attributes' => [
                    'externalUrl' => ExternalFileFactoryStub::IMAGE_A_TEST_URL,
                    'parentFieldName' => 'avatar'
                ],
                'relationships' => [
                    'parent' => [
                        'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                    ]
                ]
            ]
        ];
        $response = $this->post(['entity' => 'files'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title' => 'external file url constraint',
                'detail' => 'The provided URL does not match the URLs allowed in the system configuration.',
                'source' => ['pointer' => '/data/attributes/externalUrl']
            ],
            $response
        );
    }

    public function testTryToCreateWithExternalUrlWithNotValidMimeType(): void
    {
        $this->setIsStoredExternally(true);
        $this->setExternalFileAllowedUrlsRegExp('^http://example\.org');

        $data = [
            'data' => [
                'type' => 'files',
                'attributes' => [
                    'externalUrl' => ExternalFileFactoryStub::FILE_A_TEST_URL,
                    'parentFieldName' => 'avatar'
                ],
                'relationships' => [
                    'parent' => [
                        'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                    ]
                ]
            ]
        ];
        $response = $this->post(['entity' => 'files'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title' => 'external file mime type constraint',
                'detail' => 'The MIME type of the file is invalid ("text/plain").',
                'source' => ['pointer' => '/data/attributes/externalUrl']
            ],
            $response
        );
    }

    public function testTryToCreateWithExternalUrlReturnsErrorWhenBothContentAndExternalUrl(): void
    {
        $this->setIsStoredExternally(true);
        $this->setExternalFileAllowedUrlsRegExp('^http:\/\/example\.org');

        $data = [
            'data' => [
                'type' => 'files',
                'attributes' => [
                    'externalUrl' => ExternalFileFactoryStub::IMAGE_A_TEST_URL,
                    'content' => 'sample data',
                    'originalFilename' => 'image-a.png',
                    'parentFieldName' => 'avatar'
                ],
                'relationships' => [
                    'parent' => [
                        'data' => ['type' => 'users', 'id' => '<toString(@user->id)>'],
                    ],
                ],
            ],
        ];
        $response = $this->post(['entity' => 'files'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title' => 'form constraint',
                'detail' => 'Either "externalUrl" or "content" must be specified, but not both'
            ],
            $response
        );
    }

    public function testTryToCreateWithContentForFieldStoredExternally(): void
    {
        $this->setIsStoredExternally(true);

        $data = [
            'data' => [
                'type' => 'files',
                'attributes' => [
                    'originalFilename' => 'blank.png',
                    'content' => self::BLANK_FILE_CONTENT,
                    'parentFieldName' => 'avatar'
                ],
                'relationships' => [
                    'parent' => [
                        'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                    ]
                ]
            ]
        ];
        $response = $this->post(['entity' => 'files'], $data, [], false);

        $this->assertResponseValidationError(
            [
                'title' => 'file field compatibility constraint',
                'detail' => 'The specified file is not compatible with the field Avatar that is configured to store'
                    . ' files externally.',
                'source' => ['pointer' => '/data/attributes/content']
            ],
            $response
        );
    }

    public function testCreateWithExternalUrl(): void
    {
        $this->setIsStoredExternally(true);
        $this->setExternalFileAllowedUrlsRegExp('^http:\/\/example\.org');

        $data = [
            'data' => [
                'type' => 'files',
                'attributes' => [
                    'externalUrl' => ExternalFileFactoryStub::IMAGE_A_TEST_URL,
                    'parentFieldName' => 'avatar'
                ],
                'relationships' => [
                    'parent' => [
                        'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                    ]
                ]
            ]
        ];
        $response = $this->post(['entity' => 'files'], $data);

        $expectedData = $data;
        $expectedData['data']['relationships']['owner']['data'] = [
            'type' => 'users',
            'id' => '<toString(@user->id)>'
        ];
        $this->assertResponseContains($expectedData, $response);

        $fileId = (int)$this->getResourceId($response);

        // test that the entity was created
        $entity = $this->getEntityManager()->find(File::class, $fileId);
        self::assertNotNull($entity);
    }

    public function testGetExternalUrl(): void
    {
        $fileId = $this->getReference('external_file_1')->getId();

        $response = $this->get(
            ['entity' => 'files', 'id' => (string)$fileId]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'files',
                    'id' => (string)$fileId,
                    'attributes' => [
                        'mimeType' => 'text/plain',
                        'originalFilename' => 'file_1.txt',
                        'fileSize' => 7,
                        'externalUrl' => ExternalFileFactoryStub::IMAGE_A_TEST_URL,
                        'parentFieldName' => 'avatar'
                    ],
                    'relationships' => [
                        'owner' => [
                            'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                        ],
                        'parent' => [
                            'data' => ['type' => 'users', 'id' => '<toString(@user_2->id)>']
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testUpdateExternalUrl(): void
    {
        $this->setIsStoredExternally(true);
        $this->setExternalFileAllowedUrlsRegExp('^http:\/\/example\.org');

        $fileId = $this->getReference('external_file_1')->getId();

        $data = [
            'data' => [
                'type' => 'files',
                'id' => (string)$fileId,
                'attributes' => [
                    'externalUrl' => ExternalFileFactoryStub::IMAGE_B_TEST_URL,
                    'parentFieldName' => 'avatar'
                ]
            ]
        ];
        $response = $this->patch(['entity' => 'files', 'id' => (string)$fileId], $data);

        $expectedData = [
            'data' => [
                'type' => 'files',
                'id' => (string)$fileId,
                'attributes' => [
                    'mimeType' => 'image/png',
                    'originalFilename' => 'image-b.png',
                    'fileSize' => 96,
                    'externalUrl' => ExternalFileFactoryStub::IMAGE_B_TEST_URL,
                    'parentFieldName' => 'avatar'
                ],
                'relationships' => [
                    'owner' => [
                        'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                    ],
                    'parent' => [
                        'data' => ['type' => 'users', 'id' => '<toString(@user_2->id)>']
                    ]
                ]
            ]
        ];
        $this->assertResponseContains($expectedData, $response);

        $response = $this->get(
            ['entity' => 'files', 'id' => (string)$fileId]
        );
        $this->assertResponseContains($expectedData, $response);
    }

    public function testDeleteExternalUrl(): void
    {
        $fileId = $this->getReference('external_file_1')->getId();

        $response = $this->delete(
            ['entity' => 'files', 'id' => (string)$fileId]
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_NO_CONTENT);

        // Checks that the File entity was deleted.
        $entity = $this->getEntityManager()->find(File::class, $fileId);
        self::assertNull($entity);
    }

    public function testTryToChangeParentEntity(): void
    {
        /** @var User $previousUser */
        $previousUser = $this->getReference('user_1');
        $previousUserId = $previousUser->getId();
        /** @var User $user */
        $user = $this->getReference('user_4');
        $userId = $user->getId();

        $fileId = $this->getReference('file_1')->getId();

        $data = [
            'data' => [
                'type' => 'files',
                'id' => (string)$fileId,
                'relationships' => [
                    'parent' => [
                        'data' => ['type' => 'users', 'id' => (string)$userId]
                    ]
                ]
            ]
        ];
        $response = $this->patch(['entity' => 'files', 'id' => (string)$fileId], $data);

        $expectedData = [
            'data' => [
                'type' => 'files',
                'id' => (string)$fileId,
                'attributes' => [
                    'mimeType' => 'text/plain',
                    'originalFilename' => 'file_1.txt',
                    'fileSize' => 7,
                    'externalUrl' => null,
                    'parentFieldName' => 'avatar'
                ],
                'relationships' => [
                    'owner' => [
                        'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                    ],
                    'parent' => [
                        'data' => ['type' => 'users', 'id' => (string)$previousUserId]
                    ]
                ]
            ]
        ];
        $this->assertResponseContains($expectedData, $response);

        // test that the linked to the parent entity was not changed
        $previousUser = $this->getEntityManager()->find(User::class, $previousUserId);
        self::assertInstanceOf(File::class, $previousUser->getAvatar());
        self::assertEquals($fileId, $previousUser->getAvatar()->getId());

        // test that the linked File entity was not removed
        $file = $this->getEntityManager()->find(File::class, $fileId);
        self::assertFalse(null === $file);

        // test that the parent entity was not changed
        self::assertEquals(User::class, $file->getParentEntityClass());
        self::assertEquals($previousUserId, $file->getParentEntityId());
    }

    public function testTryToChangeParentEntityFieldName(): void
    {
        /** @var User $user */
        $user = $this->getReference('user_1');
        $userId = $user->getId();

        $fileId = $this->getReference('file_1')->getId();

        $data = [
            'data' => [
                'type' => 'files',
                'id' => (string)$fileId,
                'attributes' => [
                    'parentFieldName' => 'other'
                ]
            ]
        ];
        $response = $this->patch(['entity' => 'files', 'id' => (string)$fileId], $data);

        $expectedData = [
            'data' => [
                'type' => 'files',
                'id' => (string)$fileId,
                'attributes' => [
                    'mimeType' => 'text/plain',
                    'originalFilename' => 'file_1.txt',
                    'fileSize' => 7,
                    'externalUrl' => null,
                    'parentFieldName' => 'avatar'
                ],
                'relationships' => [
                    'owner' => [
                        'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                    ],
                    'parent' => [
                        'data' => ['type' => 'users', 'id' => (string)$userId]
                    ]
                ]
            ]
        ];
        $this->assertResponseContains($expectedData, $response);

        // test that the linked to the parent entity was not changed
        $user = $this->getEntityManager()->find(User::class, $userId);
        self::assertInstanceOf(File::class, $user->getAvatar());
        self::assertEquals($fileId, $user->getAvatar()->getId());

        // test that the linked File entity was not removed
        $file = $this->getEntityManager()->find(File::class, $fileId);
        self::assertFalse(null === $file);

        // test that the parent entity field name was not changed
        self::assertEquals('avatar', $file->getParentEntityFieldName());
    }

    public function testTryToChangeParentEntityViaRelationship(): void
    {
        $response = $this->patchRelationship(
            ['entity' => 'files', 'id' => '<toString(@file_1->id)>', 'association' => 'parent'],
            ['data' => ['type' => 'users', 'id' => '<toString(@user_4->id)>']],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
