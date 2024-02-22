<?php

namespace Oro\Bundle\ReportBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ChartBundle\Model\ChartOptionsBuilder;
use Oro\Bundle\ChartBundle\Model\ChartViewBuilder;
use Oro\Bundle\DashboardBundle\Helper\DateHelper;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\DataGridBundle\Extension\Pager\PagerInterface;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
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
use Symfony\Component\Form\FormFactoryInterface;
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
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            'oro_report.entity_provider' => EntityProvider::class,
            ConfigManager::class,
            EntityNameProvider::class,
            QueryDesignerManager::class,
            Manager::class,
            ReportDatagridConfigurationProvider::class,
            ChartViewBuilder::class,
            TranslatorInterface::class,
            ReportHandler::class,
            Router::class,
            FeatureChecker::class,
            DateHelper::class,
            'form.factory' => FormFactoryInterface::class,
            'doctrine' => ManagerRegistry::class,
            EntityFieldProvider::class
        ]);
    }

    /**
     * @Route("/view/{id}", name="oro_report_view", requirements={"id"="\d+"})
     * @Acl(
     *      id="oro_report_view",
     *      type="entity",
     *      permission="VIEW",
     *      class="Oro\Bundle\ReportBundle\Entity\Report"
     * )
     * @param Report $entity
     *
     * @return Response
     */
    public function viewAction(Report $entity)
    {
        $this->checkReport($entity);
        $this->container->get(EntityNameProvider::class)->setCurrentItem($entity);

        $reportGroup = $this->container->get(ConfigManager::class)
            ->getEntityConfig('entity', $entity->getEntity())
            ->get('plural_label');
        $parameters = [
            'entity' => $entity,
            'reportGroup' => $reportGroup
        ];

        $reportType = $entity->getType()->getName();
        if ($reportType === ReportType::TYPE_TABLE) {
            $gridName = $entity::GRID_PREFIX . $entity->getId();

            if ($this->container->get(ReportDatagridConfigurationProvider::class)->isReportValid($gridName)) {
                $parameters['gridName'] = $gridName;

                $datagrid = $this->container->get(Manager::class)->getDatagrid(
                    $gridName,
                    [PagerInterface::PAGER_ROOT_PARAM => [PagerInterface::DISABLED_PARAM => true]]
                );

                if (!empty($entity->getChartOptions())) {
                    $chartOptions = $this->buildOptions($entity, $datagrid);

                    $parameters['chartView'] = $this->container->get(ChartViewBuilder::class)
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

    private function buildOptions(Report $report, DatagridInterface $datagrid): array
    {
        $fieldProvider = $this->container->get(EntityFieldProvider::class);
        $dateHelper = $this->container->get(DateHelper::class);
        $chartOptionsBuilder = new ChartOptionsBuilder($fieldProvider, $dateHelper, $report, $datagrid);

        return $chartOptionsBuilder->buildChartOptions();
    }

    /**
     * @Route("/view/{gridName}", name="oro_report_view_grid", requirements={"gridName"="[-\w]+"})
     * @Template
     * @Acl(
     *      id="oro_report_view",
     *      type="entity",
     *      permission="VIEW",
     *      class="Oro\Bundle\ReportBundle\Entity\Report"
     * )
     *
     * @param string $gridName
     *
     * @return array
     */
    public function viewFromGridAction($gridName)
    {
        $configuration = $this->container->get(Manager::class)->getConfigurationForGrid($gridName);
        $pageTitle = isset($configuration['pageTitle']) ? $configuration['pageTitle'] : $gridName;

        return [
            'pageTitle' => $this->container->get(TranslatorInterface::class)->trans($pageTitle),
            'gridName' => $gridName,
        ];
    }

    /**
     * @Route("/create", name="oro_report_create")
     * @Template("@OroReport/Report/update.html.twig")
     * @Acl(
     *      id="oro_report_create",
     *      type="entity",
     *      permission="CREATE",
     *      class="Oro\Bundle\ReportBundle\Entity\Report"
     * )
     */
    public function createAction(Request $request)
    {
        return $this->update(new Report(), $request);
    }

    /**
     * @Route("/update/{id}", name="oro_report_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_report_update",
     *      type="entity",
     *      permission="EDIT",
     *      class="Oro\Bundle\ReportBundle\Entity\Report"
     * )
     *
     * @param Report $entity
     * @param Request $request
     *
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
     * @param Report $entity
     *
     * @return array
     */
    public function cloneAction(Report $entity, Request $request)
    {
        $this->checkReport($entity);

        $clonedEntity = clone $entity;
        $clonedEntity->setName(
            $this->container->get(TranslatorInterface::class)->trans(
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
     *
     * @return array|RedirectResponse
     */
    protected function update(Report $entity, Request $request)
    {
        $reportForm = $this->container->get('form.factory')->createNamed(
            'oro_report_form',
            ReportFormType::class,
            $entity
        );
        $this->container->get(EntityNameProvider::class)->setCurrentItem($entity);
        if ($this->container->get(ReportHandler::class)->process($entity, $reportForm)) {
            $request->getSession()->getFlashBag()->add(
                'success',
                $this->container->get(TranslatorInterface::class)->trans('Report saved')
            );

            return $this->container->get(Router::class)->redirect($entity);
        }

        return [
            'entity' => $entity,
            'form' => $reportForm->createView(),
            'entities' => $this->container->get('oro_report.entity_provider')->getEntities(),
            'metadata' => $this->container->get(QueryDesignerManager::class)->getMetadata('report')
        ];
    }

    protected function checkReport(Report $report)
    {
        if ($report->getEntity() &&
            !$this->container->get(FeatureChecker::class)->isResourceEnabled($report->getEntity(), 'entities')) {
            throw $this->createNotFoundException();
        }
    }
}
