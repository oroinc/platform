<?php

namespace Oro\Bundle\SoapBundle\Controller\Api\Rest;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use Doctrine\ORM\Query;
use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\Proxy\Proxy;
use Doctrine\Common\Collections\Criteria;

use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\View\View;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;

use Oro\Bundle\SoapBundle\Handler\Context;
use Oro\Bundle\SoapBundle\Controller\Api\EntityManagerAwareInterface;
use Oro\Bundle\SoapBundle\Request\Parameters\Filter\ParameterFilterInterface;
use Oro\Bundle\SoapBundle\Entity\Manager\EntitySerializerManagerInterface;

abstract class RestGetController extends FOSRestController implements EntityManagerAwareInterface, RestApiReadInterface
{
    const ITEMS_PER_PAGE = 10;

    /**
     * {@inheritdoc}
     */
    public function handleGetListRequest($page = 1, $limit = self::ITEMS_PER_PAGE, $filters = [], $joins = [])
    {
        $manager = $this->getManager();
        $qb      = $manager->getListQueryBuilder($limit, $page, $filters, null, $joins);

        if ($manager instanceof EntitySerializerManagerInterface) {
            $result = $manager->serialize($qb);
        } else {
            $result = $this->getPreparedItems($qb->getQuery()->getResult());
        }

        return $this->buildResponse($result, self::ACTION_LIST, ['result' => $result, 'query' => $qb]);
    }

    /**
     * GET single item
     *
     * @param  mixed $id
     *
     * @return Response
     */
    public function handleGetRequest($id)
    {
        $manager = $this->getManager();

        if ($manager instanceof EntitySerializerManagerInterface) {
            $result = $manager->serializeOne($id);
        } else {
            $result = $manager->find($id);
            if ($result) {
                $result = $this->getPreparedItem($result);
            }
        }

        return $this->buildResponse(
            $result ?: '',
            self::ACTION_READ,
            ['result' => $result],
            $result ? Codes::HTTP_OK : Codes::HTTP_NOT_FOUND
        );
    }

    /**
     * Returns resource's metadata that might be useful for some another API requests.
     * For example it might be: structure of resource object, special identifier of resource for another API method etc.
     *
     * @ApiDoc(
     *      description="Retrieve service metadata for resource",
     *      resource=false
     * )
     */
    public function optionsAction()
    {
        $metadata = $this->get('oro_soap.provider.metadata')->getMetadataFor($this);

        return $this->handleView(
            $this->view($metadata, Codes::HTTP_OK)
        );
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
     * @param array $resultFields If not empty, result item will contain only given fields.
     *
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
     *                                  or [filterName => ParameterFilterInterface]
     * @param array $filterMap          assoc array with map of filter query params to path that for doctrine criteria
     *                                  For example: 2 filters by relation field - user_id and username.
     *                                  Both should be applied to criteria as 'user' relation.
     *                                  ['user_id' => 'user', 'user_name' => 'user']
     *
     * @return array
     * @throws \Exception
     */
    protected function getFilterCriteria($supportedApiParams, $filterParameters = [], $filterMap = [])
    {
        return $this->buildFilterCriteria(
            $this->filterQueryParameters($supportedApiParams),
            $filterParameters,
            $filterMap
        );
    }

    /**
     * @param array $filters           valid parameters that can be passed
     * @param array $filterParameters  assoc array with filter params, like closure
     *                                 [filterName => [closure => \Closure(...), ...]]
     *                                 or [filterName => ParameterFilterInterface]
     * @param array $filterMap         assoc array with map of filter query params to path that for doctrine criteria
     *                                 For example: 2 filters by relation field - user_id and username.
     *                                 Both should be applied to criteria as 'user' relation.
     *                                 ['user_id' => 'user', 'user_name' => 'user']
     *
     * @return array
     * @throws \Exception
     */
    protected function buildFilterCriteria($filters, $filterParameters = [], $filterMap = [])
    {
        $criteria = Criteria::create();

        foreach ($filters as $filterName => $filterData) {
            list ($operator, $value) = $filterData;

            $filter = isset($filterParameters[$filterName]) ? $filterParameters[$filterName] : false;
            if ($filter) {
                switch (true) {
                    case $filter instanceof ParameterFilterInterface:
                        $value = $filter->filter($value, $operator);
                        break;
                    case is_array($filter) && isset($filter['closure']) && is_callable($filter['closure']):
                        $value = call_user_func($filter['closure'], $value, $operator);
                        break;
                }
            }

            $filterName = isset($filterMap[$filterName]) ? $filterMap[$filterName] : $filterName;
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
                if (is_array($value)) {
                    $expr = $exprBuilder->notIn($paramName, $value);
                } else {
                    $expr = $exprBuilder->neq($paramName, $value);
                }
                break;
            case '=':
            default:
                if (is_array($value)) {
                    $expr = $exprBuilder->in($paramName, $value);
                } else {
                    $expr = $exprBuilder->eq($paramName, $value);
                }
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
            $value = (string)$value;
        } elseif ($value instanceof \DateTime) {
            $value = $value->format('c');
        }
    }

    /**
     * @param mixed|View $data
     * @param string     $action
     * @param array      $contextValues
     * @param int        $status Used only if data was given in raw format
     *
     * @return Response
     */
    protected function buildResponse($data, $action, $contextValues = [], $status = Codes::HTTP_OK)
    {
        if ($data instanceof View) {
            $response = parent::handleView($data);
        } else {
            $headers = isset($contextValues['headers']) ? $contextValues['headers'] : [];
            unset($contextValues['headers']);

            $response = new JsonResponse($data, $status, $headers);
        }

        $includeHandler = $this->get('oro_soap.handler.include');
        $includeHandler->handle(new Context($this, $this->get('request'), $response, $action, $contextValues));

        return $response;
    }
}
