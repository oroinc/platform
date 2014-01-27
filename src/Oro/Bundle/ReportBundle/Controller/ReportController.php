<?php

namespace Oro\Bundle\ReportBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Oro\Bundle\ReportBundle\Entity\Report;
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
        $reportType = strtolower($entity->getType()->getName());
        $reportGroup = $this->get('oro_entity_config.provider.entity')
            ->getConfig($entity->getEntity())
            ->get('plural_label');
        $parameters = [
            'entity'      => $entity,
            'reportGroup' => $reportGroup
        ];

        if ($reportType === 'table') {
            $gridName = sprintf('oro_report_table_%d', $entity->getId());
            if ($this->get('oro_report.datagrid.configuration.provider')->isReportValid($gridName)) {
                $parameters['gridName'] = $gridName;
            }
        }

        return $this->render(
            sprintf('OroReportBundle:Report:%s/view.html.twig', $reportType),
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
        return array();
    }

    protected function update(Report $entity)
    {
        if ($this->get('oro_report.form.handler.report')->process($entity)) {
            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('Report saved')
            );

            return $this->get('oro_ui.router')->actionRedirect(
                array(
                    'route'      => 'oro_report_update',
                    'parameters' => array('id' => $entity->getId()),
                ),
                array(
                    'route'      => 'oro_report_view',
                    'parameters' => array('id' => $entity->getId()),
                )
            );
        }

        return array(
            'entity'   => $entity,
            'form'     => $this->get('oro_report.form.report')->createView(),
            'entities' => $this->get('oro_report.entity_provider')->getEntities(),
            'metadata' => $this->get('oro_query_designer.query_designer.manager')->getMetadata('report')
        );
    }
}
