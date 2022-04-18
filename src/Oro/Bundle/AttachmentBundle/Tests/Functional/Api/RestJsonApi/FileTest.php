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

    private string $externalFileAllowedUrlsRegExp = '';

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
        $this->toggleIsStoredExternally(false);
    }

    /**
     * @return string The file entity id
     */
    public function testPost(): string
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
        $response = $this->post(
            ['entity' => 'files'],
            $data
        );

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
     * @depends testPost
     *
     * @param string $fileId
     */
    public function testGet(string $fileId): void
    {
        $response = $this->get(
            ['entity' => 'files', 'id' => $fileId]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'files',
                    'id'            => $fileId,
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

    public function testPostExternalUrlNoExternalFilesAllowed(): void
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
                'detail' => 'No external files and images are allowed.'
                    . ' Allowed URLs RegExp can be configured on the following page:'
                    . ' System -> Configuration -> General Setup -> Upload Settings.'
            ],
            $response,
        );
    }

    public function testPostNotFoundExternalUrl(): void
    {
        $this->setExternalFileAllowedUrlsRegExp('^http:\/\/example\.org');

        $url = 'http://example.org/missing.png';
        $data = [
            'data' => [
                'type' => 'files',
                'attributes' => [
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
                'detail' => 'The specified URL is not accessible. Reason: "Not Found"',
            ],
            $response,
        );
    }

    public function testPostExternalUrlNotMatchedAllowedRegex(): void
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
                'detail' => 'The provided URL does not match the URLs allowed in the system configuration.'
            ],
            $response,
        );
    }

    public function testPostExternalUrlWithNotValidMimeType(): void
    {
        $this->toggleIsStoredExternally(true);
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
                'detail' => 'The MIME type of the file is invalid ("text/plain").'
                    . ' Allowed MIME types are "image/gif", "image/jpeg", "image/png", "image/webp".'
            ],
            $response,
        );

        $this->toggleIsStoredExternally(false);
    }

    private function toggleIsStoredExternally(bool $isStoredExternally): void
    {
        $entityConfigManager = $this->getContainer()->get('oro_entity_config.config_manager');
        $avatarFieldConfig = $entityConfigManager->getFieldConfig('attachment', User::class, 'avatar');
        $avatarFieldConfig->set('is_stored_externally', $isStoredExternally);
        $entityConfigManager->persist($avatarFieldConfig);
        $entityConfigManager->flush();
    }

    public function testPostExternalUrlReturnsErrorWhenBothContentAndExternalUrl(): void
    {
        $this->toggleIsStoredExternally(true);
        $this->setExternalFileAllowedUrlsRegExp('^http:\/\/example\.org');

        $data = [
            'data' => [
                'type' => 'files',
                'attributes' => [
                    'externalUrl' => ExternalFileFactoryStub::IMAGE_A_TEST_URL,
                    'content' => 'sample data',
                    'originalFilename' => 'image-a.png',
                    'parentFieldName' => 'avatar',
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
                'detail' => 'Either "externalUrl" or "content" must be specified, but not both'
            ],
            $response,
        );

        $this->toggleIsStoredExternally(false);
    }

    /**
     * @return string The file entity id
     */
    public function testPostExternalUrl(): string
    {
        $this->toggleIsStoredExternally(true);
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

        $this->toggleIsStoredExternally(false);

        // clear entity manager to not affect dependent tests
        $this->getEntityManager()->clear();

        return $fileId;
    }

    /**
     * @depends testPostExternalUrl
     *
     * @param string $fileId
     */
    public function testGetExternalUrl(string $fileId): void
    {
        $response = $this->get(
            ['entity' => 'files', 'id' => $fileId]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'files',
                    'id'            => $fileId,
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
     * @depends testPostExternalUrl
     *
     * @param string $fileId
     * @return string The file entity id
     */
    public function testPatchExternalUrl(string $fileId): string
    {
        $this->toggleIsStoredExternally(true);
        $this->setExternalFileAllowedUrlsRegExp('^http:\/\/example\.org');

        $data = [
            'data' => [
                'type'          => 'files',
                'id'            => $fileId,
                'attributes'    => [
                    'externalUrl'     => ExternalFileFactoryStub::IMAGE_B_TEST_URL,
                    'parentFieldName' => 'avatar',
                ]
            ]
        ];
        $response = $this->patch(['entity' => 'files', 'id' => $fileId], $data);

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'files',
                    'id'            => $fileId,
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

        $this->toggleIsStoredExternally(false);

        return $fileId;
    }

    /**
     * @depends testPatchExternalUrl
     *
     * @param string $fileId
     */
    public function testGetExternalUrlAfterPatch(string $fileId): void
    {
        $response = $this->get(
            ['entity' => 'files', 'id' => $fileId]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'files',
                    'id'            => $fileId,
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
     * @depends testPostExternalUrl
     *
     * @param string $fileId
     */
    public function testDeleteExternalUrl(string $fileId): void
    {
        $response = $this->delete(
            ['entity' => 'files', 'id' => $fileId]
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_NO_CONTENT);

        // Checks that the File entity was deleted.
        $entity = $this->getEntityManager()->find(File::class, $fileId);
        self::assertNull($entity);
    }

    private function getExternalFileAllowedUrlsRegExp(): bool
    {
        return (string)self::getConfigManager()->get('oro_attachment.external_file_allowed_urls_regexp');
    }

    private function setExternalFileAllowedUrlsRegExp(string $value): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_attachment.external_file_allowed_urls_regexp', $value);
        $configManager->flush();
    }
}
