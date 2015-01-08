<?php

namespace Oro\Bundle\SoapBundle\Tests\Unit\Handler;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\SoapBundle\Handler\Context;

trait ContextAwareTest
{
    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     *
     * @param object   $controller
     * @param Request  $request
     * @param Response $response
     * @param string   $action
     * @param array    $values
     *
     * @return Context
     */
    protected function createContext(
        $controller = null,
        Request $request = null,
        Response $response = null,
        $action = null,
        array $values = []
    ) {
        $controller = $controller ?: new \stdClass();
        $request    = $request ?: new Request();
        $response   = $response ?: new Response();
        $action     = $action ?: uniqid('action');

        return new Context($controller, $request, $response, $action, $values);
    }
}
