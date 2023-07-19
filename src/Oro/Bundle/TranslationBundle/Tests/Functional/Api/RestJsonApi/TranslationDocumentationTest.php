<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Tests\Functional\DocumentationTestTrait;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

/**
 * @group regression
 */
class TranslationDocumentationTest extends RestJsonApiTestCase
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

    public function testDocumentationForGetList(): void
    {
        $docs = $this->getEntityDocsForAction('translations', ApiAction::GET_LIST);
        $resourceData = $this->getResourceData($this->getSimpleFormatter()->format($docs));
        self::assertStringStartsWith(
            '<p>Retrieve a collection of visual elements in the application, like labels, information massages,'. "\n"
            . 'notifications, alerts, workflow statuses, etc.</p>'
            . '<p><strong>Note:</strong> The maximum number of records this endpoint can return is 1000.'
            . '</p>**Note**: The following predefined language codes are supported:' . "\n\n"
            . '- **current** for a language of the current request.' . "\n"
            . '- **user** for a default language for the current user.',
            $resourceData['documentation']
        );
    }
}
