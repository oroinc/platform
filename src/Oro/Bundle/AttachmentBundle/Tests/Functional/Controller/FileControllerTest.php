<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class FileControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(
            ['debug' => false],
            array_merge($this->generateBasicAuthHeader(), array('HTTP_X-CSRF-Header' => 1))
        );
    }

    public function testRequestInvalidAttachmentUrl()
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_attachment_file', ['codedString' => 'abc', 'extension' => 'jpg'])
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 404);
    }

    public function testRequestNotExistedAttachment()
    {
        $decodedUrl = <<<STR
        T3JvXEJ1bmRsZVxBdHRhY2htZW50QnVuZGxlXEVudGl0eVxBdHRhY2htZW50fGZpbGV8Mnxkb3dubG9hZHxkb3dubG9hZCAoMSkucG5n
STR;
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_attachment_file',
                [
                    'codedString' => $decodedUrl,
                    'extension' => 'jpg'
                ]
            )
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 404);
    }
}
