<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageEntity;

/**
 * Class EmbeddedForm
 * @package OroCRM\Bundle\EmbeddedFormBundle\Tests\Selenium\Pages
 * {@inheritdoc}
 */
class EmbeddedForm extends AbstractPageEntity
{
    public function __construct($testCase, $redirect = true)
    {
        parent::__construct($testCase, $redirect);
    }

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle($title)
    {
        $field = $this->test->byId('embedded_form_title');
        $field->clear();
        $field->value($title);

        return $this;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        $enabled = $this->test->select($this->test->byId('embedded_form_formType'));
        $enabled->selectOptionByLabel($type);

        return $this;
    }

    /**
     * @return $this
     */
    public function edit()
    {
        $this->test->byXpath("//div[@class='pull-left btn-group icons-holder']/a[@title = 'Edit']")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();

        return $this;
    }

    /**
     * @return $this
     */
    public function checkPreview()
    {
        $this->assertElementPresent("//div[@class='widget-content']/iframe");

        return $this;
    }

    public function delete()
    {
        $this->test->byXpath("//div[@class='pull-left btn-group icons-holder']/a[contains(., 'Delete')]")->click();
        $this->test->byXpath("//div[div[contains(., 'Delete Confirmation')]]//a[text()='Yes, Delete']")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();
        return new EmbeddedForms($this->test, false);
    }
}
