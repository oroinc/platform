<?php

namespace Oro\Bundle\ReportBundle\Tests\Functional\Validation;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Form;

class ReportValidationTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
    }

    public function testCreateReportShowsCorrectErrorMessageOnMissingColumns()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_report_create'));
        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();
        $formValues = $form->getPhpValues();
        $formValues['oro_report_form']['entity'] = 'Oro\Bundle\UserBundle\Entity\User';
        $formValues['oro_report_form']['name'] = 'test';
        $formValues['oro_report_form']['type'] = 'TABLE';
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $formValues);

        $expectedErrorMessage = $this->getContainer()->get('translator.default')->trans(
            'oro.report.definition.columns.mandatory',
            [],
            'validators'
        );
        $errors = $crawler->filterXPath("//div[contains(@class, 'alert-error')]/ul/li");

        $this->assertCount(1, $errors);
        $this->assertEquals($expectedErrorMessage, $errors->first()->text());
    }

    public function testCreateReportDoesNotShowMissingColumnsError()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_report_create'));
        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();
        $formValues = $form->getPhpValues();
        $formValues['oro_report_form']['entity'] = 'Oro\Bundle\UserBundle\Entity\User';
        $formValues['oro_report_form']['name'] = 'test';
        $formValues['oro_report_form']['type'] = 'TABLE';
        $formValues['oro_report_form']['definition'] = json_encode(
            [
                'columns' => [
                    [
                        'name' => 'username',
                        'label' => 'Username',
                        'func' => null,
                        'sorting' => 'DESC',
                    ],
                ],
            ]
        );

        $this->client->followRedirects(true);
        $this->client->request($form->getMethod(), $form->getUri(), $formValues);

        $expectedMessage = $this->getContainer()->get('translator.default')->trans('Report saved');
        $this->assertContains($expectedMessage, $this->client->getResponse()->getContent());
    }
}
