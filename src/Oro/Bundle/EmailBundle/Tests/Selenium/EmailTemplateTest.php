<?php

namespace Oro\Bundle\EmailBundle\Tests\Selenium;

use Oro\Bundle\EmailBundle\Tests\Selenium\Pages\EmailTemplates;
use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;

/**
 * Class EmailTemplateTest
 *
 * @package Oro\Bundle\EmailBundle\Tests\Selenium
 */
class EmailTemplateTest extends Selenium2TestCase
{
    /**
     * @return string
     */
    public function testCreateEmailTemplate()
    {
        $templateName = 'EmailTemplate_'.mt_rand();

        $login = $this->login();
        /* @var EmailTemplates $login */
        $login->openEmailTemplates('Oro\Bundle\EmailBundle')
            ->assertTitle('All - Templates - Emails - System')
            ->add()
            ->assertTitle('Create Email Template - Templates - Emails - System')
            ->setEntityName('User')
            ->setType('Html')
            ->setName($templateName)
            ->setSubject('Subject')
            ->setContent('Template content')
            ->save()
            ->assertMessage('Template saved')
            ->assertTitle('All - Templates - Emails - System')
            ->close();

        return $templateName;
    }

    /**
     * @depends testCreateEmailTemplate
     *
     * @param $templateName
     * @return string
     */
    public function testCloneEmailTemplate($templateName)
    {
        $newTemplateName = 'Clone_' . $templateName;
        $fields = array();
        $login = $this->login();
        /* @var EmailTemplates $login*/
        $login->openEmailTemplates('Oro\Bundle\EmailBundle')
            ->cloneEntity('Template name', $templateName)
            ->setName($newTemplateName)
            ->save()
            ->assertMessage('Template saved')
            ->assertTitle('All - Templates - Emails - System')
            ->close()
            ->open(array($newTemplateName))
            ->assertTitle("Template {$newTemplateName} - Edit - Templates - Emails - System")
            ->getFields($fields);
        $this->assertEquals('User', $fields['entityname']);
        // label with space according to markup in OroFormBundle:Form/fields.html.twig
        $this->assertEquals('Html ', $fields['type']);
        $this->assertEquals('Subject', $fields['subject']);
        $this->assertEquals("<p>Template content</p>", $fields['content']);

        return $newTemplateName;
    }

    /**
     * @depends testCloneEmailTemplate
     * @param $templateName
     * @return string
     */
    public function testUpdateEmailTemplate($templateName)
    {
        $newTemplateName = 'Update_' . $templateName;
        $login = $this->login();
        /* @var EmailTemplates $login*/
        $login->openEmailTemplates('Oro\Bundle\EmailBundle')
            ->open(array($templateName))
            ->assertTitle("Template {$templateName} - Edit - Templates - Emails - System")
            ->setName($newTemplateName)
            ->save()
            ->assertMessage('Template saved')
            ->assertTitle('All - Templates - Emails - System')
            ->close();

        return $newTemplateName;
    }

    /**
     * @depends testUpdateEmailTemplate
     * @param $templateName
     */
    public function testDeleteEmailTemplate($templateName)
    {
        $login = $this->login();
        /* @var EmailTemplates $login*/
        $login->openEmailTemplates('Oro\Bundle\EmailBundle')
            ->filterBy('Template name', $templateName)
            ->delete([$templateName])
            ->assertTitle('All - Templates - Emails - System')
            ->assertMessage('Item deleted');
    }
}
