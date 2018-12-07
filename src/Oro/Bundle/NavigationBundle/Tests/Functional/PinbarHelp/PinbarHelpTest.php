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
            \sprintf('%s/app.php%s', $pathToFolder, $this->getUrl('oro_pinbar_help')),
            [],
            [],
            [
                'SCRIPT_NAME' => $pathToFolder.'/app.php',
                'SCRIPT_FILENAME' => 'app.php'
            ]
        );

        $pinbarLocationImageSrc = $crawler->filterXPath('//img[@alt="Location of the Pinbar icon"]')
            ->attr('src');
        self::assertContains(
            $pathToFolder . '/bundles/oronavigation/images/pinbar-location.jpg',
            $pinbarLocationImageSrc
        );
    }
}
