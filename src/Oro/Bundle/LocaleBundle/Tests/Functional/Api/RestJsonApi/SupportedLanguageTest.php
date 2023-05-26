<?php

namespace Oro\Bundle\LocaleBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

class SupportedLanguageTest extends RestJsonApiTestCase
{
    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'supportedlanguages']);
        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'supportedlanguages', 'id' => 'en', 'attributes' => ['name' => 'English']]
                ]
            ],
            $response
        );
    }

    public function testGet(): void
    {
        $response = $this->get(['entity' => 'supportedlanguages', 'id' => 'en']);
        $this->assertResponseContains(
            [
                'data' => ['type' => 'supportedlanguages', 'id' => 'en', 'attributes' => ['name' => 'English']]
            ],
            $response
        );
    }
}
