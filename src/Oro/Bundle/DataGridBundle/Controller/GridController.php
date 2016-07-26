<?php

namespace Oro\Bundle\DataGridBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionDispatcher;
use Oro\Bundle\DataGridBundle\Exception\UserInputErrorExceptionInterface;
use Oro\Bundle\ImportExportBundle\Formatter\FormatterProvider;
use Oro\Bundle\ActionBundle\Datagrid\Extension\OperationExtension;

class GridController extends Controller
{
    const EXPORT_BATCH_SIZE = 200;

    /**
     * @Route(
     *      "/widget/{gridName}",
     *      name="oro_datagrid_widget",
     *      requirements={"gridName"="[\w\:-]+"}
     * )
     * @Template
     *
     * @param string $gridName
     *
     * @return Response
     */
    public function widgetAction($gridName)
    {
        return [
            'gridName'     => $gridName,
            'params'       => $this->getRequest()->get('params', []),
            'renderParams' => $this->getRenderParams(),
            'multiselect'  => (bool)$this->getRequest()->get('multiselect', false),
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

        if ($acl && !$this->get('oro_security.security_facade')->isGranted($acl)) {
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
    public function filterMetadata(Request $request, $gridName)
    {
        $filterNames = $request->query->get('filterNames', []);

        $gridManager = $this->get('oro_datagrid.datagrid.manager');
        $gridConfig  = $gridManager->getConfigurationForGrid($gridName);
        $acl         = $gridConfig->getAclResource();

        if ($acl && !$this->get('oro_security.security_facade')->isGranted($acl)) {
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
     * @param string $gridName
     *
     * @return Response
     */
    public function exportAction($gridName)
    {
        // Export time execution depends on a size of data
        ignore_user_abort(false);
        set_time_limit(0);

        $request     = $this->getRequest();
        $format      = $request->query->get('format');
        $csvWriterId = 'oro_importexport.writer.echo.csv';
        $writerId    = sprintf('oro_importexport.writer.echo.%s', $format);

        /** @var ItemWriterInterface $writer */
        $writer            = $this->has($writerId) ? $this->get($writerId) : $this->get($csvWriterId);
        $parametersFactory = $this->get('oro_datagrid.datagrid.request_parameters_factory');
        $parameters        = $parametersFactory->createParameters($gridName);
        $parameters->set(OperationExtension::OPERATION_ROOT_PARAM, [OperationExtension::DISABLED_PARAM => true]);
        $response = $this->get('oro_datagrid.handler.export')->handle(
            $this->get('oro_datagrid.importexport.export_connector'),
            $this->get('oro_datagrid.importexport.processor.export'),
            $writer,
            [
                'gridName'                     => $gridName,
                'gridParameters'               => $parameters,
                FormatterProvider::FORMAT_TYPE => $request->query->get('format_type', 'excel')
            ],
            self::EXPORT_BATCH_SIZE,
            $format
        );

        return $response->send();
    }

    /**
     * @Route(
     *      "/{gridName}/massAction/{actionName}",
     *      name="oro_datagrid_mass_action",
     *      requirements={"gridName"="[\w\:-]+", "actionName"="[\w-]+"}
     * )
     *
     * @param string $gridName
     * @param string $actionName
     *
     * @return Response
     * @throws \LogicException
     */
    public function massActionAction($gridName, $actionName)
    {
        $request = $this->getRequest();

        /** @var MassActionDispatcher $massActionDispatcher */
        $massActionDispatcher = $this->get('oro_datagrid.mass_action.dispatcher');
        $response             = $massActionDispatcher->dispatchByRequest($gridName, $actionName, $request);

        $data = [
            'successful' => $response->isSuccessful(),
            'message'    => $response->getMessage(),
        ];

        return new JsonResponse(array_merge($data, $response->getOptions()));
    }

    /**
     * @return array
     */
    protected function getRenderParams()
    {
        $renderParams      = $this->getRequest()->get('renderParams', []);
        $renderParamsTypes = $this->getRequest()->get('renderParamsTypes', []);

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
}
