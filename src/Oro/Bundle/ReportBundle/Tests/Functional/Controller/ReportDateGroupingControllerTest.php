<?php

namespace Oro\Bundle\ReportBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\Translation\TranslatorInterface;

class ReportDateGroupingControllerTest extends WebTestCase
{
    const ERROR_UL_XPATH = "//div[contains(@class, 'alert-error')]/ul";

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    protected function setUp()
    {
        $this->initClient([], array_merge($this->generateBasicAuthHeader(), []));
        $this->loadFixtures([LoadUserData::class]);
        $this->translator = $this->getContainer()->get('translator');
    }

    public function testCreateConstraints()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_report_create'));
        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();
        $form = $this->fillDefaultForm($form);
        $form['oro_report_form[dateGrouping][useDateGroupFilter]'] = true;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $errors = $crawler->filterXPath(self::ERROR_UL_XPATH)->text();
        $message = $this->translator->trans('oro.report.date_grouping.date_field.mandatory', [], 'validators');
        $this->assertContains($message, $errors);
    }

    public function testCreateReportWithDateGrouping()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_report_create'));
        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();
        $form = $this->fillDefaultForm($form);
        $form['oro_report_form[dateGrouping][useDateGroupFilter]'] = true;
        $form['oro_report_form[dateGrouping][fieldName]'] = 'createdAt';
        $form['input_action'] = json_encode(['route' => 'oro_report_view', 'params' => ['id' => '$id']]);

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $this->assertContains("Report saved", $this->client->getResponse()->getContent());
        $pageComponentOptionsDiv = $crawler->filterXPath("//div[starts-with(@id, 'grid-oro_report_table')]");
        $options = json_decode($pageComponentOptionsDiv->attr('data-page-component-options'), true);
        $this->assertArrayHasKey('data', $options);
        $this->assertArrayHasKey('data', $options['data']);
        $this->assertGreaterThan(5, $options['data']['data']);

        $firstData = reset($options['data']['data']);
        $this->assertArrayHasKey('timePeriod', $firstData);
        $this->assertArrayHasKey('metadata', $options);
        $this->assertArrayHasKey('columns', $options['metadata']);
        $this->assertArrayHasKey('filters', $options['metadata']);
        $this->assertCount(count($this->getColumnsDefinition()['columns']) + 2, $options['metadata']['filters']);
        $this->assertCount(count($this->getColumnsDefinition()['columns']) + 2, $options['metadata']['columns']);
    }

    /**
     * @param Form $form
     * @param bool $addGrouping
     * @return Form $form
     */
    protected function fillDefaultForm(Form $form, $addGrouping = true)
    {
        $form['oro_report_form[name]'] = 'dateGroupingTest';
        $form['oro_report_form[entity]'] = User::class;
        $form['oro_report_form[type]'] = 'TABLE';
        $form['oro_report_form[owner]'] = '1';
        $definition = ($addGrouping) ? array_merge(
            $this->getColumnsDefinition(),
            $this->getGroupingDefinition()
        ) : $this->getColumnsDefinition();
        $form['oro_report_form[definition]'] = json_encode($definition);

        return $form;
    }

    /**
     * @return array
     */
    protected function getGroupingDefinition()
    {
        return [
            'grouping_columns' => [
                [
                    'name' => 'id',
                    'temp-validation-name-124' => '',
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getColumnsDefinition()
    {
        return [
            'columns' => [
                [
                    "name" => 'id',
                    "label" => 'Id',
                    "func" => "",
                    "sorting" => "",
                ],
                [
                    "name" => 'username',
                    "label" => 'Username',
                    "func" => "",
                    "sorting" => "DESC",
                ],
                [
                    "name" => 'email',
                    "label" => 'Email',
                    "func" => "",
                    "sorting" => "",
                ],
            ],
        ];
    }
}
