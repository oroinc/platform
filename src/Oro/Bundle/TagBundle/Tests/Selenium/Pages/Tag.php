<?php

namespace Oro\Bundle\TagBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageEntity;

/**
 * Class Tag
 *
 * @package Oro\Bundle\TestFrameworkBundle\Pages\Objects
 * {@inheritdoc}
 */
class Tag extends AbstractPageEntity
{
    /** @var  \PHPUnit_Extensions_Selenium2TestCase_Element */
    protected $tagName;

    /** @var  string */
    protected $owner = "//div[starts-with(@id,'s2id_oro_tag_tag_form_owner')]/a";

   /**
     * @param $accountName
     *
     * @return $this
     */
    public function setTagName($accountName)
    {
        $element = $this->test->byXPath("//*[@data-ftid='oro_tag_tag_form_name']");
        $element->clear();
        $element->value($accountName);

        return $this;
    }

    public function getTagName()
    {
        return $this->test->byXPath("//*[@data-ftid='oro_tag_tag_form_name']")->value();
    }

    public function save($button = 'Save')
    {
        return parent::save($button);
    }
}
