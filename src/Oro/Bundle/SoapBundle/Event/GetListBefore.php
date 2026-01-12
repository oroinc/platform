<?php

namespace Oro\Bundle\SoapBundle\Event;

use Doctrine\Common\Collections\Criteria;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched before retrieving a list of entities via the SOAP API.
 *
 * This event allows listeners to modify the query criteria before the list is fetched,
 * enabling dynamic filtering, sorting adjustments, or other pre-processing operations
 * on the entity retrieval request.
 */
class GetListBefore extends Event
{
    public const NAME = 'oro_api.request.get_list.before';

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
