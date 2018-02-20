<?php

namespace Oro\Bundle\DashboardBundle\Tests\Functional\Controller\Api\Rest;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\DashboardBundle\Entity\Widget;
use Oro\Bundle\DashboardBundle\Model\ConfigProvider;
use Oro\Bundle\DashboardBundle\Model\Manager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class WidgetControllerTest extends WebTestCase
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var Widget
     */
    protected $widget;

    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    /**
     * @var Manager
     */
    protected $dashboardManager;

    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->em               = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->configProvider   = $this->getContainer()->get('oro_dashboard.config_provider');
        $this->dashboardManager = $this->getContainer()->get('oro_dashboard.manager');

        $this->widget = $this->createWidget();
        $this->em->persist($this->widget);
        $this->em->flush($this->widget);
    }

    public function testPut()
    {
        $data = [
            'isExpanded'     => 1,
            'layoutPosition' => [2, 20]
        ];

        $this->client->request(
            'PUT',
            $this->getUrl(
                'oro_api_put_dashboard_widget',
                [
                    'dashboardId' => $this->widget->getDashboard()->getId(),
                    'widgetId'    => $this->widget->getId(),
                ]
            ),
            $data
        );
        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);

        $this->em->refresh($this->widget);

        $model = $this->dashboardManager->findWidgetModel($this->widget->getId());
        $this->assertEquals($data['isExpanded'], $model->isExpanded());
        $this->assertEquals($data['layoutPosition'], $this->widget->getLayoutPosition());
        $this->assertEquals($data['layoutPosition'], $model->getLayoutPosition());
    }

    public function testAddWidget()
    {
        $widgets = $this->configProvider->getWidgetConfigs();

        $widgetNames = array_keys($widgets);

        $widgetName = $widgetNames[0];
        $id         = $this->widget->getDashboard()->getId();
        $this->client->request(
            'POST',
            $this->getUrl(
                'oro_api_post_dashboard_widget_add_widget'
            ),
            array('dashboardId' => $id, 'widgetName' => $widgetName)
        );

        $content = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertEquals($this->configProvider->getWidgetConfig($widgetName), $content['config']);
        $this->assertEquals($widgetName, $content['name']);
    }

    /**
     * @depends testPut
     */
    public function testDelete()
    {
        $this->client->request(
            'DELETE',
            $this->getUrl(
                'oro_api_delete_dashboard_widget',
                [
                    'dashboardId' => $this->widget->getDashboard()->getId(),
                    'widgetId'    => $this->widget->getId(),
                ]
            )
        );
        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);

        $this->client->request(
            'DELETE',
            $this->getUrl(
                'oro_api_delete_dashboard_widget',
                [
                    'dashboardId' => $this->widget->getDashboard()->getId(),
                    'widgetId'    => $this->widget->getId(),
                ]
            )
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 404);
    }

    /**
     * @param array $widgets
     * @param array $expectedPositions
     *
     * @dataProvider widgetsProvider
     */
    public function testPositions($widgets, $expectedPositions)
    {
        foreach ($widgets as $widget) {
            $this->em->persist($widget);
        }
        $this->em->flush();

        $dashboard = null;
        $data      = ['layoutPositions' => []];
        foreach ($widgets as $widget) {
            /* @var Widget $widget */
            $data['layoutPositions'][$widget->getId()] = array_map(
                function ($item) {
                    return $item * 10;
                },
                $widget->getLayoutPosition()
            );

            $dashboard = $widget->getDashboard();
        }

        $this->client->request(
            'PUT',
            $this->getUrl(
                'oro_api_put_dashboard_widget_positions',
                [
                    'dashboardId' => $dashboard->getId(),
                ]
            ),
            $data
        );

        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);

        /** @var  EntityManager $em */
        $em = $this->client->getContainer()->get('doctrine.orm.entity_manager');
        $widgetRepository = $em->getRepository('OroDashboardBundle:Widget');
        foreach ($widgets as $key => $widget) {
            $updatedWidget = $widgetRepository->findOneBy(['id' => $widget->getId()]);
            $this->assertEquals($expectedPositions[$key], $updatedWidget->getLayoutPosition());
        }
    }

    /**
     * @return array
     */
    public function widgetsProvider()
    {
        return [
            'multiple' => [
                'widgets'           => [
                    $this->createWidget(),
                    $this->createWidget('quick_launchpad', [2, 2]),
                ],
                'expectedPositions' => [
                    [10, 10],
                    [20, 20]
                ]
            ],
            'single'   => [
                'widgets'           => [
                    $this->createWidget()
                ],
                'expectedPositions' => [
                    [10, 10]
                ]
            ]
        ];
    }

    /**
     * @param string $name
     * @param array  $layoutPositions
     * @return Widget
     */
    protected function createWidget($name = 'quick_launchpad', array $layoutPositions = [1, 1])
    {
        $dashboard = new Dashboard();
        $dashboard->setName('main');

        $widget = new Widget();
        $widget
            ->setName($name)
            ->setLayoutPosition($layoutPositions)
            ->setDashboard($dashboard);

        $dashboard->addWidget($widget);

        return $widget;
    }
}
