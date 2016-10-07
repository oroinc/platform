<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class AttachmentItem extends Element
{
    public function checkDownloadLink()
    {
        $downLoadLink = $this->find('css', 'ul.file-menu a:contains("Download")');
        $fileMenu = $this->find('css', 'a.file-menu');

        self::assertFalse($downLoadLink->isVisible());
        $fileMenu->click();

        self::assertTrue($downLoadLink->isVisible());
        $fileMenu->click();
    }

    public function clickOnAttachmentThumbnail()
    {
        $this->find('css', 'div.thumbnail a')->click();
    }
}
