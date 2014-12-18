<?php

namespace Oro\Bundle\SegmentBundle\Controller\Api\Rest;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class DeprecatedSegmentController extends Controller
{
    /**
     * Deprecated action!!! Get entity segments forward.
     *
     * @param $entityName
     * @return Response
     */
    public function getItemsAction($entityName)
    {
        return $this->forward('OroSegmentBundle:Api/Rest/Segment:getItems', [], ['entityName' => $entityName]);
    }
}
