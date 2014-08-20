<?php

namespace Oro\Bundle\SoapBundle\Controller\Api\Rest;

use Symfony\Component\HttpFoundation\Response;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Proxy\Proxy;
use Doctrine\ORM\UnitOfWork;

use FOS\Rest\Util\Codes;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;

use Oro\Bundle\SoapBundle\Controller\Api\EntityManagerAwareInterface;

abstract class RestGetController extends FOSRestController implements EntityManagerAwareInterface, RestApiReadInterface
{
    const ITEMS_PER_PAGE = 10;

    /**
     * {@inheritdoc}
     */
    public function handleGetListRequest($page = 1, $limit = self::ITEMS_PER_PAGE, $filters = [])
    {
        $manager = $this->getManager();
        $items = $manager->getList($limit, $page, $filters);

        $result = array();
        foreach ($items as $item) {
            $result[] = $this->getPreparedItem($item);
        }
        unset($items);

        return new Response(json_encode($result), Codes::HTTP_OK);
    }

    /**
     * GET single item
     *
     * @param  mixed    $id
     * @return Response
     */
    public function handleGetRequest($id)
    {
        $item = $this->getManager()->find($id);

        if ($item) {
            $item = $this->getPreparedItem($item);
        }
        $responseData = $item ? json_encode($item) : '';

        return new Response($responseData, $item ? Codes::HTTP_OK : Codes::HTTP_NOT_FOUND);
    }

    /**
     * Return query parameter names defined in annotation for specified method
     *
     * @param string $methodName
     *
     * @return array
     */
    protected function getSupportedQueryParameters($methodName)
    {
        /** @var ParamFetcherInterface $paramFetcher */
        $paramFetcher = $this->container->get('fos_rest.request.param_fetcher');
        $paramFetcher->setController([$this, $methodName]);

        $skipParameters = ['limit', 'page'];

        return array_diff(array_keys($paramFetcher->all()), $skipParameters);
    }

    /**
     * Prepare list of entities for serialization
     *
     * @param array $entities
     * @param  array $resultFields If not empty, result item will contain only given fields.
     * @return array
     */
    protected function getPreparedItems($entities, $resultFields = [])
    {
        $result = array();
        foreach ($entities as $entity) {
            $result[] = $this->getPreparedItem($entity, $resultFields);
        }
        return $result;
    }

    /**
     * Prepare entity for serialization
     *
     * @param  mixed $entity
     * @param  array $resultFields If not empty, result item will contain only given fields.
     *
     * @return array
     */
    protected function getPreparedItem($entity, $resultFields = [])
    {
        if ($entity instanceof Proxy && !$entity->__isInitialized()) {
            $entity->__load();
        }
        $result = array();
        if ($entity) {
            /** @var UnitOfWork $uow */
            $uow = $this->getDoctrine()->getManager()->getUnitOfWork();
            foreach ($uow->getOriginalEntityData($entity) as $field => $value) {
                if ($resultFields && !in_array($field, $resultFields)) {
                    continue;
                }

                $accessors = array('get' . ucfirst($field), 'is' . ucfirst($field), 'has' . ucfirst($field));
                foreach ($accessors as $accessor) {
                    if (method_exists($entity, $accessor)) {
                        $value = $entity->$accessor();

                        $this->transformEntityField($field, $value);
                        $result[$field] = $value;
                        break;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param array $supportedApiParams valid parameters that can be passed
     * @param array $filterParameters   assoc array with filter params, like closure
     *                                  [filterName => [closure => \Closure(...), ...]]
     *
     * @return array
     * @throws \Exception
     */
    protected function getFilterCriteria($supportedApiParams, $filterParameters = [])
    {
        $allowedFilters = $this->filterQueryParameters($supportedApiParams);
        $criteria       = Criteria::create();

        foreach ($allowedFilters as $filterName => $filterData) {
            list ($operator, $value) = $filterData;

            $closure = empty($filterParameters[$filterName]['closure']) ?
                false :
                $filterParameters[$filterName]['closure'];

            $value = is_callable($closure) ? $closure($value, $operator) : $value;

            $this->addCriteria($criteria, $filterName, $operator, $value);
        }

        return $criteria;
    }

    /**
     * @param array $supportedParameters
     *
     * @return array
     * @throws \Exception
     */
    protected function filterQueryParameters(array $supportedParameters)
    {
        if (false === preg_match_all(
            '#([\w\d_-]+)([<>=]{1,2})([^&]+)#',
            rawurldecode($this->getRequest()->getQueryString()),
            $matches,
            PREG_SET_ORDER
        )) {
            throw new \Exception('No parameters found in query string');
        }

        $filteredParameters = [];
        foreach ($matches as $paramData) {
            list (, $paramName, $operator, $value) = $paramData;
            $paramName = urldecode($paramName);

            if (false === in_array($paramName, $supportedParameters)) {
                continue;
            }

            $filteredParameters[$paramName] = [$operator, urldecode($value)];
        }

        return $filteredParameters;
    }

    /**
     * @param Criteria $criteria
     * @param string   $paramName
     * @param string   $operator
     * @param string   $value
     */
    protected function addCriteria(Criteria $criteria, $paramName, $operator, $value)
    {
        $exprBuilder = Criteria::expr();
        switch ($operator) {
            case '>':
                $expr = $exprBuilder->gt($paramName, $value);
                break;
            case '<':
                $expr = $exprBuilder->lt($paramName, $value);
                break;
            case '>=':
                $expr = $exprBuilder->gte($paramName, $value);
                break;
            case '<=':
                $expr = $exprBuilder->lte($paramName, $value);
                break;
            case '<>':
                $expr = $exprBuilder->neq($paramName, $value);
                break;
            case '=':
            default:
                $expr = $exprBuilder->eq($paramName, $value);
                break;
        }

        $criteria->andWhere($expr);
    }

    /**
     * Prepare entity field for serialization
     *
     * @param string $field
     * @param mixed  $value
     */
    protected function transformEntityField($field, &$value)
    {
        if ($value instanceof Proxy && method_exists($value, '__toString')) {
            $value = (string) $value;
        } elseif ($value instanceof \DateTime) {
            $value = $value->format('c');
        }
    }
}
