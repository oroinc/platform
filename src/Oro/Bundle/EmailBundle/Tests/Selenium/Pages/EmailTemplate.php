<?php

namespace Oro\Bundle\EmailBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageEntity;

/**
 * Class EmailTemplate
 *
 * @package Oro\Bundle\EmailBundle\Bundle\Pages\Objects
 */
class EmailTemplate extends AbstractPageEntity
{
    /** @var \PHPUnit_Extensions_Selenium2TestCase_Element_Select */
    protected $entityName;
    /** @var \PHPUnit_Extensions_Selenium2TestCase_Element_Select */
    protected $name;
    /** @var \PHPUnit_Extensions_Selenium2TestCase_Element_Select */
    protected $type;
    /** @var \PHPUnit_Extensions_Selenium2TestCase_Element_Select */
    protected $subject;
    /** @var \PHPUnit_Extensions_Selenium2TestCase_Element_Select */
    protected $content;

    public function __construct($testCase, $redirect = true)
    {
        parent::__construct($testCase, $redirect);
        $this->entityName = $this->test
            ->select($this->test->byXpath("//*[@data-ftid='oro_email_emailtemplate_entityName']"));
        $this->name = $this->test->byXpath("//*[@data-ftid='oro_email_emailtemplate_name']");
        $this->type = $this->test->byXpath("//*[@data-ftid='oro_email_emailtemplate_type']");
        $this->subject = $this->test
            ->byXpath("//*[@data-ftid='oro_email_emailtemplate_translations_defaultLocale_en_subject']");
        $this->content = $this->test
            ->byXpath("//*[@data-ftid='oro_email_emailtemplate_translations_defaultLocale_en_content']");
    }

    /**
     * @param $entityName
     * @return $this
     */
    public function setEntityName($entityName)
    {
        $this->entityName->selectOptionByLabel($entityName);
        return $this;
    }

    public function getEntityName()
    {
        return $this->entityName->selectedLabel();
    }

    /**
     * @param $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name->clear();
        $this->name->value($name);
        return $this;
    }

    public function getName()
    {
        return $this->name->attribute('value');
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type->element(
            $this->test->using('xpath')->value("div[label[normalize-space(text()) = '{$type}']]/input")
        )->click();
        return $this;
    }

    public function getType()
    {
        return $this->test->byXpath(
            "//div[@data-ftid='oro_email_emailtemplate_type']/div[input[@checked = 'checked']]/label"
        )->text();
    }

    /**
     * @param $subject
     * @return $this
     */
    public function setSubject($subject)
    {
        $this->subject->clear();
        $this->subject->value($subject);
        return $this;
    }

    public function getSubject()
    {
        return $this->subject->attribute('value');
    }

    /**
     * @param $content string
     * @return $this
     */
    public function setContent($content)
    {
        $this->test->waitUntil(
            function (\PHPUnit_Extensions_Selenium2TestCase $testCase) {
                return $testCase->execute(
                    [
                        'script' => 'return tinyMCE.activeEditor.initialized',
                        'args' => [],
                    ]
                );
            },
            intval(MAX_EXECUTION_TIME)
        );

        $this->test->execute(
            [
                'script' => sprintf('tinyMCE.activeEditor.setContent(\'%s\')', $content),
                'args' => [],
            ]
        );

        return $this;
    }

    public function getContent()
    {
        return $this->content->attribute('value');
    }

    public function getFields(&$values)
    {
        $values['entityname'] = $this->getEntityName();
        $values['type'] = $this->getType();
        $values['name'] = $this->getName();
        $values['subject'] = $this->getSubject();
        $values['content'] = $this->getContent();

        return $this;
    }
}
