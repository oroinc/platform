<?php

namespace Oro\Bundle\DataGridBundle\Controller;

use Oro\Bundle\DataGridBundle\Async\Topics;
use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\DataGridBundle\Datagrid\RequestParameterBagFactory;
use Oro\Bundle\DataGridBundle\Exception\LogicException;
use Oro\Bundle\DataGridBundle\Exception\UserInputErrorExceptionInterface;
use Oro\Bundle\DataGridBundle\Extension\Export\Configuration;
use Oro\Bundle\DataGridBundle\Extension\Export\ExportExtension;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionDispatcher;
use Oro\Bundle\ImportExportBundle\Formatter\FormatterProvider;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Provides the ability to control of the Grid
 */
class GridController extends Controller
{
    /**
     * @Route(
     *      "/widget/{gridName}",
     *      name="oro_datagrid_widget",
     *      requirements={"gridName"="[\w\:-]+"}
     * )
     * @Template
     * @param Request $request
     * @param string $gridName
     *
     * @return array
     */
    public function widgetAction(Request $request, $gridName)
    {
        return [
            'gridName'     => $gridName,
            'params'       => $request->get('params', []),
            'renderParams' => $this->getRenderParams($request),
            'multiselect'  => (bool)$request->get('multiselect', false),
        ];
    }

    /**
     * @Route(
     *      "/{gridName}",
     *      name="oro_datagrid_index",
     *      requirements={"gridName"="[\w\:-]+"}
     * )
     *
     * @param string $gridName
     *
     * @return Response
     * @throws \Exception
     */
    public function getAction($gridName)
    {
        $gridManager = $this->get('oro_datagrid.datagrid.manager');
        $gridConfig  = $gridManager->getConfigurationForGrid($gridName);
        $acl         = $gridConfig->getAclResource();

        if ($acl && !$this->isGranted($acl)) {
            throw new AccessDeniedException('Access denied.');
        }

        $grid = $gridManager->getDatagridByRequestParams($gridName);

        try {
            $result = $grid->getData();
        } catch (\Exception $e) {
            if ($e instanceof UserInputErrorExceptionInterface) {
                return new JsonResponse(
                    [
                        'type'    => UserInputErrorExceptionInterface::TYPE,
                        'message' => $this->get('translator')->trans($e->getMessageTemplate(), $e->getMessageParams())
                    ],
                    500
                );
            }
            throw $e;
        }

        return new JsonResponse($result->toArray());
    }

    /**
     * @Route("/{gridName}/filter-metadata", name="oro_datagrid_filter_metadata", options={"expose"=true})
     */
    public function filterMetadataAction(Request $request, $gridName)
    {
        $filterNames = $request->query->get('filterNames', []);

        $gridManager = $this->get('oro_datagrid.datagrid.manager');
        $gridConfig  = $gridManager->getConfigurationForGrid($gridName);
        $acl         = $gridConfig->getAclResource();

        if ($acl && !$this->isGranted($acl)) {
            throw new AccessDeniedException('Access denied.');
        }

        $grid = $gridManager->getDatagridByRequestParams($gridName);
        $meta = $grid->getResolvedMetadata();

        $filterData = [];
        foreach ($meta['filters'] as $filter) {
            if (!in_array($filter['name'], $filterNames)) {
                continue;
            }

            $filterData[$filter['name']] = $filter;
        }

        return new JsonResponse($filterData);
    }

    /**
     * @Route(
     *      "/{gridName}/export/",
     *      name="oro_datagrid_export_action",
     *      requirements={"gridName"="[\w\:-]+"}
     * )
     *
     * @AclAncestor("oro_datagrid_gridview_export")
     *
     * @param Request $request
     * @param string $gridName
     *
     * @return JsonResponse
     */
    public function exportAction(Request $request, $gridName)
    {
        $format = $request->query->get('format');
        $formatType = $request->query->get('format_type', 'excel');
        $gridParameters = $this->getRequestParametersFactory()->fetchParameters($gridName);
        $parameters = [
            'gridName' => $gridName,
            'gridParameters' => $gridParameters,
            FormatterProvider::FORMAT_TYPE => $formatType,
        ];

        $gridConfiguration = $this->getGridManager()->getConfigurationForGrid($gridName);
        $exportOptions = $gridConfiguration->offsetGetByPath(ExportExtension::EXPORT_OPTION_PATH);
        if (isset($exportOptions[$format][Configuration::OPTION_PAGE_SIZE])) {
            $parameters['pageSize'] = (int)$exportOptions[$format][Configuration::OPTION_PAGE_SIZE];
        }

        $this->getMessageProducer()->send(Topics::PRE_EXPORT, [
            'format' => $format,
            'parameters' => $parameters,
            'notificationTemplate' => 'datagrid_export_result',
        ]);

        return new JsonResponse([
            'successful' => true,
        ]);
    }

    /**
     * @Route(
     *      "/{gridName}/massAction/{actionName}",
     *      name="oro_datagrid_mass_action",
     *      requirements={"gridName"="[\w\:-]+", "actionName"="[\w-]+"}
     * )
     * @param Request $request
     * @param string $gridName
     * @param string $actionName
     *
     * @return Response
     * @throws \LogicException
     */
    public function massActionAction(Request $request, $gridName, $actionName)
    {
        /* @var $tokenManager CsrfTokenManagerInterface */
        $tokenManager = $this->get('security.csrf.token_manager');
        if (!$tokenManager->isTokenValid(new CsrfToken($actionName, $request->get('token')))) {
            return new JsonResponse('Invalid CSRF Token', JsonResponse::HTTP_FORBIDDEN);
        }

        /** @var MassActionDispatcher $massActionDispatcher */
        $massActionDispatcher = $this->get('oro_datagrid.mass_action.dispatcher');

        try {
            $response = $massActionDispatcher->dispatchByRequest($gridName, $actionName, $request);
        } catch (LogicException $e) {
            return new JsonResponse(null, JsonResponse::HTTP_FORBIDDEN);
        }

        $data = [
            'successful' => $response->isSuccessful(),
            'message'    => $response->getMessage(),
        ];

        return new JsonResponse(array_merge($data, $response->getOptions()));
    }

    /**
     * @param Request $request
     * @return array
     */
    protected function getRenderParams(Request $request)
    {
        $renderParams      = $request->get('renderParams', []);
        $renderParamsTypes = $request->get('renderParamsTypes', []);

        foreach ($renderParamsTypes as $param => $type) {
            if (array_key_exists($param, $renderParams)) {
                switch ($type) {
                    case 'bool':
                    case 'boolean':
                        $renderParams[$param] = (bool)$renderParams[$param];
                        break;
                    case 'int':
                    case 'integer':
                        $renderParams[$param] = (int)$renderParams[$param];
                        break;
                }
            }
        }

        return $renderParams;
    }

    /**
     * @return MessageProducerInterface
     */
    protected function getMessageProducer()
    {
        return $this->get('oro_message_queue.client.message_producer');
    }

    /**
     * @return RequestParameterBagFactory
     */
    protected function getRequestParametersFactory()
    {
        return $this->get('oro_datagrid.datagrid.request_parameters_factory');
    }

    /**
     * @return Manager
     */
    protected function getGridManager()
    {
        return $this->get('oro_datagrid.datagrid.manager');
    }
}
