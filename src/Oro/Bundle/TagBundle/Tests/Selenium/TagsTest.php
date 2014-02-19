<?php

namespace Oro\Bundle\TagBundle\Tests\Selenium;

use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;

class TagsTest extends Selenium2TestCase
{
    /**
     * @return string
     */
    public function testCreateTag()
    {
        $tagName = 'Tag_'.mt_rand();

        $login = $this->login();

        $login->openTags('Oro\Bundle\TagBundle')
            ->add()
            ->assertTitle('Create Tag - Tags - System')
            ->setTagName($tagName)
            ->setOwner('admin')
            ->save()
            ->assertMessage('Tag saved')
            ->assertTitle('Tags - System')
            ->close();

        return $tagName;
    }

    /**
     * @depends testCreateTag
     * @param $tagName
     * @return string
     */
    public function testUpdateTag($tagName)
    {
        $newTagName = 'Update_' . $tagName;
        $login = $this->login();
        /** @var \Oro\Bundle\TagBundle\Tests\Selenium\Pages\Tags $login*/
        $login->openTags('Oro\Bundle\TagBundle')
            ->filterBy('Tag', $tagName)
            ->edit()
            ->setTagName($newTagName)
            ->save()
            ->assertTitle('Tags - System')
            ->assertMessage('Tag saved');

        return $newTagName;
    }

    /**
     * @depends testUpdateTag
     * @param $tagName
     */
    public function testDeleteTag($tagName)
    {
        $login = $this->login();
        /** @var \Oro\Bundle\TagBundle\Tests\Selenium\Pages\Tags $login*/
        $login->openTags('Oro\Bundle\TagBundle')
            ->filterBy('Tag', $tagName)
            ->delete()
            ->assertTitle('Tags - System')
            ->assertMessage('Item deleted');
    }
}
