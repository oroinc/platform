<?php

namespace Oro\Bundle\CommentBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Tests\Functional\DocumentationTestTrait;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

/**
 * @group regression
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CommentDocumentationTest extends RestJsonApiTestCase
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

    public function testCommentTarget(): void
    {
        $docs = $this->getEntityDocsForAction('comments', ApiAction::GET);
        $resourceData = $this->getResourceData($this->getSimpleFormatter()->format($docs));
        self::assertEquals(
            '<p>A record that the comment was made on.</p>',
            $resourceData['response']['target']['description']
        );
    }

    public function testCommentTargetForCreate(): void
    {
        $docs = $this->getEntityDocsForAction('comments', ApiAction::CREATE);
        $resourceData = $this->getResourceData($this->getSimpleFormatter()->format($docs));
        self::assertEquals(
            '<p>A record that the comment was made on.</p>'
            . '<p><strong>The required field.</strong></p>',
            $resourceData['parameters']['target']['description']
        );
    }

    public function testCommentTargetForUpdate(): void
    {
        $docs = $this->getEntityDocsForAction('comments', ApiAction::UPDATE);
        $resourceData = $this->getResourceData($this->getSimpleFormatter()->format($docs));
        self::assertEquals(
            '<p>A record that the comment was made on.</p>'
            . '<p><strong>This field must not be empty, if it is passed.</strong></p>',
            $resourceData['parameters']['target']['description']
        );
    }

    public function testTargetEntityComments(): void
    {
        $docs = $this->getEntityDocsForAction('notes', ApiAction::GET);
        $resourceData = $this->getResourceData($this->getSimpleFormatter()->format($docs));
        self::assertEquals(
            '<p>The comments associated with the note record.</p>',
            $resourceData['response']['comments']['description']
        );
    }

    public function testTargetEntityCommentsForCreate(): void
    {
        $docs = $this->getEntityDocsForAction('notes', ApiAction::CREATE);
        $resourceData = $this->getResourceData($this->getSimpleFormatter()->format($docs));
        self::assertEquals(
            '<p>The comments associated with the note record.</p>',
            $resourceData['parameters']['comments']['description']
        );
    }

    public function testTargetEntityCommentsForUpdate(): void
    {
        $docs = $this->getEntityDocsForAction('notes', ApiAction::UPDATE);
        $resourceData = $this->getResourceData($this->getSimpleFormatter()->format($docs));
        self::assertEquals(
            '<p>The comments associated with the note record.</p>'
            . '<p><strong>The read-only field. A passed value will be ignored.</strong></p>',
            $resourceData['parameters']['comments']['description']
        );
    }

    public function testCommentTargetGetSubresource(): void
    {
        $docs = $this->getSubresourceEntityDocsForAction('comments', 'target', ApiAction::GET_SUBRESOURCE);
        $resourceData = $this->getResourceData($this->getSimpleFormatter()->format($docs));
        self::assertEquals('Get target', $resourceData['description']);
        self::assertEquals(
            '<p>Retrieve an entity record that the comment was made on.</p>',
            $resourceData['documentation']
        );
    }

    public function testCommentTargetGetRelationship(): void
    {
        $docs = $this->getSubresourceEntityDocsForAction('comments', 'target', ApiAction::GET_RELATIONSHIP);
        $resourceData = $this->getResourceData($this->getSimpleFormatter()->format($docs));
        self::assertEquals('Get "target" relationship', $resourceData['description']);
        self::assertEquals(
            '<p>Retrieve the ID of an entity record that the comment was made on.</p>',
            $resourceData['documentation']
        );
    }

    public function testTargetEntityCommentsGetSubresource(): void
    {
        $docs = $this->getSubresourceEntityDocsForAction('notes', 'comments', ApiAction::GET_SUBRESOURCE);
        $resourceData = $this->getResourceData($this->getSimpleFormatter()->format($docs));
        self::assertEquals('Get comments', $resourceData['description']);
        self::assertEquals(
            '<p>Retrieve the records of the comments associated with a specific note record.</p>',
            $resourceData['documentation']
        );
    }

    public function testTargetEntityCommentsGetRelationship(): void
    {
        $docs = $this->getSubresourceEntityDocsForAction('notes', 'comments', ApiAction::GET_RELATIONSHIP);
        $resourceData = $this->getResourceData($this->getSimpleFormatter()->format($docs));
        self::assertEquals('Get "comments" relationship', $resourceData['description']);
        self::assertEquals(
            '<p>Retrieve the IDs of the comments associated with a specific note record.</p>',
            $resourceData['documentation']
        );
    }

    public function testTargetEntityCommentsUpdateRelationship(): void
    {
        $docs = $this->getSubresourceEntityDocsForAction('notes', 'comments', ApiAction::UPDATE_RELATIONSHIP);
        self::assertEmpty($this->getSimpleFormatter()->format($docs));
    }

    public function testTargetEntityCommentsAddRelationship(): void
    {
        $docs = $this->getSubresourceEntityDocsForAction('notes', 'comments', ApiAction::ADD_RELATIONSHIP);
        self::assertEmpty($this->getSimpleFormatter()->format($docs));
    }

    public function testTargetEntityCommentsDeleteRelationship(): void
    {
        $docs = $this->getSubresourceEntityDocsForAction('notes', 'comments', ApiAction::DELETE_RELATIONSHIP);
        self::assertEmpty($this->getSimpleFormatter()->format($docs));
    }
}
