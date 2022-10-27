<?php

namespace Oro\Bundle\UIBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Twig\Environment;

/**
 * BeforeViewRenderEvent event is triggered before processing entity view (by oro_view_process Twig function)
 * and allows to modify the data that is passed to the template.
 */
class BeforeViewRenderEvent extends Event
{
    /** @var Environment */
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
     * @param Environment       $twigEnvironment
     * @param array             $data
     * @param object            $entity
     */
    public function __construct(Environment $twigEnvironment, array $data, $entity)
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
     * @return Environment
     */
    public function getTwigEnvironment()
    {
        return $this->twigEnvironment;
    }
}
