<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Tests\Functional\Stub\ExternalFileFactoryStub;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FileTest extends RestJsonApiTestCase
{
    private static $blankFileContent = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAAA'
    . '1BMVEUAAACnej3aAAAAAXRSTlMAQObYZgAAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII=';

    private string $externalFileAllowedUrlsRegExp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadUser::class]);
        $this->externalFileAllowedUrlsRegExp = $this->getExternalFileAllowedUrlsRegExp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if ($this->getExternalFileAllowedUrlsRegExp() !== $this->externalFileAllowedUrlsRegExp) {
            $this->setExternalFileAllowedUrlsRegExp($this->externalFileAllowedUrlsRegExp);
        }
        $this->externalFileAllowedUrlsRegExp = '';
        $this->setIsStoredExternally(false);
    }

    private function getExternalFileAllowedUrlsRegExp(): string
    {
        return (string)self::getConfigManager()->get('oro_attachment.external_file_allowed_urls_regexp');
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

    public function testCreate(): int
    {
        $data = [
            'data' => [
                'type'          => 'files',
                'attributes'    => [
                    'mimeType'         => 'image/png',
                    'originalFilename' => 'blank.png',
                    'fileSize'         => 95,
                    'content'          => self::$blankFileContent,
                    'parentFieldName'  => 'avatar'
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
            'id'   => '<toString(@user->id)>'
        ];
        $this->assertResponseContains($expectedData, $response);

        $fileId = (int)$this->getResourceId($response);

        // test that the entity was created
        $entity = $this->getEntityManager()->find(File::class, $fileId);
        self::assertNotNull($entity);

        // clear entity manager to not affect dependent tests
        $this->getEntityManager()->clear();

        return $fileId;
    }

    /**
     * @depends testCreate
     */
    public function testGet(int $fileId): void
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
                        'content'          => self::$blankFileContent,
                        'parentFieldName'  => 'avatar'
                    ],
                    'relationships' => [
                        'owner'  => [
                            'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                        ],
                        'parent' => [
                            'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToCreateWithoutOriginalFilename(): void
    {
        $data = [
            'data' => [
                'type'          => 'files',
                'attributes'    => [
                    'mimeType'         => 'image/png',
                    'fileSize'         => 95,
                    'content'          => self::$blankFileContent,
                    'parentFieldName'  => 'avatar'
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
                'title'  => 'form constraint',
                'detail' => 'The "content" field should be specified together with "originalFilename" field.'
            ],
            $response,
        );
    }

    public function testTryToCreateWithPathInOriginalFilename(): void
    {
        $data = [
            'data' => [
                'type'          => 'files',
                'attributes'    => [
                    'mimeType'         => 'image/png',
                    'originalFilename' => '/path/blank.png',
                    'fileSize'         => 95,
                    'content'          => self::$blankFileContent,
                    'parentFieldName'  => 'avatar'
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
                'title'  => 'filename without path constraint',
                'detail' => 'The file name should not have a path.',
                'source' => ['pointer' => '/data/attributes/originalFilename']
            ],
            $response,
        );
    }

    public function testTryToCreateWithExtraLongOriginalFilename(): void
    {
        $data = [
            'data' => [
                'type'          => 'files',
                'attributes'    => [
                    'mimeType'         => 'image/png',
                    'originalFilename' => 'Fuscebibendumleointemporhendreritmaurisestsemperodiovestibulumconguearcuera'
                        . 'tegeterateraesentacorcjustojrcivariusnatoquepenatibusetmagnisdisparturientmontesFuscebibend'
                        . 'umleointemporhendreritmaurisestsemperodiovestibulumconguearcuerategeterateraesentacorcjusto'
                        . 'jrcivariusnatoquepenatibusetmagnisdisparturientmontes.png',
                    'fileSize'         => 95,
                    'content'          => self::$blankFileContent,
                    'parentFieldName'  => 'avatar'
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
                'title'  => 'length constraint',
                'detail' => 'This value is too long. It should have 255 characters or less.',
                'source' => ['pointer' => '/data/attributes/originalFilename']
            ],
            $response,
        );
    }

    public function testTryToCreateWithInvalidContent(): void
    {
        $data = [
            'data' => [
                'type'          => 'files',
                'attributes'    => [
                    'mimeType'         => 'image/png',
                    'originalFilename' => 'blank.png',
                    'fileSize'         => 95,
                    'content'          => '0',
                    'parentFieldName'  => 'avatar'
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
                'title'  => 'form constraint',
                'detail' => 'Cannot decode content encoded with MIME base64.'
            ],
            $response,
        );
    }

    public function testTryToCreateWithExternalUrlNoExternalFilesAllowed(): void
    {
        $this->setExternalFileAllowedUrlsRegExp('');

        $data = [
            'data' => [
                'type'          => 'files',
                'attributes'    => [
                    'externalUrl'     => ExternalFileFactoryStub::IMAGE_A_TEST_URL,
                    'parentFieldName' => 'avatar',
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
                'title'  => 'external file url constraint',
                'detail' => 'No external files and images are allowed.'
                    . ' Allowed URLs RegExp can be configured on the following page:'
                    . ' System -> Configuration -> General Setup -> Upload Settings.',
                'source' => ['pointer' => '/data/attributes/externalUrl']
            ],
            $response,
        );
    }

    public function testTryToCreateWithNotFoundExternalUrl(): void
    {
        $this->setExternalFileAllowedUrlsRegExp('^http:\/\/example\.org');

        $url = 'http://example.org/missing.png';
        $data = [
            'data' => [
                'type'          => 'files',
                'attributes'    => [
                    'externalUrl' => $url,
                ],
                'relationships' => [
                    'parent' => [
                        'data' => ['type' => 'users', 'id' => '<toString(@user->id)>'],
                    ],
                ],
            ],
        ];
        $response = $this->post(
            ['entity' => 'files'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'external file url constraint',
                'detail' => 'The specified URL is not accessible. Reason: "Not Found"',
                'source' => ['pointer' => '/data/attributes/externalUrl']
            ],
            $response,
        );
    }

    public function testTryToCreateWithExternalUrlNotMatchedAllowedRegex(): void
    {
        $this->setExternalFileAllowedUrlsRegExp('^http:\/\/valid\.domain');

        $data = [
            'data' => [
                'type'          => 'files',
                'attributes'    => [
                    'externalUrl'     => ExternalFileFactoryStub::IMAGE_A_TEST_URL,
                    'parentFieldName' => 'avatar',
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
                'title'  => 'external file url constraint',
                'detail' => 'The provided URL does not match the URLs allowed in the system configuration.',
                'source' => ['pointer' => '/data/attributes/externalUrl']
            ],
            $response,
        );
    }

    public function testTryToCreateWithExternalUrlWithNotValidMimeType(): void
    {
        $this->setIsStoredExternally(true);
        $this->setExternalFileAllowedUrlsRegExp('^http://example\.org');

        $data = [
            'data' => [
                'type'          => 'files',
                'attributes'    => [
                    'externalUrl'     => ExternalFileFactoryStub::FILE_A_TEST_URL,
                    'parentFieldName' => 'avatar',
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
                'title'  => 'external file mime type constraint',
                'detail' => 'The MIME type of the file is invalid ("text/plain").'
                    . ' Allowed MIME types are "image/gif", "image/jpeg", "image/png", "image/webp".',
                'source' => ['pointer' => '/data/attributes/externalUrl']
            ],
            $response,
        );
    }

    public function testTryToCreateWithExternalUrlReturnsErrorWhenBothContentAndExternalUrl(): void
    {
        $this->setIsStoredExternally(true);
        $this->setExternalFileAllowedUrlsRegExp('^http:\/\/example\.org');

        $data = [
            'data' => [
                'type'          => 'files',
                'attributes'    => [
                    'externalUrl'      => ExternalFileFactoryStub::IMAGE_A_TEST_URL,
                    'content'          => 'sample data',
                    'originalFilename' => 'image-a.png',
                    'parentFieldName'  => 'avatar',
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
                'title'  => 'form constraint',
                'detail' => 'Either "externalUrl" or "content" must be specified, but not both'
            ],
            $response,
        );
    }

    public function testCreateWithExternalUrl(): int
    {
        $this->setIsStoredExternally(true);
        $this->setExternalFileAllowedUrlsRegExp('^http:\/\/example\.org');

        $data = [
            'data' => [
                'type'          => 'files',
                'attributes'    => [
                    'externalUrl'     => ExternalFileFactoryStub::IMAGE_A_TEST_URL,
                    'parentFieldName' => 'avatar',
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
            'id'   => '<toString(@user->id)>'
        ];
        $this->assertResponseContains($expectedData, $response);

        $fileId = (int)$this->getResourceId($response);

        // test that the entity was created
        $entity = $this->getEntityManager()->find(File::class, $fileId);
        self::assertNotNull($entity);

        // clear entity manager to not affect dependent tests
        $this->getEntityManager()->clear();

        return $fileId;
    }

    /**
     * @depends testCreateWithExternalUrl
     */
    public function testGetExternalUrl(int $fileId): void
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
                        'originalFilename' => 'image-a.png',
                        'fileSize'         => 95,
                        'externalUrl'      => ExternalFileFactoryStub::IMAGE_A_TEST_URL,
                        'parentFieldName'  => 'avatar',
                    ],
                    'relationships' => [
                        'owner'  => [
                            'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                        ],
                        'parent' => [
                            'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    /**
     * @depends testCreateWithExternalUrl
     */
    public function testUpdateExternalUrl(int $fileId): int
    {
        $this->setIsStoredExternally(true);
        $this->setExternalFileAllowedUrlsRegExp('^http:\/\/example\.org');

        $data = [
            'data' => [
                'type'       => 'files',
                'id'         => (string)$fileId,
                'attributes' => [
                    'externalUrl'     => ExternalFileFactoryStub::IMAGE_B_TEST_URL,
                    'parentFieldName' => 'avatar',
                ]
            ]
        ];
        $response = $this->patch(['entity' => 'files', 'id' => (string)$fileId], $data);

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'files',
                    'id'            => (string)$fileId,
                    'attributes'    => [
                        'mimeType'         => 'image/png',
                        'originalFilename' => 'image-b.png',
                        'fileSize'         => 96,
                        'externalUrl'      => ExternalFileFactoryStub::IMAGE_B_TEST_URL,
                        'parentFieldName'  => 'avatar',
                    ],
                    'relationships' => [
                        'owner'  => [
                            'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                        ],
                        'parent' => [
                            'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                        ]
                    ]
                ]
            ],
            $response
        );

        return $fileId;
    }

    /**
     * @depends testUpdateExternalUrl
     */
    public function testGetExternalUrlAfterPatch(int $fileId): void
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
                        'originalFilename' => 'image-b.png',
                        'fileSize'         => 96,
                        'externalUrl'      => ExternalFileFactoryStub::IMAGE_B_TEST_URL,
                        'parentFieldName'  => 'avatar',
                    ],
                    'relationships' => [
                        'owner'  => [
                            'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                        ],
                        'parent' => [
                            'data' => ['type' => 'users', 'id' => '<toString(@user->id)>']
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    /**
     * @depends testCreateWithExternalUrl
     */
    public function testDeleteExternalUrl(int $fileId): void
    {
        $response = $this->delete(
            ['entity' => 'files', 'id' => (string)$fileId]
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_NO_CONTENT);

        // Checks that the File entity was deleted.
        $entity = $this->getEntityManager()->find(File::class, $fileId);
        self::assertNull($entity);
    }
}
