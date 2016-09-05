<?php

namespace Oro\Bundle\DataAuditBundle\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

use Oro\Bundle\SegmentBundle\Event\WidgetOptionsLoadEvent;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\DataAuditBundle\SegmentWidget\ContextChecker;

class SegmentWidgetOptionsListener
{
    /** @var Request */
    protected $request;

    /** @var HttpKernelInterface */
    protected $httpKernel;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var ContextChecker */
    protected $contextChecker;

    /**
     * @param HttpKernelInterface $httpKernel
     * @param SecurityFacade      $securityFacade
     * @param ContextChecker      $contextChecker
     */
    public function __construct(
        HttpKernelInterface $httpKernel,
        SecurityFacade $securityFacade,
        ContextChecker $contextChecker
    ) {
        $this->httpKernel     = $httpKernel;
        $this->securityFacade = $securityFacade;
        $this->contextChecker = $contextChecker;
    }

    /**
     * @param Request $request
     */
    public function setRequest(Request $request = null)
    {
        $this->request = $request;
    }

    /**
     * @param WidgetOptionsLoadEvent $event
     */
    public function onLoad(WidgetOptionsLoadEvent $event)
    {
        if (!$this->securityFacade->isGranted('oro_dataaudit_history')) {
            return;
        }
        $widgetOptions = $event->getWidgetOptions();
        if (!$this->contextChecker->isApplicableInContext($widgetOptions)) {
            return;
        }
        $fieldsLoader = $widgetOptions['fieldsLoader'];

        $auditFilters = array_map(function ($filter) {
            if (isset($filter['dateParts'], $filter['dateParts']['value'])) {
                $filter['dateParts'] = [
                    'value' => $filter['dateParts']['value'],
                ];
            }

            return $filter;
        }, $widgetOptions['metadata']['filters']);

        $event->setWidgetOptions(array_merge_recursive(
            $widgetOptions,
            [
                'auditFilters'      => $auditFilters,
                'auditFieldsLoader' => [
                    'entityChoice'      => $fieldsLoader['entityChoice'],
                    'loadingMaskParent' => $fieldsLoader['loadingMaskParent'],
                    'router'            => 'oro_api_get_audit_fields',
                    'routingParams'     => [],
                    'fieldsData'        => $this->getAuditFields(),
                    'confirmMessage'    => $fieldsLoader['confirmMessage'],
                ],
                'extensions' => [
                    'orodataaudit/js/app/components/segment-component-extension',
                ],
            ]
        ));
    }

    /**
     * @return string Json encoded
     */
    protected function getAuditFields()
    {
        $path = [
            '_controller' => 'OroDataAuditBundle:Api/Rest/Audit:getFields'
        ];
        $subRequest = $this->request->duplicate(['_format' => 'json'], null, $path);
        $response = $this->httpKernel->handle($subRequest, HttpKernelInterface::MASTER_REQUEST);

        return $response->getContent();
    }
}
