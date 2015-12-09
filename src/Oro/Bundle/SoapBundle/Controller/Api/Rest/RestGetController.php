<?php

namespace Oro\Bundle\SoapBundle\Controller\Api\Rest;

use Doctrine\ORM\QueryBuilder;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\SearchBundle\Event\PrepareResultItemEvent;
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

use Oro\Component\DoctrineUtils\ORM\SqlQueryBuilder;
use Oro\Bundle\SearchBundle\Query\Query as SearchQuery;
use Oro\Bundle\SearchBundle\Query\Result\Item as SearchResultItem;
use Oro\Bundle\SoapBundle\Handler\Context;
use Oro\Bundle\SoapBundle\Controller\Api\EntityManagerAwareInterface;
use Oro\Bundle\SoapBundle\Request\Parameters\Filter\ParameterFilterInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class RestGetController extends FOSRestController implements EntityManagerAwareInterface, RestApiReadInterface
{
    const ITEMS_PER_PAGE = 10;

    /**
     * {@inheritdoc}
     */
    public function handleGetListRequest($page = 1, $limit = self::ITEMS_PER_PAGE, $filters = [], $joins = [])
    {
        $manager    = $this->getManager();
        $qb         = $manager->getListQueryBuilder($limit, $page, $filters, null, $joins);
        $totalCount = null;

        if (null !== $qb) {
            if ($manager->isSerializerConfigured()) {
                $result = $manager->serialize($qb);
            } elseif ($qb instanceof QueryBuilder) {
                $result = $this->getPreparedItems($qb->getQuery()->getResult());
            } elseif ($qb instanceof SqlQueryBuilder) {
                $result = $this->getPreparedItems($qb->getQuery()->getResult());
            } elseif ($qb instanceof SearchQuery) {
                $searchResult = $this->container->get('oro_search.index')->query($qb);

                $dispatcher = $this->get('event_dispatcher');
                foreach ($searchResult->getElements() as $item) {
                    $dispatcher->dispatch(PrepareResultItemEvent::EVENT_NAME, new PrepareResultItemEvent($item));
                }

                $result       = $this->getPreparedItems($searchResult->toArray());
                $totalCount   = function () use ($searchResult) {
                    return $searchResult->getRecordsCount();
                };
            } else {
                throw new \RuntimeException(
                    sprintf(
                        'Unsupported query type: %s.',
                        is_object($qb) ? get_class($qb) : gettype($qb)
                    )
                );
            }
        } else {
            $result = [];
        }

        $responseContext = ['result' => $result, 'query' => $qb];
        if (null !== $totalCount) {
            $responseContext['totalCount'] = $totalCount;
        }

        return $this->buildResponse($result, self::ACTION_LIST, $responseContext);
    }

    /**
     * {@inheritdoc}
     */
    public function handleGetRequest($id)
    {
        $manager = $this->getManager();

        if ($manager->isSerializerConfigured()) {
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
        $result = [];
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
        $result = [];
        if ($entity) {
            if (is_array($entity)) {
                foreach ($entity as $field => $value) {
                    $this->transformEntityField($field, $value);
                    $result[$field] = $value;
                }
            } elseif ($entity instanceof SearchResultItem) {
                return [
                    'id'     => $entity->getRecordId(),
                    'entity' => $entity->getEntityName(),
                    'title'  => $entity->getRecordTitle()
                ];
            } else {
                /** @var UnitOfWork $uow */
                $uow = $this->getDoctrine()->getManager()->getUnitOfWork();
                foreach ($uow->getOriginalEntityData($entity) as $field => $value) {
                    if ($resultFields && !in_array($field, $resultFields)) {
                        continue;
                    }

                    $accessors = ['get' . ucfirst($field), 'is' . ucfirst($field), 'has' . ucfirst($field)];
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
        }

        return $result;
    }

    /**
     * @param array $parameters  The allowed query parameters
     * @param array $normalisers The normalizers of the filter values.
     *                           [filterName => normalizer, ...]
     *                           Each normalizer can be:
     *                             * instance of ParameterFilterInterface
     *                             * [closure => \Closure(...), ...]
     * @param array $fieldMap    The map between filters and entity fields
     *                           [filterName => fieldName or alias.fieldName, ...]
     *                           For example: 2 filters by relation field - user_id and user_name.
     *                           Both should be applied to 'user' relation.
     *                           ['user_id' => 'user', 'user_name' => 'user']
     *
     * @return Criteria
     */
    protected function getFilterCriteria($parameters, $normalisers = [], $fieldMap = [])
    {
        return $this->buildFilterCriteria(
            $this->filterQueryParameters($parameters),
            $normalisers,
            $fieldMap
        );
    }

    /**
     * Builds the Criteria object based on the given filters
     *
     * @param array $filters     The filter criteria.
     *                           [filterName => [operator, value], ...]
     * @param array $normalisers The normalizers of the filter values.
     *                           [filterName => normalizer, ...]
     *                           Each normalizer can be:
     *                             * instance of ParameterFilterInterface
     *                             * [closure => \Closure(...), ...]
     * @param array $fieldMap    The map between filters and entity fields
     *                           [filterName => fieldName or alias.fieldName, ...]
     *                           For example: 2 filters by relation field - user_id and user_name.
     *                           Both should be applied to 'user' relation.
     *                           ['user_id' => 'user', 'user_name' => 'user']
     *
     * @return Criteria
     */
    protected function buildFilterCriteria($filters, $normalisers = [], $fieldMap = [])
    {
        $criteria = Criteria::create();

        foreach ($filters as $filterName => $data) {
            list ($operator, $value) = $data;

            $normaliser = isset($normalisers[$filterName]) ? $normalisers[$filterName] : false;
            if ($normaliser) {
                switch (true) {
                    case $normaliser instanceof ParameterFilterInterface:
                        $value = $normaliser->filter($value, $operator);
                        break;
                    case is_array($normaliser) && isset($normaliser['closure']) && is_callable($normaliser['closure']):
                        $value = call_user_func($normaliser['closure'], $value, $operator);
                        break;
                }
            }

            $fieldName = isset($fieldMap[$filterName]) ? $fieldMap[$filterName] : $filterName;
            $this->addCriteria($criteria, $fieldName, $operator, $value);
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
            '#(?P<name>[\w\d_-]+)(?P<operator>(<|>|%3C|%3E)?=|<>|%3C%3E|(<|>|%3C|%3E))(?P<value>[^&]+)#',
            $this->getRequest()->getQueryString(),
            $matches,
            PREG_SET_ORDER
        )) {
            throw new \Exception('No parameters found in query string');
        }

        $filteredParameters = [];
        foreach ($matches as $match) {
            $name = $match['name'];
            if (false === in_array($name, $supportedParameters, true)) {
                continue;
            }

            $filteredParameters[$name] = [
                rawurldecode($match['operator']),
                rawurldecode($match['value'])
            ];
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

    /**
     * @return Response
     */
    protected function buildNotFoundResponse()
    {
        return $this->buildResponse('', self::ACTION_READ, ['result' => null], Codes::HTTP_NOT_FOUND);
    }
}
