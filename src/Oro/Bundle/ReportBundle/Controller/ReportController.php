<?php

namespace Oro\Bundle\ReportBundle\Controller;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\ChartBundle\Model\ChartOptionsBuilder;
use Oro\Bundle\ChartBundle\Model\ChartViewBuilder;
use Oro\Bundle\DashboardBundle\Helper\DateHelper;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\DataGridBundle\Extension\Pager\PagerInterface;
use Oro\Bundle\EntityBundle\Provider\EntityProvider;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\Manager as QueryDesignerManager;
use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\ReportBundle\Entity\ReportType;
use Oro\Bundle\ReportBundle\Form\Handler\ReportHandler;
use Oro\Bundle\ReportBundle\Form\Type\ReportType as ReportFormType;
use Oro\Bundle\ReportBundle\Grid\ReportDatagridConfigurationProvider;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SegmentBundle\Provider\EntityNameProvider;
use Oro\Bundle\UIBundle\Route\Router;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Covers the CRUD functionality and the additional operation clone for the Report entity.
 */
class ReportController extends AbstractController
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), [
            'oro_report.entity_provider' => EntityProvider::class,
            ConfigManager::class,
            EntityNameProvider::class,
            QueryDesignerManager::class,
            Manager::class,
            ReportDatagridConfigurationProvider::class,
            ChartViewBuilder::class,
            ChartOptionsBuilder::class,
            TranslatorInterface::class,
            ReportHandler::class,
            Router::class,
            FeatureChecker::class,
            DateHelper::class,
        ]);
    }

    /**
     * @Route("/view/{id}", name="oro_report_view", requirements={"id"="\d+"})
     * @Acl(
     *      id="oro_report_view",
     *      type="entity",
     *      permission="VIEW",
     *      class="OroReportBundle:Report"
     * )
     *
     * @param Report $entity
     * @return Response
     */
    public function viewAction(Report $entity)
    {
        $this->checkReport($entity);
        $this->get(EntityNameProvider::class)->setCurrentItem($entity);

        $reportGroup = $this->get(ConfigManager::class)
            ->getEntityConfig('entity', $entity->getEntity())
            ->get('plural_label');
        $parameters  = [
            'entity'      => $entity,
            'reportGroup' => $reportGroup
        ];

        $reportType = $entity->getType()->getName();
        if ($reportType === ReportType::TYPE_TABLE) {
            $gridName = $entity::GRID_PREFIX . $entity->getId();

            if ($this->get(ReportDatagridConfigurationProvider::class)->isReportValid($gridName)) {
                $parameters['gridName'] = $gridName;

                $datagrid = $this->get(Manager::class)->getDatagrid(
                    $gridName,
                    [PagerInterface::PAGER_ROOT_PARAM => [PagerInterface::DISABLED_PARAM => true]]
                );

                $chartOptions = $this->get(ChartOptionsBuilder::class)->buildOptions(
                    $entity->getChartOptions(),
                    $datagrid->getConfig()->toArray()
                );

                if (!empty($chartOptions)) {
                    $chartOptions = $this->processChartOptions($datagrid, $chartOptions);

                    $parameters['chartView'] = $this->get(ChartViewBuilder::class)
                        ->setDataGrid($datagrid)
                        ->setOptions($chartOptions)
                        ->getView();
                }
            }
        }

        return $this->render(
            sprintf('@OroReport/Report/%s/view.html.twig', strtolower($reportType)),
            $parameters
        );
    }

    /**
     * @Route("/view/{gridName}", name="oro_report_view_grid", requirements={"gridName"="[-\w]+"})
     *
     * @Template
     * @Acl(
     *      id="oro_report_view",
     *      type="entity",
     *      permission="VIEW",
     *      class="OroReportBundle:Report"
     * )
     *
     * @param string $gridName
     * @return array
     */
    public function viewFromGridAction($gridName)
    {
        $configuration = $this->get(Manager::class)->getConfigurationForGrid($gridName);
        $pageTitle = isset($configuration['pageTitle']) ? $configuration['pageTitle'] : $gridName;

        return [
            'pageTitle' => $this->get(TranslatorInterface::class)->trans($pageTitle),
            'gridName'  => $gridName,
        ];
    }

    /**
     * @Route("/create", name="oro_report_create")
     * @Template("@OroReport/Report/update.html.twig")
     * @Acl(
     *      id="oro_report_create",
     *      type="entity",
     *      permission="CREATE",
     *      class="OroReportBundle:Report"
     * )
     */
    public function createAction(Request $request)
    {
        return $this->update(new Report(), $request);
    }

    /**
     * @Route("/update/{id}", name="oro_report_update", requirements={"id"="\d+"})
     *
     * @Template
     * @Acl(
     *      id="oro_report_update",
     *      type="entity",
     *      permission="EDIT",
     *      class="OroReportBundle:Report"
     * )
     *
     * @param Report $entity
     * @param Request $request
     * @return array
     */
    public function updateAction(Report $entity, Request $request)
    {
        $this->checkReport($entity);

        return $this->update($entity, $request);
    }

    /**
     * @Route("/clone/{id}", name="oro_report_clone", requirements={"id"="\d+"})
     * @Template("@OroReport/Report/update.html.twig")
     * @AclAncestor("oro_report_create")
     *
     * @param Report $entity
     * @return array
     */
    public function cloneAction(Report $entity, Request $request)
    {
        $this->checkReport($entity);

        $clonedEntity = clone $entity;
        $clonedEntity->setName(
            $this->get(TranslatorInterface::class)->trans(
                'oro.report.action.clone.name_format',
                [
                    '{name}' => $clonedEntity->getName()
                ]
            )
        );

        return $this->update($clonedEntity, $request);
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
     * @param Request $request
     * @return array|RedirectResponse
     */
    protected function update(Report $entity, Request $request)
    {
        $reportForm = $this->get('form.factory')->createNamed(
            'oro_report_form',
            ReportFormType::class,
            $entity
        );
        $this->get(EntityNameProvider::class)->setCurrentItem($entity);
        if ($this->get(ReportHandler::class)->process($entity, $reportForm)) {
            $request->getSession()->getFlashBag()->add(
                'success',
                $this->get(TranslatorInterface::class)->trans('Report saved')
            );

            return $this->get(Router::class)->redirect($entity);
        }

        return [
            'entity'   => $entity,
            'form'     => $reportForm->createView(),
            'entities' => $this->get('oro_report.entity_provider')->getEntities(),
            'metadata' => $this->get(QueryDesignerManager::class)->getMetadata('report')
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
        $dateTimeHelper = $this->get(DateHelper::class);
        $dateTypes      = [Types::DATETIME_MUTABLE, Types::DATE_MUTABLE, Types::DATETIMETZ_MUTABLE];

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

    protected function checkReport(Report $report)
    {
        if ($report->getEntity() &&
            !$this->get(FeatureChecker::class)->isResourceEnabled($report->getEntity(), 'entities')) {
            throw $this->createNotFoundException();
        }
    }
}
