<?php

namespace Oro\Bundle\DataAuditBundle\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

use Oro\Bundle\SegmentBundle\Event\WidgetOptionsLoadEvent;

class SegmentWidgetOptionsListener
{
    /** @var Request */
    protected $request;

    /** @var HttpKernelInterface */
    protected $httpKernel;

    /**
     * @param HttpKernelInterface $httpKernel
     */
    public function __construct(HttpKernelInterface $httpKernel)
    {
        $this->httpKernel = $httpKernel;
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
        $widgetOptions = $event->getWidgetOptions();
        $fieldsLoader = $widgetOptions['fieldsLoader'];

        $event->setWidgetOptions(array_merge_recursive(
            $widgetOptions,
            [
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
