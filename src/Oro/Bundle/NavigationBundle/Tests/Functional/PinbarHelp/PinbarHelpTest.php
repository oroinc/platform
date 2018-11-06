<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\PinbarHelp;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class PinbarHelpTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
    }

    public function testPinbarLocationImageWithBaseUrl()
    {
        $pathToFolder = '/path/to/folder';
        //Emulate subfolder request
        $crawler = $this->client->request(
            'GET',
            $pathToFolder . '/app.php/admin/pinbar/help',
            [],
            [],
            [
                'SCRIPT_NAME' => $pathToFolder.'/app.php',
                'SCRIPT_FILENAME' => 'app.php'
            ]
        );

        $pinbarLocationImage = $crawler->selectImage('Location of the Pinbar icon')->image();
        self::assertContains(
            $pathToFolder . '/bundles/oronavigation/images/pinbar-location.jpg',
            $pinbarLocationImage->getUri()
        );
    }
}
