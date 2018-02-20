<?php

namespace Oro\Bundle\UIBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Twig_Environment;

class BeforeViewRenderEvent extends Event
{
    /** @var \Twig_Environment */
    protected $twigEnvironment;

    /**
     * Array of data collected in entity view template
     *
     * @var array
     */
    protected $data;

    /** @var object */
    protected $entity;

    /**
     * @param \Twig_Environment $twigEnvironment
     * @param array             $data
     * @param object            $entity
     */
    public function __construct(Twig_Environment $twigEnvironment, array $data, $entity)
    {
        $this->entity          = $entity;
        $this->data            = $data;
        $this->twigEnvironment = $twigEnvironment;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return object
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @return \Twig_Environment
     */
    public function getTwigEnvironment()
    {
        return $this->twigEnvironment;
    }
}
