<?php

namespace Oro\Bundle\ReportBundle\Controller;

use Doctrine\DBAL\Types\Type;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Oro\Bundle\DashboardBundle\Helper\DateHelper;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Extension\Pager\PagerInterface;
use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\ReportBundle\Entity\ReportType;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

class ReportController extends Controller
{
    /**
     * @Route("/view/{id}", name="oro_report_view", requirements={"id"="\d+"}, defaults={"id"=0})
     * @Acl(
     *      id="oro_report_view",
     *      type="entity",
     *      permission="VIEW",
     *      class="OroReportBundle:Report"
     * )
     */
    public function viewAction(Report $entity)
    {
        $this->get('oro_segment.entity_name_provider')->setCurrentItem($entity);

        $reportGroup = $this->get('oro_entity_config.provider.entity')
            ->getConfig($entity->getEntity())
            ->get('plural_label');
        $parameters  = [
            'entity'      => $entity,
            'reportGroup' => $reportGroup
        ];

        $reportType = $entity->getType()->getName();
        if ($reportType === ReportType::TYPE_TABLE) {
            $gridName = $entity::GRID_PREFIX . $entity->getId();

            if ($this->get('oro_report.datagrid.configuration.provider')->isReportValid($gridName)) {
                $parameters['gridName'] = $gridName;

                $datagrid = $this->get('oro_datagrid.datagrid.manager')->getDatagrid(
                    $gridName,
                    [PagerInterface::PAGER_ROOT_PARAM => [PagerInterface::DISABLED_PARAM => true]]
                );

                $chartOptions = $this->get('oro_chart.options_builder')->buildOptions(
                    $entity->getChartOptions(),
                    $datagrid->getConfig()->toArray()
                );

                if (!empty($chartOptions)) {
                    $chartOptions = $this->processChartOptions($datagrid, $chartOptions);

                    $parameters['chartView'] = $this->get('oro_chart.view_builder')
                        ->setDataGrid($datagrid)
                        ->setOptions($chartOptions)
                        ->getView();
                }
            }
        }

        return $this->render(
            sprintf('OroReportBundle:Report:%s/view.html.twig', strtolower($reportType)),
            $parameters
        );
    }

    /**
     * @Route("/create", name="oro_report_create")
     * @Template("OroReportBundle:Report:update.html.twig")
     * @Acl(
     *      id="oro_report_create",
     *      type="entity",
     *      permission="CREATE",
     *      class="OroReportBundle:Report"
     * )
     */
    public function createAction()
    {
        return $this->update(new Report());
    }

    /**
     * @Route("/update/{id}", name="oro_report_update", requirements={"id"="\d+"}, defaults={"id"=0})
     *
     * @Template
     * @Acl(
     *      id="oro_report_update",
     *      type="entity",
     *      permission="EDIT",
     *      class="OroReportBundle:Report"
     * )
     */
    public function updateAction(Report $entity)
    {
        return $this->update($entity);
    }

    /**
     * @Route(
     *      "/{_format}",
     *      name="oro_report_index",
     *      requirements={"_format"="html|json"},
     *      defaults={"_format" = "html"}
     * )
     *
     * @Template
     * @AclAncestor("oro_report_view")
     */
    public function indexAction()
    {
        return [];
    }

    /**
     * @param Report $entity
     * @return array
     */
    protected function update(Report $entity)
    {
        $this->get('oro_segment.entity_name_provider')->setCurrentItem($entity);
        if ($this->get('oro_report.form.handler.report')->process($entity)) {
            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('Report saved')
            );

            return $this->get('oro_ui.router')->redirect($entity);
        }

        return [
            'entity'   => $entity,
            'form'     => $this->get('oro_report.form.report')->createView(),
            'entities' => $this->get('oro_report.entity_provider')->getEntities(),
            'metadata' => $this->get('oro_query_designer.query_designer.manager')->getMetadata('report')
        ];
    }

    /**
     * Method detects type of report's chart 'label' field, and in case of datetime will check dates interval and
     * set proper type (time, day, date, month or year). Xaxis labels not taken into account - they will be rendered
     * automatically. Also chart dot labels may overlap if dates are close to each other.
     *
     * Should be refactored in scope of BAP-8294.
     *
     * @param DatagridInterface $datagrid
     * @param array             $chartOptions
     *
     * @return array
     */
    protected function processChartOptions(DatagridInterface $datagrid, array $chartOptions)
    {
        $labelFieldName = $chartOptions['data_schema']['label'];
        $labelFieldType = $datagrid->getConfig()->offsetGetByPath(
            sprintf('[columns][%s][frontend_type]', $labelFieldName)
        );

        /** @var DateHelper $dateTimeHelper */
        $dateTimeHelper = $this->get('oro_dashboard.datetime.helper');
        $dateTypes      = [Type::DATETIME, Type::DATE, Type::DATETIMETZ];

        if (in_array($labelFieldType, $dateTypes)) {
            $data  = $datagrid->getData()->offsetGet('data');
            $dates = array_map(
                function ($dataItem) use ($labelFieldName) {
                    return $dataItem[$labelFieldName];
                },
                $data
            );

            $minDate = new \DateTime(min($dates));
            $maxDate = new \DateTime(max($dates));

            $formatStrings = $dateTimeHelper->getFormatStrings($minDate, $maxDate);

            $chartOptions['data_schema']['label'] = [
                'field_name'   => $chartOptions['data_schema']['label'],
                'type'         => $formatStrings['viewType']
            ];
        }

        return $chartOptions;
    }
}
