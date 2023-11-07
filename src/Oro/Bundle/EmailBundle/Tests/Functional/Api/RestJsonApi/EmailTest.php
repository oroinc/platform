<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDepartment;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin;
use Oro\Bundle\EmailBundle\Model\FolderType;
use Oro\Bundle\EmailBundle\Tests\Functional\Api\DataFixtures\LoadEmailData;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadBusinessUnit;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class EmailTest extends RestJsonApiTestCase
{
    use RolePermissionExtension;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadEmailData::class, LoadBusinessUnit::class]);
        $this->updateRolePermissions(
            'ROLE_ADMINISTRATOR',
            EmailUser::class,
            [
                'VIEW'         => AccessLevel::GLOBAL_LEVEL,
                'VIEW_PRIVATE' => AccessLevel::LOCAL_LEVEL,
                'CREATE'       => AccessLevel::GLOBAL_LEVEL,
                'DELETE'       => AccessLevel::GLOBAL_LEVEL,
                'ASSIGN'       => AccessLevel::GLOBAL_LEVEL,
                'EDIT'         => AccessLevel::GLOBAL_LEVEL
            ]
        );

        $this->getOptionalListenerManager()->enableListener('oro_email.listener.entity_listener');
    }

    private function setAutoLinkAttachments(string $targetEntityClass, ?bool $value): ?bool
    {
        /** @var ConfigManager $configManager */
        $configManager = self::getContainer()->get('oro_entity_config.config_manager');
        $entityConfig = $configManager->getEntityConfig('attachment', $targetEntityClass);
        $previousValue = $entityConfig->get('auto_link_attachments');
        $entityConfig->set('auto_link_attachments', $value);
        $configManager->persist($entityConfig);
        $configManager->flush();

        return $previousValue;
    }

    private function findLinkedAttachment(string $targetEntityClass, int $targetEntityId): ?Attachment
    {
        /** @var AttachmentManager $attachmentManager */
        $attachmentManager = self::getContainer()->get('oro_attachment.manager');
        $attachmentTargets = $attachmentManager->getAttachmentTargets();

        return $this->getEntityManager()->getRepository(Attachment::class)->findOneBy([
            $attachmentTargets[$targetEntityClass] => $targetEntityId
        ]);
    }

    private function getActivityTargetIds(Email $email): array
    {
        $result = [];
        $targets = $email->getActivityTargets();
        foreach ($targets as $target) {
            $result[] = $target->getId();
        }
        sort($result);

        return $result;
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'emails']);
        $this->assertResponseContains('cget_email.yml', $response);
    }

    public function testGetListFilterById(): void
    {
        $response = $this->cget(
            ['entity' => 'emails'],
            ['filter[id]' => '<toString(@email_1->id)>']
        );
        $this->assertResponseContains('cget_email_filter_by_id.yml', $response);
    }

    public function testGetListFilterBySeveralIds(): void
    {
        $response = $this->cget(
            ['entity' => 'emails'],
            ['filter' => ['id' => ['<toString(@email_1->id)>', '<toString(@email_3->id)>']]]
        );
        $this->assertResponseContains('cget_email_filter_by_several_ids.yml', $response);
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'emails', 'id' => '<toString(@email_1->id)>'],
            ['include' => 'emailUsers,emailAttachments']
        );
        $this->assertResponseContains('get_email.yml', $response);

        // the "emailThreadContextItemId" meta property should be added to "activityTargets" for "create" action only
        $responseContent = self::jsonToArray($response->getContent());
        self::assertTrue(isset($responseContent['data']['relationships']['activityTargets']['data'][0]));
        self::assertFalse(isset($responseContent['data']['relationships']['activityTargets']['data'][0]['meta']));
    }

    public function testTryToCreateWithEmptyData(): void
    {
        $response = $this->post(
            ['entity' => 'emails'],
            ['data' => ['type' => 'emails']],
            [],
            false
        );
        $this->assertResponseValidationErrors(
            [
                [
                    'title'  => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/data/attributes/from']
                ],
                [
                    'title'  => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/data/attributes/subject']
                ],
                [
                    'title'  => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/data/attributes/sentAt']
                ],
                [
                    'title'  => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/data/attributes/internalDate']
                ],
                [
                    'title'  => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/data/attributes/messageId']
                ],
                [
                    'title'  => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/data/attributes/from/email']
                ]
            ],
            $response
        );
    }

    public function testTryToCreateWithEmptyImportance(): void
    {
        $data = $this->getRequestData('create_email_min.yml');
        $data['data']['attributes']['importance'] = '';
        $response = $this->post(['entity' => 'emails'], $data, [], false);
        $this->assertResponseValidationError(
            [
                'title'  => 'choice constraint',
                'detail' => 'The value you selected is not a valid choice.',
                'source' => ['pointer' => '/data/attributes/importance']
            ],
            $response
        );
    }

    public function testTryToCreateWithInvalidImportance(): void
    {
        $data = $this->getRequestData('create_email_min.yml');
        $data['data']['attributes']['importance'] = 'another';
        $response = $this->post(['entity' => 'emails'], $data, [], false);
        $this->assertResponseValidationError(
            [
                'title'  => 'choice constraint',
                'detail' => 'The value you selected is not a valid choice.',
                'source' => ['pointer' => '/data/attributes/importance']
            ],
            $response
        );
    }

    public function testTryToCreateWithEmptyEmailAddress(): void
    {
        $data = $this->getRequestData('create_email_min.yml');
        $data['data']['attributes']['from']['email'] = '';
        $data['data']['attributes']['toRecipients'][0]['email'] = '';
        $response = $this->post(['entity' => 'emails'], $data, [], false);
        $this->assertResponseValidationErrors(
            [
                [
                    'title'  => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/data/attributes/from/email']
                ],
                [
                    'title'  => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/data/attributes/toRecipients/0/email']
                ]
            ],
            $response
        );
    }

    public function testTryToCreateWithInvalidEmailAddress(): void
    {
        $data = $this->getRequestData('create_email_min.yml');
        $data['data']['attributes']['from']['email'] = 'test1';
        $data['data']['attributes']['toRecipients'][0]['email'] = 'test2';
        $response = $this->post(['entity' => 'emails'], $data, [], false);
        $this->assertResponseValidationErrors(
            [
                [
                    'title'  => 'email constraint',
                    'detail' => 'This value contains not valid email address.',
                    'source' => ['pointer' => '/data/attributes/from/email']
                ],
                [
                    'title'  => 'email constraint',
                    'detail' => 'This value contains not valid email address.',
                    'source' => ['pointer' => '/data/attributes/toRecipients/0/email']
                ]
            ],
            $response
        );
    }

    public function testTryToCreateWithEmptyBodyType(): void
    {
        $data = $this->getRequestData('create_email_min.yml');
        $data['data']['attributes']['body']['type'] = '';
        $data['data']['attributes']['body']['content'] = 'test';
        $response = $this->post(['entity' => 'emails'], $data, [], false);
        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/body/type']
            ],
            $response
        );
    }

    public function testTryToCreateWithInvalidBodyType(): void
    {
        $data = $this->getRequestData('create_email_min.yml');
        $data['data']['attributes']['body']['type'] = 'another';
        $data['data']['attributes']['body']['content'] = 'test';
        $response = $this->post(['entity' => 'emails'], $data, [], false);
        $this->assertResponseValidationError(
            [
                'title'  => 'choice constraint',
                'detail' => 'The value you selected is not a valid choice.',
                'source' => ['pointer' => '/data/attributes/body/type']
            ],
            $response
        );
    }

    public function testCreateWithEmptyBodyContent(): void
    {
        $data = $this->getRequestData('create_email_min.yml');
        $data['data']['attributes']['body']['type'] = 'text';
        $data['data']['attributes']['body']['content'] = '';
        $response = $this->post(['entity' => 'emails'], $data);
        $expectedData = $data;
        $expectedData['data']['attributes']['shortTextBody'] = '';
        $expectedData['data']['attributes']['bodySynced'] = true;
        unset(
            $expectedData['data']['attributes']['sentAt'],
            $expectedData['data']['attributes']['internalDate']
        );
        $this->assertResponseContains($expectedData, $response);
    }

    public function testCreateWithoutBodyContent(): void
    {
        $data = $this->getRequestData('create_email_min.yml');
        $data['data']['attributes']['body']['type'] = 'html';
        $response = $this->post(['entity' => 'emails'], $data);
        $expectedData = $data;
        $expectedData['data']['attributes']['shortTextBody'] = '';
        $expectedData['data']['attributes']['bodySynced'] = true;
        unset(
            $expectedData['data']['attributes']['sentAt'],
            $expectedData['data']['attributes']['internalDate']
        );
        $this->assertResponseContains($expectedData, $response);
    }

    public function testCreateWithRequiredDataOnly(): void
    {
        $response = $this->post(['entity' => 'emails'], 'create_email_min.yml');
        $createdEmail = $this->getEntityManager()->find(Email::class, (int)$this->getResourceId($response));
        $this->getReferenceRepository()->addReference('createdEmail', $createdEmail);
        $this->assertResponseContains('create_email_min.yml', $response);
    }

    public function testCreateWithRequiredDataOnlyWhenDefaultFolderExistsInEmailOrigin(): void
    {
        $em = $this->getEntityManager();
        $defaultFolder = new EmailFolder();
        $defaultFolder->setType(FolderType::OTHER);
        $defaultFolder->setName('Other');
        $defaultFolder->setFullName('Other');
        $em->persist($defaultFolder);
        $origin = new InternalEmailOrigin();
        $origin->setName(InternalEmailOrigin::BAP . '_API');
        $origin->addFolder($defaultFolder);
        $origin->setOrganization($this->getReference(LoadOrganization::ORGANIZATION));
        $origin->setOwner($this->getReference(LoadUser::USER));
        $em->persist($origin);
        $em->flush();
        $em->clear();

        $response = $this->post(['entity' => 'emails'], 'create_email_min.yml');
        $createdEmail = $this->getEntityManager()->find(Email::class, (int)$this->getResourceId($response));
        $this->getReferenceRepository()->addReference('createdEmail', $createdEmail);
        $this->assertResponseContains('create_email_min.yml', $response);

        // test that the existing default folder is reused
        $origin = $em->getRepository(InternalEmailOrigin::class)->findOneBy([
            'organization' => $this->getReference(LoadOrganization::ORGANIZATION),
            'owner'        => $this->getReference(LoadUser::USER),
            'internalName' => InternalEmailOrigin::BAP . '_API'
        ]);
        self::assertCount(1, $origin->getFolders());
        self::assertSame(
            $origin->getFolders()->first()->getId(),
            $createdEmail->getEmailUsers()->first()->getFolders()->first()->getId()
        );
    }

    public function testCreateWithAllData(): void
    {
        $data = $this->getRequestData('create_email.yml');
        $data['included'][0]['attributes']['private'] = false;
        $response = $this->post(['entity' => 'emails'], $data);
        $createdEmail = $this->getEntityManager()->find(Email::class, (int)$this->getResourceId($response));
        $this->getReferenceRepository()->addReference('createdEmail', $createdEmail);
        $expectedData = $this->getResponseData('create_email.yml');
        // the "private" field is read-only and its value is computed automatically
        $expectedData['included'][0]['attributes']['private'] = true;
        $this->assertResponseContains($expectedData, $response);
    }

    public function testCreateWithAttachmentWhenAutoLinkAttachmentsEnabled(): void
    {
        $em = $this->getEntityManager();
        $department = new TestDepartment();
        $department->setName('Test Department');
        $department->setOwner($this->getReference(LoadBusinessUnit::BUSINESS_UNIT));
        $em->persist($department);
        $em->flush();

        $data = $this->getRequestData('create_email.yml');
        $data['data']['relationships']['activityTargets']['data'][] = [
            'type' => $this->getEntityType(TestDepartment::class),
            'id'   => (string)$department->getId()
        ];

        $originalAutoLinkAttachmentsForUser = $this->setAutoLinkAttachments(TestDepartment::class, true);
        try {
            $response = $this->post(['entity' => 'emails'], $data);
        } finally {
            $this->setAutoLinkAttachments(TestDepartment::class, $originalAutoLinkAttachmentsForUser);
        }

        $createdEmail = $this->getEntityManager()->find(Email::class, (int)$this->getResourceId($response));
        $this->getReferenceRepository()->addReference('createdEmail', $createdEmail);
        $expectedData = $this->getResponseData('create_email.yml');
        $expectedData['data']['relationships']['activityTargets']['data'] = [
            [
                'type' => $this->getEntityType(TestDepartment::class),
                'id'   => (string)$department->getId()
            ]
        ];
        $this->assertResponseContains($expectedData, $response);

        /** @var EmailAttachment $emailAttachment */
        $emailAttachment = $createdEmail->getEmailBody()->getAttachments()->first();

        $linkedAttachment = $this->findLinkedAttachment(TestDepartment::class, $department->getId());
        self::assertNotNull($linkedAttachment);
        self::assertEquals($emailAttachment->getFileName(), $linkedAttachment->getFile()->getOriginalFilename());
        self::assertEquals($emailAttachment->getContentType(), $linkedAttachment->getFile()->getMimeType());
        self::assertEquals($emailAttachment->getFile()->getId(), $linkedAttachment->getFile()->getId());
    }

    public function testCreateWithAllDataWhenAttachmentHasRelationshipToEmail(): void
    {
        $data = $this->getRequestData('create_email.yml');
        $data['data']['id'] = 'new_email';
        unset($data['data']['relationships']['emailAttachments']);
        $data['included'][1]['relationships']['email']['data'] = ['type' => 'emails', 'id' => 'new_email'];
        $response = $this->post(['entity' => 'emails'], $data);
        $createdEmail = $this->getEntityManager()->find(Email::class, (int)$this->getResourceId($response));
        $this->getReferenceRepository()->addReference('createdEmail', $createdEmail);
        $this->assertResponseContains('create_email.yml', $response);
    }

    public function testCreateWithEmptyActivityTargets(): void
    {
        $data = $this->getRequestData('create_email_min.yml');
        $data['data']['relationships']['activityTargets']['data'] = [];
        $response = $this->post(['entity' => 'emails'], $data);
        $createdEmail = $this->getEntityManager()->find(Email::class, (int)$this->getResourceId($response));
        $this->getReferenceRepository()->addReference('createdEmail', $createdEmail);
        $expectedData = $this->getResponseData('create_email_min.yml');
        $expectedData['data']['relationships']['activityTargets']['data'] = [];
        $this->assertResponseContains($expectedData, $response);
        self::assertCount(0, $createdEmail->getActivityTargets());
    }

    public function testCreateWithActivityTargets(): void
    {
        $data = $this->getRequestData('create_email_min.yml');
        $data['data']['relationships']['activityTargets']['data'] = [
            ['type' => 'users', 'id' => '<toString(@user->id)>']
        ];
        $response = $this->post(['entity' => 'emails'], $data);
        $createdEmail = $this->getEntityManager()->find(Email::class, (int)$this->getResourceId($response));
        $this->getReferenceRepository()->addReference('createdEmail', $createdEmail);
        $expectedData = $this->getResponseData('create_email_min.yml');
        $expectedData['data']['relationships']['activityTargets']['data'] = [
            ['type' => 'users', 'id' => '<toString(@user->id)>']
        ];
        $this->assertResponseContains($expectedData, $response);
        self::assertCount(1, $createdEmail->getActivityTargets());
    }

    public function testCreateWithComments(): void
    {
        $data = $this->getRequestData('create_email_min.yml');
        $data['data']['relationships']['comments']['data'] = [
            ['type' => 'comments', 'id' => 'new_comment']
        ];
        $data['included'][] = [
            'type'       => 'comments',
            'id'         => 'new_comment',
            'attributes' => [
                'message' => 'Some comment'
            ]
        ];
        $response = $this->post(['entity' => 'emails'], $data);
        $createdEmail = $this->getEntityManager()->find(Email::class, (int)$this->getResourceId($response));
        $this->getReferenceRepository()->addReference('createdEmail', $createdEmail);
        $this->assertResponseContains('create_email_min.yml', $response);
        $responseContent = self::jsonToArray($response->getContent());
        self::assertCount(1, $responseContent['data']['relationships']['comments']['data']);
    }

    public function testTryToCreateWithInvalidFolderType(): void
    {
        $data = $this->getRequestData('create_email.yml');
        $data['included'][0]['attributes']['folders'][0]['type'] = 'invalid';
        $response = $this->post(['entity' => 'emails'], $data, [], false);
        $this->assertResponseValidationError(
            [
                'title'  => 'choice constraint',
                'detail' => 'The value you selected is not a valid choice.',
                'source' => ['pointer' => '/included/0/attributes/folders/0/type']
            ],
            $response
        );
    }

    public function testTryToCreateWithEmptyFolderData(): void
    {
        $data = $this->getRequestData('create_email.yml');
        $data['included'][0]['attributes']['folders'][0]['type'] = '';
        $data['included'][0]['attributes']['folders'][0]['name'] = '';
        $data['included'][0]['attributes']['folders'][0]['path'] = '';
        $response = $this->post(['entity' => 'emails'], $data, [], false);
        $this->assertResponseValidationErrors(
            [
                [
                    'title'  => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/included/0/attributes/folders/0/type']
                ],
                [
                    'title'  => 'choice constraint',
                    'detail' => 'The value you selected is not a valid choice.',
                    'source' => ['pointer' => '/included/0/attributes/folders/0/type']
                ],
                [
                    'title'  => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/included/0/attributes/folders/0/name']
                ],
                [
                    'title'  => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/included/0/attributes/folders/0/path']
                ]
            ],
            $response
        );
    }

    public function testTryToCreateWithoutFolders(): void
    {
        $data = $this->getRequestData('create_email.yml');
        unset($data['included'][0]['attributes']['folders']);
        $response = $this->post(['entity' => 'emails'], $data, [], false);
        $this->assertResponseValidationError(
            [
                'title'  => 'count constraint',
                'detail' => 'This collection should contain 1 element or more.',
                'source' => ['pointer' => '/included/0/attributes/folders']
            ],
            $response
        );
    }

    public function testTryToCreateWithNullContentEncodingForAttachment(): void
    {
        $data = $this->getRequestData('create_email.yml');
        $data['included'][1]['attributes']['contentEncoding'] = null;
        $response = $this->post(['entity' => 'emails'], $data, [], false);
        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/included/1/attributes/contentEncoding']
            ],
            $response
        );
    }

    public function testTryToCreateWhenContentEncodingForAttachmentIsNotSpecified(): void
    {
        $data = $this->getRequestData('create_email.yml');
        unset($data['included'][1]['attributes']['contentEncoding']);
        $response = $this->post(['entity' => 'emails'], $data, [], false);
        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/included/1/attributes/contentEncoding']
            ],
            $response
        );
    }

    public function testTryToCreateWithAttachmentButWithoutBody(): void
    {
        $data = $this->getRequestData('create_email.yml');
        unset($data['data']['attributes']['body']);
        $response = $this->post(['entity' => 'emails'], $data, [], false);
        $this->assertResponseValidationError(
            [
                'title'  => 'email attachment without body constraint',
                'detail' => 'The attachments cannot be added to an email without a body.',
                'source' => ['pointer' => '/data/attributes/body']
            ],
            $response
        );
    }

    public function testSetBody(): void
    {
        $emailId = $this->getReference('email_2')->getId();
        $data = [
            'data' => [
                'type'       => 'emails',
                'id'         => (string)$emailId,
                'attributes' => [
                    'body' => [
                        'type'    => 'html',
                        'content' => 'Test <b>Body</b>'
                    ]
                ]
            ]
        ];
        $response = $this->patch(['entity' => 'emails', 'id' => (string)$emailId], $data);
        $expectedData = $data;
        $expectedData['data']['attributes']['shortTextBody'] = 'Test Body';
        $expectedData['data']['attributes']['bodySynced'] = true;
        $this->assertResponseContains($expectedData, $response);
    }

    public function testSetBodyWithAttachment(): void
    {
        $emailId = $this->getReference('email_2')->getId();
        $data = [
            'data'     => [
                'type'          => 'emails',
                'id'            => (string)$emailId,
                'attributes'    => [
                    'body' => [
                        'type'    => 'html',
                        'content' => 'Test <b>Body</b>'
                    ]
                ],
                'relationships' => [
                    'emailAttachments' => [
                        'data' => [
                            ['type' => 'emailattachments', 'id' => 'email_attachment_1']
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type'       => 'emailattachments',
                    'id'         => 'email_attachment_1',
                    'attributes' => [
                        'fileName'        => 'test.jpg',
                        'contentType'     => 'image/jpeg',
                        'contentEncoding' => 'base64',
                        'content'         => LoadEmailData::ENCODED_ATTACHMENT_CONTENT
                    ]
                ]
            ]
        ];
        $response = $this->patch(['entity' => 'emails', 'id' => (string)$emailId], $data);
        $email = $this->getEntityManager()->find(Email::class, $emailId);
        $body = $email->getEmailBody();
        /** @var EmailAttachment $attachment */
        $attachment = $body->getAttachments()->first();
        self::assertSame($body->getId(), $attachment->getEmailBody()->getId());
        $expectedData = $data;
        $expectedData['data']['attributes']['shortTextBody'] = 'Test Body';
        $expectedData['data']['attributes']['bodySynced'] = true;
        $expectedData['data']['relationships']['emailAttachments']['data'][0]['id'] = (string)$attachment->getId();
        $expectedData['included'][0]['id'] = (string)$attachment->getId();
        $this->assertResponseContains($expectedData, $response);
    }

    public function testSetBodyWithAttachmentWhenAutoLinkAttachmentsEnabled(): void
    {
        /** @var Email $email */
        $email = $this->getReference('email_2');
        $em = $this->getEntityManager();
        $department = new TestDepartment();
        $department->setName('Test Department');
        $department->setOwner($this->getReference(LoadBusinessUnit::BUSINESS_UNIT));
        $em->persist($department);
        /** @var ActivityManager $activityManager */
        $activityManager = self::getContainer()->get('oro_activity.manager');
        $activityManager->addActivityTarget($email, $department);
        $em->flush();

        $originalAutoLinkAttachmentsForUser = $this->setAutoLinkAttachments(TestDepartment::class, true);
        try {
            $this->testSetBodyWithAttachment();
        } finally {
            $this->setAutoLinkAttachments(TestDepartment::class, $originalAutoLinkAttachmentsForUser);
        }

        /** @var EmailAttachment $emailAttachment */
        $emailAttachment = $email->getEmailBody()->getAttachments()->first();

        $linkedAttachment = $this->findLinkedAttachment(TestDepartment::class, $department->getId());
        self::assertNotNull($linkedAttachment);
        self::assertEquals($emailAttachment->getFileName(), $linkedAttachment->getFile()->getOriginalFilename());
        self::assertEquals($emailAttachment->getContentType(), $linkedAttachment->getFile()->getMimeType());
        self::assertEquals($emailAttachment->getFile()->getId(), $linkedAttachment->getFile()->getId());
    }

    public function testSetBodyWithAttachmentWhenAttachmentHasRelationshipToEmail(): void
    {
        $emailId = $this->getReference('email_2')->getId();
        $data = [
            'data'     => [
                'type'       => 'emails',
                'id'         => (string)$emailId,
                'attributes' => [
                    'body' => [
                        'type'    => 'html',
                        'content' => 'Test <b>Body</b>'
                    ]
                ]
            ],
            'included' => [
                [
                    'type'          => 'emailattachments',
                    'id'            => 'email_attachment_1',
                    'attributes'    => [
                        'fileName'        => 'test.jpg',
                        'contentType'     => 'image/jpeg',
                        'contentEncoding' => 'base64',
                        'content'         => LoadEmailData::ENCODED_ATTACHMENT_CONTENT
                    ],
                    'relationships' => [
                        'email' => [
                            'data' => ['type' => 'emails', 'id' => (string)$emailId]
                        ]
                    ]
                ]
            ]
        ];
        $response = $this->patch(['entity' => 'emails', 'id' => (string)$emailId], $data);
        $email = $this->getEntityManager()->find(Email::class, $emailId);
        $body = $email->getEmailBody();
        /** @var EmailAttachment $attachment */
        $attachment = $body->getAttachments()->first();
        self::assertSame($body->getId(), $attachment->getEmailBody()->getId());
        $expectedData = $data;
        $expectedData['data']['attributes']['shortTextBody'] = 'Test Body';
        $expectedData['data']['attributes']['bodySynced'] = true;
        $expectedData['data']['relationships']['emailAttachments']['data'][0]['id'] = (string)$attachment->getId();
        $expectedData['included'][0]['id'] = (string)$attachment->getId();
        $this->assertResponseContains($expectedData, $response);
    }

    public function testUnsetBodyWhenItDoesNotExist(): void
    {
        $emailId = $this->getReference('email_2')->getId();
        $data = [
            'data' => [
                'type'       => 'emails',
                'id'         => (string)$emailId,
                'attributes' => [
                    'body' => null
                ]
            ]
        ];
        $response = $this->patch(['entity' => 'emails', 'id' => (string)$emailId], $data);
        $expectedData = $data;
        $expectedData['data']['attributes']['shortTextBody'] = null;
        $expectedData['data']['attributes']['bodySynced'] = false;
        $this->assertResponseContains($expectedData, $response);
    }

    public function testTryToSetBodyWhenItAlreadyExists(): void
    {
        $emailId = $this->getReference('email_1')->getId();
        $response = $this->patch(
            ['entity' => 'emails', 'id' => (string)$emailId],
            [
                'data' => [
                    'type'       => 'emails',
                    'id'         => (string)$emailId,
                    'attributes' => [
                        'body' => [
                            'content' => 'Updated Body'
                        ]
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'value constraint',
                'detail' => 'It is not allowed to change the body.',
                'source' => ['pointer' => '/data/attributes/body']
            ],
            $response
        );
    }

    public function testTryToChangeBodyType(): void
    {
        $emailId = $this->getReference('email_1')->getId();
        $response = $this->patch(
            ['entity' => 'emails', 'id' => (string)$emailId],
            [
                'data' => [
                    'type'       => 'emails',
                    'id'         => (string)$emailId,
                    'attributes' => [
                        'body' => [
                            'type' => 'html'
                        ]
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'value constraint',
                'detail' => 'It is not allowed to change the body.',
                'source' => ['pointer' => '/data/attributes/body']
            ],
            $response
        );
    }

    public function testTryToUnsetBodyWhenItAlreadyExists(): void
    {
        $emailId = $this->getReference('email_1')->getId();
        $response = $this->patch(
            ['entity' => 'emails', 'id' => (string)$emailId],
            [
                'data' => [
                    'type'       => 'emails',
                    'id'         => (string)$emailId,
                    'attributes' => [
                        'body' => null
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'value constraint',
                'detail' => 'It is not allowed to remove the body.',
                'source' => ['pointer' => '/data/attributes/body']
            ],
            $response
        );
    }

    public function testTryToAddAttachmentToExistingEmailWithoutBody(): void
    {
        $emailId = $this->getReference('email_2')->getId();
        $data = [
            'data'     => [
                'type'          => 'emails',
                'id'            => (string)$emailId,
                'relationships' => [
                    'emailAttachments' => [
                        'data' => [
                            ['type' => 'emailattachments', 'id' => 'email_attachment_1']
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type'       => 'emailattachments',
                    'id'         => 'email_attachment_1',
                    'attributes' => [
                        'fileName'        => 'test.jpg',
                        'contentType'     => 'image/jpeg',
                        'contentEncoding' => 'base64',
                        'content'         => LoadEmailData::ENCODED_ATTACHMENT_CONTENT
                    ]
                ]
            ]
        ];
        $response = $this->patch(['entity' => 'emails', 'id' => (string)$emailId], $data, [], false);
        $this->assertResponseValidationError(
            [
                'title'  => 'email attachment without body constraint',
                'detail' => 'The attachments cannot be added to an email without a body.',
                'source' => ['pointer' => '/data/attributes/body']
            ],
            $response
        );
    }

    public function testTryToAddAttachmentToExistingEmailWithBody(): void
    {
        $emailId = $this->getReference('email_1')->getId();
        $data = [
            'data'     => [
                'type'          => 'emails',
                'id'            => (string)$emailId,
                'relationships' => [
                    'emailAttachments' => [
                        'data' => [
                            ['type' => 'emailattachments', 'id' => 'email_attachment_1']
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type'       => 'emailattachments',
                    'id'         => 'email_attachment_1',
                    'attributes' => [
                        'fileName'        => 'test.jpg',
                        'contentType'     => 'image/jpeg',
                        'contentEncoding' => 'base64',
                        'content'         => LoadEmailData::ENCODED_ATTACHMENT_CONTENT
                    ]
                ]
            ]
        ];
        $response = $this->patch(['entity' => 'emails', 'id' => (string)$emailId], $data, [], false);
        $this->assertResponseValidationError(
            [
                'title'  => 'email attachment without body constraint',
                'detail' => 'The attachments cannot be added to an email without a body.',
                'source' => ['pointer' => '/data/attributes/body']
            ],
            $response
        );
    }

    public function testUpdateEmailUser(): void
    {
        $emailId = $this->getReference('email_1')->getId();
        $data = [
            'data'     => [
                'type' => 'emails',
                'id'   => (string)$emailId
            ],
            'included' => [
                [
                    'type'          => 'emailusers',
                    'id'            => '<toString(@emailUser_1->id)>',
                    'meta'          => ['update' => true],
                    'attributes'    => [
                        'seen' => true
                    ],
                    'relationships' => [
                        'email' => [
                            'data' => ['type' => 'emails', 'id' => (string)$emailId]
                        ]
                    ]
                ]
            ]
        ];
        $response = $this->patch(['entity' => 'emails', 'id' => (string)$emailId], $data);
        $expectedData = $data;
        unset($expectedData['included'][0]['meta']);
        $this->assertResponseContains($expectedData, $response);
        $email = $this->getEntityManager()->find(Email::class, $emailId);
        self::assertCount(1, $email->getEmailUsers());
        self::assertTrue($email->getEmailUsers()->first()->isSeen());
    }

    public function testTryToAddEmailUser(): void
    {
        $emailId = $this->getReference('email_1')->getId();
        $response = $this->patch(
            ['entity' => 'emails', 'id' => (string)$emailId],
            [
                'data'     => [
                    'type'          => 'emails',
                    'id'            => (string)$emailId,
                    'relationships' => [
                        'emailUsers' => [
                            'data' => [
                                ['type' => 'emailusers', 'id' => '<toString(@emailUser_1->id)>'],
                                ['type' => 'emailusers', 'id' => 'email_user_2']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'          => 'emailusers',
                        'id'            => 'email_user_2',
                        'attributes'    => [
                            'receivedAt' => '2022-05-01T15:00:00Z',
                            'folders'    => [['type' => 'inbox', 'name' => 'Inbox', 'path' => 'Inbox']]
                        ],
                        'relationships' => [
                            'organization' => [
                                'data' => ['type' => 'organizations', 'id' => '<toString(@organization->id)>']
                            ],
                            'owner'        => [
                                'data' => ['type' => 'users', 'id' => '<toString(@user1->id)>']
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
                'title'  => 'request data constraint',
                'detail' => 'An email user cannot be created via email API resource.'
                    . ' Use API resource to create an email user.',
                'source' => ['pointer' => '/included/0']
            ],
            $response
        );
    }

    public function testTryToChangeEmailUsers(): void
    {
        $emailId = $this->getReference('email_1')->getId();
        $response = $this->patch(
            ['entity' => 'emails', 'id' => (string)$emailId],
            [
                'data' => [
                    'type'          => 'emails',
                    'id'            => (string)$emailId,
                    'relationships' => [
                        'emailUsers' => [
                            'data' => [
                                ['type' => 'emailusers', 'id' => '<toString(@emailUser_1->id)>'],
                                ['type' => 'emailusers', 'id' => '<toString(@emailUser_3->id)>']
                            ]
                        ]
                    ]
                ]
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'emails',
                    'id'            => (string)$emailId,
                    'relationships' => [
                        'emailUsers' => [
                            'data' => [
                                ['type' => 'emailusers', 'id' => '<toString(@emailUser_1->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
        $email = $this->getEntityManager()->find(Email::class, $emailId);
        self::assertCount(1, $email->getEmailUsers());
        self::assertSame($this->getReference('emailUser_1')->getId(), $email->getEmailUsers()->first()->getId());
    }

    public function testUpdateActivityTargets(): void
    {
        $emailId = $this->getReference('email_1')->getId();
        $data = [
            'data' => [
                'type'          => 'emails',
                'id'            => (string)$emailId,
                'relationships' => [
                    'activityTargets' => [
                        'data' => [
                            ['type' => 'users', 'id' => '<toString(@user->id)>'],
                            ['type' => 'users', 'id' => '<toString(@user1->id)>']
                        ]
                    ]
                ]
            ]
        ];
        $response = $this->patch(['entity' => 'emails', 'id' => (string)$emailId], $data);
        $this->assertResponseContains($data, $response);
        $email = $this->getEntityManager()->find(Email::class, $emailId);
        self::assertCount(2, $email->getActivityTargets());

        // the "emailThreadContextItemId" meta property should be added to "activityTargets" for "create" action only
        $responseContent = self::jsonToArray($response->getContent());
        self::assertTrue(isset($responseContent['data']['relationships']['activityTargets']['data'][0]));
        self::assertFalse(isset($responseContent['data']['relationships']['activityTargets']['data'][0]['meta']));
    }

    public function testGetSubresourceForActivityTargets(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'emails', 'id' => '<toString(@email_1->id)>', 'association' => 'activityTargets']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'users',
                        'id'         => '<toString(@user->id)>',
                        'attributes' => [
                            'username' => '<toString(@user->username)>'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetSubresourceForActivityTargetsWithIncludeFilter(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'emails', 'id' => '<toString(@email_1->id)>', 'association' => 'activityTargets'],
            ['include' => 'userRoles']
        );
        $this->assertResponseContains(
            [
                'data'     => [
                    [
                        'type'       => 'users',
                        'id'         => '<toString(@user->id)>',
                        'attributes' => [
                            'username' => '<toString(@user->username)>'
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'       => 'userroles',
                        'id'         => '<toString(@ROLE_ADMINISTRATOR->id)>',
                        'attributes' => [
                            'role' => '<toString(@ROLE_ADMINISTRATOR->role)>'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForActivityTargets(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'emails', 'id' => '<toString(@email_1->id)>', 'association' => 'activityTargets']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'users', 'id' => '<toString(@user->id)>']
                ]
            ],
            $response,
            true
        );
    }

    public function testUpdateRelationshipForActivityTargets(): void
    {
        $emailId = $this->getReference('email_1')->getId();
        $targetEntityId = $this->getReference('user1')->getId();
        $this->patchRelationship(
            ['entity' => 'emails', 'id' => (string)$emailId, 'association' => 'activityTargets'],
            [
                'data' => [
                    ['type' => 'users', 'id' => (string)$targetEntityId]
                ]
            ]
        );
        /** @var Email $email */
        $email = $this->getEntityManager()->find(Email::class, $emailId);
        self::assertEquals([$targetEntityId], $this->getActivityTargetIds($email));
    }

    public function testAddRelationshipForActivityTargets(): void
    {
        $emailId = $this->getReference('email_1')->getId();
        $targetEntity1Id = $this->getReference('user')->getId();
        $targetEntity2Id = $this->getReference('user1')->getId();
        $response = $this->postRelationship(
            ['entity' => 'emails', 'id' => (string)$emailId, 'association' => 'activityTargets'],
            [
                'data' => [
                    ['type' => 'users', 'id' => (string)$targetEntity2Id]
                ]
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'users',
                        'id'   => (string)$targetEntity1Id,
                        'meta' => [
                            'emailThreadContextItemId' => 'users-' . $targetEntity1Id . '-' . $emailId
                        ]
                    ],
                    [
                        'type' => 'users',
                        'id'   => (string)$targetEntity2Id,
                        'meta' => [
                            'emailThreadContextItemId' => 'users-' . $targetEntity2Id . '-' . $emailId
                        ]
                    ]
                ]
            ],
            $response
        );
        /** @var Email $email */
        $email = $this->getEntityManager()->find(Email::class, $emailId);
        self::assertEquals([$targetEntity1Id, $targetEntity2Id], $this->getActivityTargetIds($email));
    }

    public function testDeleteRelationshipForActivityTargets(): void
    {
        $emailId = $this->getReference('email_1')->getId();
        $this->deleteRelationship(
            ['entity' => 'emails', 'id' => (string)$emailId, 'association' => 'activityTargets'],
            [
                'data' => [
                    ['type' => 'users', 'id' => '<toString(@user->id)>']
                ]
            ]
        );
        /** @var Email $email */
        $email = $this->getEntityManager()->find(Email::class, $emailId);
        self::assertEquals([], $this->getActivityTargetIds($email));
    }
}
