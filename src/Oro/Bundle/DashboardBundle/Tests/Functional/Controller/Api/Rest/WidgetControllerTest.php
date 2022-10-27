<?php

namespace Oro\Bundle\DashboardBundle\Tests\Functional\Controller\Api\Rest;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\DashboardBundle\Entity\Widget;
use Oro\Bundle\DashboardBundle\Model\ConfigProvider;
use Oro\Bundle\DashboardBundle\Model\Manager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class WidgetControllerTest extends WebTestCase
{
    /** @var EntityManager */
    private $em;

    /** @var Widget */
    private $widget;

    /** @var ConfigProvider */
    private $configProvider;

    /** @var Manager */
    private $dashboardManager;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->configProvider = $this->getContainer()->get('oro_dashboard.config_provider');
        $this->dashboardManager = $this->getContainer()->get('oro_dashboard.manager');

        $this->widget = $this->createWidget();
    }

    public function testPut()
    {
        $data = [
            'isExpanded'     => 1,
            'layoutPosition' => [2, 20]
        ];

        $this->client->jsonRequest(
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
        $id = $this->widget->getDashboard()->getId();
        $this->client->jsonRequest(
            'POST',
            $this->getUrl(
                'oro_api_post_dashboard_widget_add_widget'
            ),
            ['dashboardId' => $id, 'widgetName' => $widgetName]
        );

        $content = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertArrayHasKey('id', $content);
        unset($content['id']);
        $this->assertEquals(
            [
                'name'            => $widgetName,
                'config'          => $this->configProvider->getWidgetConfig($widgetName),
                'layout_position' => [0, 0],
                'expanded'        => true
            ],
            $content
        );
    }

    /**
     * @depends testPut
     */
    public function testDelete()
    {
        $this->client->jsonRequest(
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

        $this->client->jsonRequest(
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
     * @dataProvider widgetsProvider
     */
    public function testPositions(array $widgetsData, array $expectedPositions)
    {
        $widgets = [];
        foreach ($widgetsData as $widgetData) {
            $widgets[] = $this->createWidget(
                $widgetData['name'],
                $widgetData['layoutPosition']
            );
        }

        $dashboard = null;
        $data = ['layoutPositions' => []];
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

        $this->client->jsonRequest(
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

        /** @var EntityManagerInterface $em */
        $em = $this->client->getContainer()->get('doctrine.orm.entity_manager');
        $widgetRepository = $em->getRepository(Widget::class);
        foreach ($widgets as $key => $widget) {
            $updatedWidget = $widgetRepository->findOneBy(['id' => $widget->getId()]);
            $this->assertEquals($expectedPositions[$key], $updatedWidget->getLayoutPosition());
        }
    }

    public function widgetsProvider(): array
    {
        return [
            'multiple' => [
                'widgets'           => [
                    ['name' => 'quick_launchpad', 'layoutPosition' => [1, 1]],
                    ['name' => 'quick_launchpad', 'layoutPosition' => [2, 2]],
                ],
                'expectedPositions' => [
                    [10, 10],
                    [20, 20]
                ]
            ],
            'single'   => [
                'widgets'           => [
                    ['name' => 'quick_launchpad', 'layoutPosition' => [1, 1]],
                ],
                'expectedPositions' => [
                    [10, 10]
                ]
            ]
        ];
    }

    private function createWidget(string $name = 'quick_launchpad', array $layoutPositions = [1, 1]): Widget
    {
        $dashboard = new Dashboard();
        $dashboard->setName('main');

        $widget = new Widget();
        $widget
            ->setName($name)
            ->setLayoutPosition($layoutPositions)
            ->setDashboard($dashboard);

        $dashboard->addWidget($widget);

        $this->em->persist($widget);
        $this->em->flush();

        return $widget;
    }
}
