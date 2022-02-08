<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Tests\Functional\DocumentationTestTrait;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

/**
 * @group regression
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AttachmentDocumentationTest extends RestJsonApiTestCase
{
    use DocumentationTestTrait;

    /** @var string used in DocumentationTestTrait */
    private const VIEW = 'rest_json_api';

    private static bool $isDocumentationCacheWarmedUp = false;

    protected function setUp(): void
    {
        parent::setUp();
        if (!self::$isDocumentationCacheWarmedUp) {
            $this->warmUpDocumentationCache();
            self::$isDocumentationCacheWarmedUp = true;
        }
    }

    public function testAttachmentTarget(): void
    {
        $docs = $this->getEntityDocsForAction('attachments', ApiAction::GET);
        $resourceData = $this->getResourceData($this->getSimpleFormatter()->format($docs));
        self::assertEquals(
            '<p>A record which the attachment record belongs to.</p>',
            $resourceData['response']['target']['description']
        );
    }

    public function testAttachmentTargetForCreate(): void
    {
        $docs = $this->getEntityDocsForAction('attachments', ApiAction::CREATE);
        $resourceData = $this->getResourceData($this->getSimpleFormatter()->format($docs));
        self::assertEquals(
            '<p>A record which the attachment record belongs to.</p>'
            . '<p><strong>The required field.</strong></p>',
            $resourceData['parameters']['target']['description']
        );
    }

    public function testAttachmentTargetForUpdate(): void
    {
        $docs = $this->getEntityDocsForAction('attachments', ApiAction::UPDATE);
        $resourceData = $this->getResourceData($this->getSimpleFormatter()->format($docs));
        self::assertEquals(
            '<p>A record which the attachment record belongs to.</p>'
            . '<p><strong>This field must not be empty, if it is passed.</strong></p>',
            $resourceData['parameters']['target']['description']
        );
    }

    public function testTargetEntityAttachments(): void
    {
        $docs = $this->getEntityDocsForAction('testapidepartments', ApiAction::GET);
        $resourceData = $this->getResourceData($this->getSimpleFormatter()->format($docs));
        self::assertEquals(
            '<p>The attachments associated with the test department record.</p>',
            $resourceData['response']['attachments']['description']
        );
    }

    public function testTargetEntityAttachmentsForCreate(): void
    {
        $docs = $this->getEntityDocsForAction('testapidepartments', ApiAction::CREATE);
        $resourceData = $this->getResourceData($this->getSimpleFormatter()->format($docs));
        self::assertEquals(
            '<p>The attachments associated with the test department record.</p>',
            $resourceData['parameters']['attachments']['description']
        );
    }

    public function testTargetEntityAttachmentsForUpdate(): void
    {
        $docs = $this->getEntityDocsForAction('testapidepartments', ApiAction::UPDATE);
        $resourceData = $this->getResourceData($this->getSimpleFormatter()->format($docs));
        self::assertEquals(
            '<p>The attachments associated with the test department record.</p>'
            . '<p><strong>The read-only field. A passed value will be ignored.</strong></p>',
            $resourceData['parameters']['attachments']['description']
        );
    }

    public function testAttachmentTargetGetSubresource(): void
    {
        $docs = $this->getSubresourceEntityDocsForAction('attachments', 'target', ApiAction::GET_SUBRESOURCE);
        $resourceData = $this->getResourceData($this->getSimpleFormatter()->format($docs));
        self::assertEquals('Get target', $resourceData['description']);
        self::assertEquals(
            '<p>Retrieve the record which the attachment belongs to.</p>',
            $resourceData['documentation']
        );
    }

    public function testAttachmentTargetGetRelationship(): void
    {
        $docs = $this->getSubresourceEntityDocsForAction('attachments', 'target', ApiAction::GET_RELATIONSHIP);
        $resourceData = $this->getResourceData($this->getSimpleFormatter()->format($docs));
        self::assertEquals('Get "target" relationship', $resourceData['description']);
        self::assertEquals(
            '<p>Retrieve the ID of a record which the attachment belongs to.</p>',
            $resourceData['documentation']
        );
    }

    public function testTargetEntityAttachmentsGetSubresource(): void
    {
        $docs = $this->getSubresourceEntityDocsForAction(
            'testapidepartments',
            'attachments',
            ApiAction::GET_SUBRESOURCE
        );
        $resourceData = $this->getResourceData($this->getSimpleFormatter()->format($docs));
        self::assertEquals('Get attachments', $resourceData['description']);
        self::assertEquals(
            '<p>Retrieve the records of the attachments associated with a specific test department record.</p>',
            $resourceData['documentation']
        );
    }

    public function testTargetEntityAttachmentsGetRelationship(): void
    {
        $docs = $this->getSubresourceEntityDocsForAction(
            'testapidepartments',
            'attachments',
            ApiAction::GET_RELATIONSHIP
        );
        $resourceData = $this->getResourceData($this->getSimpleFormatter()->format($docs));
        self::assertEquals('Get "attachments" relationship', $resourceData['description']);
        self::assertEquals(
            '<p>Retrieve the IDs of the attachments associated with a specific test department record.</p>',
            $resourceData['documentation']
        );
    }

    public function testTargetEntityAttachmentsUpdateRelationship(): void
    {
        $docs = $this->getSubresourceEntityDocsForAction(
            'testapidepartments',
            'attachments',
            ApiAction::UPDATE_RELATIONSHIP
        );
        self::assertEmpty($this->getSimpleFormatter()->format($docs));
    }

    public function testTargetEntityAttachmentsAddRelationship(): void
    {
        $docs = $this->getSubresourceEntityDocsForAction(
            'testapidepartments',
            'attachments',
            ApiAction::ADD_RELATIONSHIP
        );
        self::assertEmpty($this->getSimpleFormatter()->format($docs));
    }

    public function testTargetEntityAttachmentsDeleteRelationship(): void
    {
        $docs = $this->getSubresourceEntityDocsForAction(
            'testapidepartments',
            'attachments',
            ApiAction::DELETE_RELATIONSHIP
        );
        self::assertEmpty($this->getSimpleFormatter()->format($docs));
    }
}
