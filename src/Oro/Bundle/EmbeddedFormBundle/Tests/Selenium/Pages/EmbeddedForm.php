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
    /**
     * @param string $title
     * @return $this
     */
    public function setTitle($title)
    {
        $field = $this->test->byXpath("//*[@data-ftid='embedded_form_title']");
        $field->clear();
        $field->value($title);

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

    /**
     * @return EmbeddedForms
     */
    public function delete()
    {
        $this->test->byXpath("//div[@class='pull-left btn-group icons-holder']/a[contains(., 'Delete')]")->click();
        $this->test->byXpath("//div[div[contains(., 'Delete Confirmation')]]//a[text()='Yes, Delete']")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();
        return new EmbeddedForms($this->test, false);
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setFirstName($name)
    {
        $this->test->frame('frameId');
        $field = $this->test->byXpath("//input[@data-ftid='orocrm_magento_contactus_contact_request_firstName']");
        $field->clear();
        $field->value($name);

        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setLastName($name)
    {
        $field = $this->test->byXpath("//*[@data-ftid='orocrm_contactus_contact_request_lastName']");
        $field->clear();
        $field->value($name);

        return $this;
    }

    /**
     * @param string $email
     * @return $this
     */
    public function setEmail($email)
    {
        $field = $this->test->byXpath("//*[@data-ftid='orocrm_contactus_contact_request_emailAddress']");
        $field->clear();
        $field->value($email);

        return $this;
    }

    /**
     * @param string $comment
     * @return $this
     */
    public function setComment($comment)
    {
        $field = $this->test->byXpath("//*[@data-ftid='orocrm_contactus_contact_request_comment']");
        $field->clear();
        $field->value($comment);

        return $this;
    }

    /**
     * @return $this
     */
    public function submitForm()
    {
        $this->test->byXPath("//button[@id='orocrm_contactus_contact_request_submit']")->click();
        $this->waitForAjax();
        $this->assertElementPresent(
            "//div[@id='page']/p[normalize-space(.)='Form has been submitted successfully']",
            'Form has not been submitted'
        );

        return $this;
    }
}
