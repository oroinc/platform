<?php

namespace Oro\Bundle\SearchBundle\Controller\Api;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;
use Oro\Bundle\SearchBundle\Event\PrepareResultItemEvent;
use Oro\Bundle\SearchBundle\Query\Result;
use Symfony\Component\DependencyInjection\ContainerAware;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

class SoapController extends ContainerAware
{
    /**
     * @Soap\Method("search")
     * @Soap\Param("search", phpType = "string")
     * @Soap\Param("offset", phpType = "int")
     * @Soap\Param("max_results", phpType = "int")
     * @Soap\Result(phpType = "Oro\Bundle\SearchBundle\Query\Result")
     *
     * @AclAncestor("oro_search")
     */
    public function searchAction($search, $offset = 0, $max_results = 0)
    {
        $result = $this->container->get('oro_search.index')->simpleSearch($search, $offset, $max_results);
        $this->postProcessResult($result);

        return $this->container->get('besimple.soap.response')->setReturnValue($result);
    }

    /**
     * @Soap\Method("advancedSearch")
     * @Soap\Param("query", phpType = "string")
     * @Soap\Result(phpType = "Oro\Bundle\SearchBundle\Query\Result")
     *
     * @AclAncestor("oro_search")
     */
    public function advancedSearchAction($query)
    {
        $result = $this->container->get('oro_search.index')->advancedSearch($query);
        $this->postProcessResult($result);

        return $this->container->get('besimple.soap.response')->setReturnValue($result);
    }

    /**
     * @param Result $result
     */
    protected function postProcessResult(Result $result)
    {
        $dispatcher = $this->container->get('event_dispatcher');
        foreach ($result->getElements() as $item) {
            $dispatcher->dispatch(PrepareResultItemEvent::EVENT_NAME, new PrepareResultItemEvent($item));
        }
    }
}
