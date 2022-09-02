<?php

namespace Oro\Bundle\DataGridBundle\Controller;

use Oro\Bundle\DataGridBundle\Async\Topic\DatagridPreExportTopic;
use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\DataGridBundle\Datagrid\RequestParameterBagFactory;
use Oro\Bundle\DataGridBundle\Exception\LogicException;
use Oro\Bundle\DataGridBundle\Exception\UserInputErrorExceptionInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionDispatcher;
use Oro\Bundle\ImportExportBundle\Formatter\FormatterProvider;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\CsrfProtection;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides the ability to control of the Grid
 */
class GridController extends AbstractController
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
        $gridManager = $this->get(Manager::class);
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
                        'message' => $this
                            ->get(TranslatorInterface::class)->trans($e->getMessageTemplate(), $e->getMessageParams())
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

        $gridManager = $this->get(Manager::class);
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
        $gridParameters = $this->container->get(RequestParameterBagFactory::class)->fetchParameters($gridName);

        $this->container->get(MessageProducerInterface::class)
            ->send(
                DatagridPreExportTopic::getName(),
                [
                    'outputFormat' => $format,
                    'contextParameters' => [
                        'gridName' => $gridName,
                        'gridParameters' => $gridParameters,
                        FormatterProvider::FORMAT_TYPE => $formatType,
                    ],
                ]
            );

        return new JsonResponse([
            'successful' => true,
        ]);
    }

    /**
     * @Route(
     *      "/{gridName}/massAction/{actionName}",
     *      name="oro_datagrid_mass_action",
     *      requirements={"gridName"="[\w\:\-]+", "actionName"="[\w\-]+"}
     * )
     * @CsrfProtection()
     *
     * @param Request $request
     * @param string $gridName
     * @param string $actionName
     *
     * @return Response
     * @throws \LogicException
     */
    public function massActionAction(Request $request, $gridName, $actionName)
    {
        $massActionDispatcher = $this->get(MassActionDispatcher::class);

        try {
            $response = $massActionDispatcher->dispatchByRequest($gridName, $actionName, $request);
        } catch (LogicException $e) {
            if ($this->get(KernelInterface::class)->isDebug()) {
                throw $e;
            } else {
                $this->get(LoggerInterface::class)->error($e->getMessage());
                return new JsonResponse(null, JsonResponse::HTTP_FORBIDDEN);
            }
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
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                MessageProducerInterface::class,
                MassActionDispatcher::class,
                RequestParameterBagFactory::class,
                Manager::class,
                KernelInterface::class,
                LoggerInterface::class,
            ]
        );
    }
}
