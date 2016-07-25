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
    use FilteredGridTrait;

    /** @var string */
    protected $owner;

    /** @var string */
    protected $organization;

    /** @var  \PHPUnit_Extensions_Selenium2TestCase_Element */
    protected $tags;

    /**
     * @param string $fieldId Original field id
     * @param string $content
     * @return $this
     */
    public function setContentToTinymceElement($fieldId, $content)
    {
        $iframeElement = $this->test->byXPath(
            "//iframe[starts-with(@id,'" . $fieldId . "')]"
        );
        $this->test->frame($iframeElement);
        $bodyElement = $this->test->byTag('body');
        $bodyElement->clear();
        $this->test->frame(null);
        $iframeElement->click();
        $this->test->keys($content);
        return $this;
    }

    /**
     * Save entity
     * @param string $button Default name of save button
     * @return $this
     */
    public function save($button = 'Save and Close')
    {
        $this->test->byXPath("//button[normalize-space(.) = '{$button}']")->click();
        sleep(1);
        $this->waitPageToLoad();
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
        $this->test->byXPath("//div[@class='customer-content']/div[1]//a")->click();
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
        if (substr($class, -1) === 'y') {
            $class = substr($class, 0, strlen($class) - 1) . 'ies';
        } else {
            $class .= 's';
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
                ->byXPath("//div[starts-with(@id,'s2id_orocrm_contact_form_tags_autocomplete')]//input");
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
                ->byXPath("//div[starts-with(@id,'s2id_orocrm_contact_form_tags_autocomplete')]//input");
            $tagsPath->click();
            $tagsPath->value($tag);
            $this->waitForAjax();
            $this->assertElementPresent(
                "//div[@id='select2-drop']//div[contains(., '{$tag}')]",
                "Tag's autocomplete doesn't return entity"
            );
            $this->test->byXPath("//div[@id='select2-drop']//div[contains(., '{$tag}')]")->click();

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
            $this->test->byXPath("//div[@id='select2-drop']/div/input")->value($owner);
            $this->waitForAjax();
            $this->assertElementPresent(
                "//div[@id='select2-drop']//div[contains(., '{$owner}')]",
                "Owner autocomplete doesn't return search value"
            );
            $this->test->byXPath("//div[@id='select2-drop']//div[contains(., '{$owner}')]")->click();
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
            $this->test->byXpath($this->organization)->click();
            $this->waitForAjax();
            $this->test->byXpath("//div[@id='select2-drop']//div[contains(., '{$organization}')]")->click();
        }
        return $this;
    }

    public function getOrganization()
    {
        $element = $this->test->select($this->test->byXPath($this->organization));
        return trim($element->selectedLabel());
    }

    /**
     * @param array $actions
     * @param bool $exist
     * @return $this
     */
    public function checkActionInGroup(array $actions, $exist = true)
    {
        foreach ($actions as $action) {
            $this->test->byXPath("//div[@class='pull-right']//a[@class='btn dropdown-toggle']")->click();
            $this->waitForAjax();
            if (!$exist) {
                $this->assertElementNotPresent(
                    "//div[@class='pull-right']//a[contains(., '{$action}')]",
                    "Action {$action} exists but not expected"
                );
            } else {
                $this->assertElementPresent(
                    "//div[@class='pull-right']//a[contains(., '{$action}')]",
                    "Action {$action} does not exist"
                );
            }
        }

        return $this;
    }

    public function runActionInGroup($action)
    {
        $this->test->byXPath("//div[@class='pull-right']//a[@class='btn dropdown-toggle']")->click();
        $this->waitForAjax();
        $this->test->byXPath("//div[@class='pull-right']//a[contains(., '{$action}')]")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();

        return $this;
    }

    /**
     * @param string $fieldName
     * @param string $value
     * @param string|null $fieldType
     * @return $this
     */
    public function checkEntityFieldData($fieldName, $value, $fieldType = null)
    {
        $this->assertElementPresent(
            "//div[contains(@class,'control-group')]/label[contains(., '{$fieldName}')]".
            "/following-sibling::div/div",
            "Field '{$fieldName}' is not found"
        );
        $actualValue = $this->test->byXPath(
            "//div[contains(@class,'control-group')]/label[contains(., '{$fieldName}')]".
            "/following-sibling::div/div"
        )->text();

        if ($fieldType) {
            list($value, $actualValue) = $this->prepareValues($value, $actualValue, $fieldType);
        }

        \PHPUnit_Framework_Assert::assertEquals($value, $actualValue, "Field '{$fieldName}' has incorrect value");
        return $this;
    }

    /**
     * Method is verifying activity in activity list by activity name and activity type
     * @param $activityType
     * @param $activityName
     * @return $this
     */
    public function verifyActivity($activityType, $activityName)
    {
        $this->test->moveto($this->test->byXPath("//*[@class='container-fluid accordion']"));
        $this->filterByMultiselect('Activity Type', [$activityType]);

        $this->assertElementPresent(
            "//*[@class='container-fluid accordion']".
            "//*[@class='message-item message'][contains(., '{$activityName}')]".
            "/parent::div[@class='extra-info']/parent::div".
            "/*[@class='details'][contains(., '{$activityType}')]",
            "{$activityType} '{$activityName}' not found"
        );

        return $this;
    }

    /**
     * @param $filterName
     * @param $entityName
     * @return $this
     */
    public function assignEntityFromEmbeddedGrid($filterName, $entityName)
    {
        $this->filterBy($filterName, $entityName);
        $this->assertElementPresent(
            "//div[@class='container-fluid grid-scrollable-container']//td[contains(., '{$entityName}')]".
            "//preceding-sibling::td/input",
            "{$entityName} is not found in embedded grid"
        );
        $this->test->byXPath(
            "//div[@class='container-fluid grid-scrollable-container']//td[contains(., '{$entityName}')]".
            "//preceding-sibling::td/input"
        )->click();

        return $this;
    }


    /**
     * Method implement entity pagination switching
     * Method can get 'Next', 'Previous', 'Last', 'First' as values
     * @param string $value
     * @return $this
     */
    public function switchEntityPagination($value)
    {
        $this->assertElementPresent("//div[@id='entity-pagination']", 'Pagination not available at entity view page');
        switch ($value) {
            case 'Next':
                $this->test->byXPath("//div[@class='pagination']//i[@class='icon-chevron-right hide-text']")->click();
                break;
            case 'Previous':
                $this->test->byXPath("//div[@class='pagination']//i[@class='icon-chevron-left hide-text']")->click();
                break;
            case 'Last':
                $this->test->byXPath("//div[@class='pagination']//a[normalize-space()='Last']")->click();
                break;
            case 'First':
                $this->test->byXPath("//div[@class='pagination']//a[normalize-space()='First']")->click();
                break;
        }
        $this->waitPageToLoad();
        $this->waitForAjax();
        return $this;
    }

    /**
     * Prepare values for comparing to make tests independent from server configuration
     *
     * @param  mixed $value
     * @param  mixed $actualValue
     * @param  string $type
     * @return array
     */
    protected function prepareValues($value, $actualValue, $type)
    {
        switch ($type) {
            case 'DateTime':
                /**
                 * Use timestamps for datetime comparission to make sure we don't depend on ICU lib version,
                 * which may give different datetime format (e.g. with/without coma after year)
                 */
                $value = new \DateTime($value);
                $value = $value->getTimestamp();
                $actualValue = new \DateTime($actualValue);
                $actualValue = $actualValue->getTimestamp();
                break;
        }

        return [$value, $actualValue];
    }
}
