<?php

namespace Oro\Bundle\SoapBundle\Handler;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestApiReadInterface;
use Oro\Bundle\SoapBundle\Controller\Api\EntityManagerAwareInterface;

class TotalHeaderHandler implements IncludeHandlerInterface
{
    const HEADER_NAME = 'X-Total-Count';

    /**
     * {@inheritdoc}
     */
    public function supports($object)
    {
        return $object instanceof RestApiReadInterface && $object instanceof EntityManagerAwareInterface;
    }

    /**
     * {@inheritdoc}
     */
    public function handle($object, Request $request, Response $response)
    {
        /** @var RestApiReadInterface|EntityManagerAwareInterface $object */

    }
}
