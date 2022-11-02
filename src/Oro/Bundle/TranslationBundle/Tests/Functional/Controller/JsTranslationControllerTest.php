<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class JsTranslationControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
    }

    public function testGetTranslation(): void
    {
        $this->client->request('GET', $this->getUrl('oro_translation_jstranslation', ['_locale' => 'en']));
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);
    }
}
