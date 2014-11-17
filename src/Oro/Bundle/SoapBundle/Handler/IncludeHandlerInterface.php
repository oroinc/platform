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
     *
     * @return bool
     */
    public function supports($object);

    /**
     * Process "include request" and modify response object
     *
     * @param object|EntityManagerAwareInterface|FormAwareInterface $object
     * @param Request                                               $request
     * @param Response                                              $response
     *
     * @return void
     */
    public function handle($object, Request $request, Response $response);
}
