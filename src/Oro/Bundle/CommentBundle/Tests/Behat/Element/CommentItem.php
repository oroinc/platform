<?php

namespace Oro\Bundle\CommentBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class CommentItem extends Element
{
    /**
     * @param string $title Could be either Update comment, Delete comment, etc.
     */
    public function clickActionLink($title)
    {
        $actions = $this->find('css', 'div.comment-actions a.dropdown-toggle');
        self::assertNotNull($actions, 'Comment actions dropdown not found');

        // BAP-11448. PhantomJs not handle mouseOver on this element
//        $actions->mouseOver();
        $this->getDriver()->executeJsOnXpath($actions->getXpath(), '{{ELEMENT}}.click()');

        $this->clickLink($title);
        $this->getDriver()->waitForAjax();
    }

    public function clickOnAttachmentThumbnail()
    {
        $this->find('css', 'div.thumbnail a')->click();
    }

    public function checkDownloadLink()
    {
        $downLoadLink = $this->find('css', 'ul.file-menu a:contains("Download")');
        $fileMenu = $this->find('css', 'a.file-menu');

        self::assertFalse($downLoadLink->isVisible());
        $fileMenu->click();

        self::assertTrue($downLoadLink->isVisible());
        $fileMenu->click();
    }
}
