<?php

namespace Oro\Bundle\NoteBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Tests\Functional\DocumentationTestTrait;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

/**
 * @group regression
 */
class NoteDocumentationTest extends RestJsonApiTestCase
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

    public function testNoteActivityTargets(): void
    {
        $docs = $this->getEntityDocsForAction('notes', ApiAction::GET);
        $resourceData = $this->getResourceData($this->getSimpleFormatter()->format($docs));
        self::assertEquals(
            '<p>Records associated with the note record.</p>',
            $resourceData['response']['activityTargets']['description']
        );
    }

    public function testTargetEntityActivityNotes(): void
    {
        $docs = $this->getEntityDocsForAction('testactivitytargets', ApiAction::GET);
        $resourceData = $this->getResourceData($this->getSimpleFormatter()->format($docs));
        self::assertEquals(
            '<p>The notes associated with the test activity target record.</p>',
            $resourceData['response']['activityNotes']['description']
        );
    }

    public function testNoteActivityTargetsGetSubresource(): void
    {
        $docs = $this->getSubresourceEntityDocsForAction('notes', 'activityTargets', ApiAction::GET_SUBRESOURCE);
        $resourceData = $this->getResourceData($this->getSimpleFormatter()->format($docs));
        self::assertEquals('Get activity targets', $resourceData['description']);
        self::assertEquals(
            '<p>Retrieve records associated with a specific note record.</p>',
            $resourceData['documentation']
        );
    }

    public function testNoteActivityTargetsGetRelationship(): void
    {
        $docs = $this->getSubresourceEntityDocsForAction('notes', 'activityTargets', ApiAction::GET_RELATIONSHIP);
        $resourceData = $this->getResourceData($this->getSimpleFormatter()->format($docs));
        self::assertEquals('Get "activity targets" relationship', $resourceData['description']);
        self::assertEquals(
            '<p>Retrieve the IDs of records associated with a specific note record.</p>',
            $resourceData['documentation']
        );
    }

    public function testTargetEntityActivityNotesGetSubresource(): void
    {
        $docs = $this->getSubresourceEntityDocsForAction(
            'testactivitytargets',
            'activityNotes',
            ApiAction::GET_SUBRESOURCE
        );
        $resourceData = $this->getResourceData($this->getSimpleFormatter()->format($docs));
        self::assertEquals('Get activity notes', $resourceData['description']);
        self::assertEquals(
            '<p>Retrieve the records of the notes associated with a specific test activity target record.</p>',
            $resourceData['documentation']
        );
    }

    public function testTargetEntityActivityNotesGetRelationship(): void
    {
        $docs = $this->getSubresourceEntityDocsForAction(
            'testactivitytargets',
            'activityNotes',
            ApiAction::GET_RELATIONSHIP
        );
        $resourceData = $this->getResourceData($this->getSimpleFormatter()->format($docs));
        self::assertEquals('Get "activity notes" relationship', $resourceData['description']);
        self::assertEquals(
            '<p>Retrieve the IDs of the notes associated with a specific test activity target record.</p>',
            $resourceData['documentation']
        );
    }
}
