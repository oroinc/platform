<?php

namespace Oro\Bundle\DashboardBundle\Tests\Functional;

use Oro\Bundle\DashboardBundle\Entity\Widget;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Field\InputFormField;
use Symfony\Component\DomCrawler\Form;

class AbstractWidgetTestCase extends WebTestCase
{
    /**
     * @param Form $form
     * @param string $fieldName
     * @param mixed $value
     * @param string $fieldType
     */
    protected function setOrAdd(Form $form, $fieldName, $value, $fieldType = 'text')
    {
        if (!$form->has($fieldName)) {
            $doc = new \DOMDocument("1.0");
            $doc->loadHTML(sprintf('<input type="%s" name="%s" value="" />', $fieldType, $fieldName));
            $dynamicField = new InputFormField($doc->getElementsByTagName('input')->item(0));
            $form->set($dynamicField);
        }

        $form[$fieldName] = $value;
    }

    /**
     * @param Widget $widget
     * @param array $configFields
     */
    protected function configureWidget(Widget $widget, array $configFields)
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_dashboard_configure',
                ['id' => $widget->getId(), '_widgetContainer' => 'dialog']
            )
        );
        $response = $this->client->getResponse();
        $this->assertEquals($response->getStatusCode(), 200, 'Failed in getting configure widget dialog window !');

        /**
         * @var $crawler Crawler
         * @var $form Form
         */
        $crawler = $this->client->getCrawler();
        /** @var Form $form */
        $form = $crawler->selectButton('Save')->form();

        foreach ($configFields as $fieldsName => $value) {
            $this->setOrAdd($form, $fieldsName, $value);
        }

        $this->client->submit($form);

        $response = $this->client->getResponse();
        $this->assertEquals($response->getStatusCode(), 200, "Failed in submit widget configuration options !");
    }

    /**
     * Returns data for which will be used to show widget's chart
     *
     * @param $crawler
     * @return array
     */
    protected function getChartData($crawler)
    {
        $dataComponent = $crawler->filter('.column-chart');
        if ($dataComponent->extract(['data-page-component-options'])) {
            $data = $dataComponent->extract(['data-page-component-options']);
            $data = json_decode($data[0]);
            return $data->chartOptions->dataSource->data;
        } else {
            $dataComponent = $crawler->filter('.dashboard-widget-content > [data-page-component-options]');
            $data = $dataComponent->extract(['data-page-component-options']);
            $data = json_decode($data[0]);
            return $data->data;
        }
    }
}
