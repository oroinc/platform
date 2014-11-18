<?php

namespace Oro\Bundle\SoapBundle\Handler;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\SoapBundle\Controller\Api\FormAwareInterface;
use Oro\Bundle\SoapBundle\Controller\Api\EntityManagerAwareInterface;

interface IncludeHandlerInterface
{
    /**
     * Is handler object supports "include request"
     *
     * @param object|EntityManagerAwareInterface|FormAwareInterface $object
     * @param array                                                 $context
     *
     * @return bool
     */
    public function supports($object, array $context);

    /**
     * Process "include request" and modify response object
     *
     * @param object|EntityManagerAwareInterface|FormAwareInterface $object
     * @param array                                                 $context
     * @param Request                                               $request
     * @param Response                                              $response
     *
     * @return void
     */
    public function handle($object, array $context, Request $request, Response $response);
}
