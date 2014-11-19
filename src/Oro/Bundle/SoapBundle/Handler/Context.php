<?php

namespace Oro\Bundle\SoapBundle\Handler;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\SoapBundle\Controller\Api\FormAwareInterface;
use Oro\Bundle\SoapBundle\Controller\Api\EntityManagerAwareInterface;

class Context
{
    /** @var object|FormAwareInterface|EntityManagerAwareInterface */
    protected $controller;

    /** @var Request */
    protected $request;

    /** @var Response */
    protected $response;

    /** @var string */
    protected $action;

    /** @var array */
    protected $values;

    /**
     * @param object|FormAwareInterface|EntityManagerAwareInterface $controller
     * @param Request                                               $request
     * @param Response                                              $response
     * @param string                                                $action
     * @param array                                                 $values
     */
    public function __construct($controller, Request $request, Response $response, $action = null, array $values = [])
    {
        $this->controller = $controller;
        $this->request    = $request;
        $this->response   = $response;
        $this->action     = $action;
        $this->values     = $values;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param string $actionName
     *
     * @return bool
     */
    public function isAction($actionName)
    {
        return $actionName === $this->action;
    }

    /**
     * @return object|FormAwareInterface|EntityManagerAwareInterface
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param string $key
     * @param null   $default
     *
     * @return null
     */
    public function get($key, $default = null)
    {
        if ($this->has($key)) {
            return $this->values[$key];
        }

        return $default;
    }

    /**
     * @param string $key
     * @param mixed  $value
     */
    public function set($key, $value)
    {
        $this->values[$key] = $value;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function has($key)
    {
        return isset($this->values[$key]);
    }
}
