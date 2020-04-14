<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\Controller;

use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class FileControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient(
            ['debug' => false],
            array_merge($this->generateBasicAuthHeader(), array('HTTP_X-CSRF-Header' => 1))
        );
    }

    public function testRequestNotExistedAttachment()
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_attachment_get_file',
                [
                    'id' => 2,
                    'action' => FileUrlProviderInterface::FILE_ACTION_DOWNLOAD,
                    'filename' => 'sample-filename',
                ]
            )
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 404);
    }
}
