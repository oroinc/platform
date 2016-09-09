<?php

namespace Oro\Bundle\TagBundle\Tests\Selenium;

use Oro\Bundle\TagBundle\Tests\Selenium\Pages\Tags;
use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;

class TagsTest extends Selenium2TestCase
{
    /**
     * @return string
     */
    public function testCreateTag()
    {
        $tagName = 'Tag_' . mt_rand();

        $login = $this->login();
        /** @var Tags $login */
        $login->openTags('Oro\Bundle\TagBundle')
            ->add()
            ->assertTitle('Create Tag - Tags - System')
            ->setTagName($tagName)
            ->setOwner('admin')
            ->save('Save and Close')
            ->assertMessage('Tag saved')
            ->assertTitle('All - Tags - System');

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
        /** @var Tags $login*/
        $login->openTags('Oro\Bundle\TagBundle')
            ->filterBy('Name', $tagName)
            ->edit()
            ->assertTitle("{$tagName} Tag - Edit - Tags - System")
            ->setTagName($newTagName)
            ->save('Save and Close')
            ->assertMessage('Tag saved')
            ->assertTitle('All - Tags - System');

        return $newTagName;
    }

    /**
     * @depends testUpdateTag
     * @param $tagName
     */
    public function testDeleteTag($tagName)
    {
        $login = $this->login();
        /** @var Tags $login*/
        $login->openTags('Oro\Bundle\TagBundle')
            ->filterBy('Name', $tagName)
            ->delete([$tagName])
            ->assertMessage('Item deleted')
            ->assertTitle('All - Tags - System');
    }
}
