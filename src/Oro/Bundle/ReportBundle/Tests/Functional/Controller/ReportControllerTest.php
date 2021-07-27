<?php

namespace Oro\Bundle\ReportBundle\Tests\Functional\Controller;

use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;
use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\ReportBundle\Entity\ReportType;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

class ReportControllerTest extends WebTestCase
{
    private const TEST_REPORT = 'Test user report';
    private const UPDATED_TEST_REPORT = 'Updated test user report';

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
    }

    public function testCreate(): void
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_report_create'));

        $form = $crawler->selectButton('Save and Close')->form();

        $formValues = $form->getPhpValues();
        $formValues['oro_report_form']['name'] = self::TEST_REPORT;
        $formValues['oro_report_form']['description'] = self::TEST_REPORT . ' description';
        $formValues['oro_report_form']['entity'] = User::class;
        $formValues['oro_report_form']['type'] = ReportType::TYPE_TABLE;
        $formValues['oro_report_form']['definition'] = QueryDefinitionUtil::encodeDefinition([
            'columns' => [
                [
                    'name' => 'id',
                    'label' => 'Id',
                    'func' => [
                        'name' => 'Count',
                        'group_type' => 'aggregates',
                        'group_name' => 'number',
                        'return_type' => 'integer'
                    ],
                    'sorting' => ''
                ],
                [
                    'name' => 'firstName',
                    'label' => 'First name',
                    'func' => '',
                    'sorting' => ''
                ]
            ],
            'grouping_columns' => [
                [
                    'name' => 'firstName',
                    'temp-validation-name-3050' => ''
                ]
            ],
            'filters' => [
                [
                    'columnName' => 'username',
                    'criterion' => [
                        'filter' => 'string',
                        'data' => [
                            'value' => 'test',
                            'type' => '1'
                        ]
                    ]
                ]
            ]
        ]);

        $this->client->followRedirects();
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $formValues);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        static::assertStringContainsString('Report saved', $crawler->html());
    }

    /**
     * @depends testCreate
     */
    public function testIndex(): void
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_report_index'));
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        static::assertStringContainsString('reports-grid', $crawler->html());
        static::assertStringContainsString(self::TEST_REPORT, $result->getContent());
    }

    /**
     * @depends testCreate
     */
    public function testUpdate(): int
    {
        $em = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass(Report::class);

        $repository = $em->getRepository(Report::class);

        /** @var Report $report */
        $report = $repository->findOneBy(['name' => self::TEST_REPORT]);

        $crawler = $this->client->request('GET', $this->getUrl('oro_report_update', ['id' => $report->getId()]));

        $form = $crawler->selectButton('Save and Close')->form();
        $form['oro_report_form[name]'] = self::UPDATED_TEST_REPORT;

        $crawler = $this->client->submit($form);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        static::assertStringContainsString('Report saved', $crawler->html());

        $em->clear();

        $report = $repository->find($report->getId());

        $this->assertNotNull($report);
        $this->assertEquals(self::UPDATED_TEST_REPORT, $report->getName());

        return $report->getId();
    }

    /**
     * @depends testUpdate
     */
    public function testClone(int $id): string
    {
        $name = sprintf('Copy of %s', self::UPDATED_TEST_REPORT);

        $crawler = $this->client->request('GET', $this->getUrl('oro_report_clone', ['id' => $id]));

        $form = $crawler->selectButton('Save and Close')->form();
        $this->assertEquals($name, $form['oro_report_form[name]']->getValue());

        $crawler = $this->client->submit($form);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        static::assertStringContainsString('Report saved', $crawler->html());

        $repository = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass(Report::class)
            ->getRepository(Report::class);

        $originalReport = $repository->find($id);
        $this->assertNotNull($originalReport);

        $copiedReport = $repository->findOneBy(['name' => $name]);
        $this->assertNotNull($copiedReport);
        $this->assertEquals($name, $copiedReport->getName());

        $this->assertNotEquals($originalReport->getId(), $copiedReport->getId());
        $this->assertNotEquals($originalReport->getName(), $copiedReport->getName());
        $this->assertSame($originalReport->getDescription(), $copiedReport->getDescription());
        $this->assertSame($originalReport->getType(), $copiedReport->getType());
        $this->assertSame($originalReport->getEntity(), $copiedReport->getEntity());
        $this->assertSame($originalReport->getOwner(), $copiedReport->getOwner());
        $this->assertSame($originalReport->getDefinition(), $copiedReport->getDefinition());
        $this->assertSame($originalReport->getChartOptions(), $copiedReport->getChartOptions());
        $this->assertSame($originalReport->getOrganization(), $copiedReport->getOrganization());

        return $name;
    }

    /**
     * @depends testClone
     */
    public function testIndexAfterClone(string $name): void
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_report_index'));
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        static::assertStringContainsString('reports-grid', $crawler->html());
        static::assertStringContainsString($name, $result->getContent());
    }
}
