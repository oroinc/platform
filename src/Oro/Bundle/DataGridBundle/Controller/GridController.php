<?php

namespace Oro\Bundle\DataGridBundle\Controller;

use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionDispatcher;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Oro\Bundle\BatchBundle\Step\StepExecutor;
use Oro\Bundle\ImportExportBundle\Context\Context as ExportContext;
use Oro\Bundle\ImportExportBundle\MimeType\MimeTypeGuesser;

class GridController extends Controller
{
    const EXPORT_BATCH_SIZE = 200;

    /**
     * @Route(
     *      "/widget/{gridName}",
     *      name="oro_datagrid_widget",
     *      requirements={"gridName"="[\w-]+"}
     * )
     * @Template
     *
     * @param string $gridName
     *
     * @return Response
     */
    public function widgetAction($gridName)
    {
        return array(
            'gridName' => $gridName,
            'renderParams' => $this->getRequest()->get('renderParams', array())
        );
    }

    /**
     * @Route(
     *      "/{gridName}",
     *      name="oro_datagrid_index",
     *      requirements={"gridName"="[\w-]+"}
     * )
     *
     * @param string $gridName
     *
     * @return Response
     */
    public function getAction($gridName)
    {
        $parameters = (array)$this->getRequest()->query->get($gridName, array());
        $grid = $this->get('oro_datagrid.datagrid.manager')->getDatagrid($gridName, $parameters);
        $result = $grid->getData();

        return new JsonResponse($result->toArray());
    }

    /**
     * @Route(
     *      "/{gridName}/export/",
     *      name="oro_datagrid_export_action",
     *      requirements={"gridName"="[\w-]+"}
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

        $request = $this->getRequest();
        $format  = $request->query->get('format');

        $context = new ExportContext(['gridName' => $gridName]);

        // prepare export executor
        $executor = new StepExecutor();
        $executor->setBatchSize(self::EXPORT_BATCH_SIZE);
        $executor
            ->setReader($this->get('oro_datagrid.importexport.export_connector'))
            ->setProcessor($this->get('oro_datagrid.importexport.processor.export'))
            ->setWriter($this->get(sprintf('oro_importexport.writer.echo.%s', $format)));
        foreach ([$executor->getReader(), $executor->getProcessor(), $executor->getWriter()] as $element) {
            if ($element instanceof ContextAwareInterface) {
                $element->setImportExportContext($context);
            }
        }

        /** @var MimeTypeGuesser $mimeTypeGuesser */
        $mimeTypeGuesser = $this->get('oro_importexport.file.mime_type_guesser');
        $contentType     = $mimeTypeGuesser->guessByFileExtension($format);
        if (!$contentType) {
            $contentType = 'application/octet-stream';
        }

        // prepare response
        $response = new StreamedResponse($this->exportCallback($context, $executor));
        $response->headers->set('Content-Type', $contentType);
        $outputFileName = sprintf('datagrid_%s_%s.%s', str_replace('-', '_', $gridName), date('Y_m_d_H_i_s'), $format);
        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $outputFileName)
        );

        return $response->send();
    }

    /**
     * @Route(
     *      "/{gridName}/massAction/{actionName}",
     *      name="oro_datagrid_mass_action",
     *      requirements={"gridName"="[\w-]+", "actionName"="[\w-]+"}
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
        $response = $massActionDispatcher->dispatchByRequest($gridName, $actionName, $request);

        $data = [
            'successful' => $response->isSuccessful(),
            'message'    => $response->getMessage(),
        ];

        return new JsonResponse(array_merge($data, $response->getOptions()));
    }

    /**
     * @param ContextInterface $context
     * @param StepExecutor     $executor
     * @return \Closure
     */
    protected function exportCallback(ContextInterface $context, StepExecutor $executor)
    {
        return function () use ($executor) {
            flush();
            $executor->execute();
        };
    }
}
