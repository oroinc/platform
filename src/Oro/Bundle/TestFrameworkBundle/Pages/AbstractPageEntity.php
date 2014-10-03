<?php

namespace Oro\Bundle\TestFrameworkBundle\Pages;

/**
 * Class AbstractPageEntity
 *
 * @package Oro\Bundle\TestFrameworkBundle\Pages
 * {@inheritdoc}
 */
abstract class AbstractPageEntity extends AbstractPage
{
    /** @var string */
    protected $owner;

    /** @var string */
    protected $organization;

    /** @var  \PHPUnit_Extensions_Selenium2TestCase_Element */
    protected $tags;

    /**
     * Save entity
     *
     * @return $this
     */
    public function save()
    {
        $this->test->byXpath("//button[normalize-space(.) = 'Save and Close']")->click();
        $this->waitPageToLoad();
        sleep(1);
        $this->waitForAjax();
        return $this;
    }

    /**
     * Return to grid from entity view page
     *
     * @return $this
     */
    public function toGrid()
    {
        $this->test->byXpath("//div[@class='customer-content pull-left']/div[1]//a")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();

        return $this;
    }

    /**
     * @param bool $redirect
     *
     * @return mixed
     */
    public function close($redirect = false)
    {
        $class = get_class($this);
        if (substr($class, -1) == 'y') {
            $class = substr($class, 0, strlen($class) - 1) . 'ies';
        } else {
            $class = $class . 's';
        }

        return new $class($this->test, $redirect);
    }

    /**
     * Verify tag
     *
     * @param $tag
     *
     * @return $this
     * @throws \Exception
     */
    public function verifyTag($tag)
    {
        if ($this->isElementPresent("//div[starts-with(@id,'s2id_orocrm_contact_form_tags_autocomplete')]")) {
            $tagsPath = $this->test
                ->byXpath("//div[starts-with(@id,'s2id_orocrm_contact_form_tags_autocomplete')]//input");
            $tagsPath->click();
            $tagsPath->value(substr($tag, 0, (strlen($tag)-1)));
            $this->waitForAjax();
            $this->assertElementPresent(
                "//div[@id='select2-drop']//div[contains(., '{$tag}')]",
                "Tag's autocomplete doesn't return entity"
            );
            $tagsPath->clear();
        } else {
            if ($this->isElementPresent("//div[contains(@class, 'tags-holder')]")) {
                $this->assertElementPresent(
                    "//div[contains(@class, 'tags-holder')]//li[contains(., '{$tag}')]",
                    'Tag is not assigned to entity'
                );
            } else {
                throw new \Exception("Tag field can't be found");
            }
        }
        return $this;
    }

    /**
     * Set tag
     *
     * @param $tag
     * @return $this
     * @throws \Exception
     */
    public function setTag($tag)
    {
        if ($this->isElementPresent("//div[starts-with(@id,'s2id_orocrm_contact_form_tags_autocomplete')]")) {
            $tagsPath = $this->test
                ->byXpath("//div[starts-with(@id,'s2id_orocrm_contact_form_tags_autocomplete')]//input");
            $tagsPath->click();
            $tagsPath->value($tag);
            $this->waitForAjax();
            $this->assertElementPresent(
                "//div[@id='select2-drop']//div[contains(., '{$tag}')]",
                "Tag's autocomplete doesn't return entity"
            );
            $this->test->byXpath("//div[@id='select2-drop']//div[contains(., '{$tag}')]")->click();

            return $this;
        } else {
            throw new \Exception("Tag field can't be found");
        }
    }

    /**
     * @param $paramName
     *
     * @return mixed
     */
    public function getParam($paramName)
    {
        $url = $this->test->url();
        $path = parse_url($url)['path'];
        $str = explode('/', $path);

        $found_index = array_search($paramName, $str);
        if ($found_index !== false) {
            return $str[$found_index+1];
        }

        return null;
    }

    public function getId($paramName = 'view')
    {
        return $this->getParam($paramName);
    }

    /**
     * @param $owner
     *
     * @return $this
     */
    public function setOwner($owner)
    {
        if (isset($this->owner)) {
            $ownerObject = $this->test->byXPath($this->owner);
            $ownerObject->click();
            $this->waitForAjax();
            $this->test->byXpath("//div[@id='select2-drop']/div/input")->value($owner);
            $this->waitForAjax();
            $this->assertElementPresent(
                "//div[@id='select2-drop']//div[contains(., '{$owner}')]",
                "Owner autocomplete doesn't return search value"
            );
            $this->test->byXpath("//div[@id='select2-drop']//div[contains(., '{$owner}')]")->click();
        }
        return $this;
    }

    /**
     * @param $organization
     *
     * @return $this
     */
    public function setOrganization($organization)
    {
        if (isset($this->organization)) {
            $element = $this->test->select($this->test->byXPath($this->organization));
            $this->test->moveto($element);
            $element->selectOptionByLabel($organization);
        }
        return $this;
    }

    public function getOrganization()
    {
        $element = $this->test->select($this->test->byXPath($this->organization));
        return trim($element->selectedLabel());
    }
}
