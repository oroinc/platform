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
        $actions = $this->find('css', 'div.comment-actions .dropdown-toggle');
        self::assertNotNull($actions, 'Comment actions dropdown not found');

        // BAP-11448. PhantomJs not handle mouseOver on this element
//        $actions->mouseOver();
        $this->getDriver()->executeJsOnXpath($actions->getXpath(), '{{ELEMENT}}.click()');

        $this->clickLink($title);
        $this->getDriver()->waitForAjax();
    }
}
