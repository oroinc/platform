<?php

namespace Oro\Bundle\SoapBundle\Event;

use Doctrine\Common\Collections\Criteria;
use Symfony\Contracts\EventDispatcher\Event;

class GetListBefore extends Event
{
    const NAME = 'oro_api.request.get_list.before';

    /**
     * @var string
     */
    protected $className;

    /**
     * @var Criteria
     */
    protected $criteria;

    /**
     * @param Criteria $criteria
     * @param string   $className
     */
    public function __construct(Criteria $criteria, $className)
    {
        $this->criteria = $criteria;
        $this->className = $className;
    }

    /**
     * @return Criteria
     */
    public function getCriteria()
    {
        return $this->criteria;
    }

    public function setCriteria(Criteria $criteria)
    {
        $this->criteria = $criteria;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }
}
