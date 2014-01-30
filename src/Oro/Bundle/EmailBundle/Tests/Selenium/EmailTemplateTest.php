<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Selenium;

use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;

/**
 * Class EmailTemplateTest
 *
 * @package Oro\Bundle\TestFrameworkBundle\Tests\Selenium
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
        $login->openEmailTemplates('Oro\Bundle\EmailBundle')
            ->add()
            ->assertTitle('Create Email Template - Templates - Emails - System')
            ->setEntityName('User')
            ->setType('Html')
            ->setName($templateName)
            ->setSubject('Subject')
            ->setContent('Template content')
            ->save()
            ->assertMessage('Template saved')
            ->assertTitle('Templates - Emails - System')
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
        $login->openEmailTemplates('Oro\Bundle\EmailBundle')
            ->cloneEntity('Template name', $templateName)
            ->setName($newTemplateName)
            ->save()
            ->assertMessage('Template saved')
            ->assertTitle('Templates - Emails - System')
            ->close()
            ->open(array($newTemplateName))
            ->getFields($fields);
        $this->assertEquals('User', $fields['entityname']);
        // label with space according to markup in OroFormBundle:Form/fields.html.twig
        $this->assertEquals('Html ', $fields['type']);
        $this->assertEquals('Subject', $fields['subject']);
        $this->assertEquals('Template content', $fields['content']);

        return $newTemplateName;
    }

    /**
     * @depends testCreateEmailTemplate
     * @param $templateName
     * @return string
     */
    public function testUpdateEmailTemplate($templateName)
    {
        $newTemplateName = 'Update_' . $templateName;
        $login = $this->login();
        $login->openEmailTemplates('Oro\Bundle\EmailBundle')
            ->open(array($templateName))
            ->setName($newTemplateName)
            ->save()
            ->assertMessage('Template saved')
            ->assertTitle('Templates - Emails - System')
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
        $login->openEmailTemplates('Oro\Bundle\EmailBundle')
            ->delete('Template name', $templateName)
            ->assertTitle('Templates - Emails - System')
            ->assertMessage('Item deleted');
    }
}
