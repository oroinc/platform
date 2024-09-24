<?php

namespace Oro\Bundle\DataAuditBundle\Controller\Api\Rest;

use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Oro\Bundle\EntityBundle\Exception\InvalidEntityException;
use Oro\Bundle\EntityBundle\Provider\EntityWithFieldsProvider;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestGetController;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\SoapBundle\Request\Parameters\Filter\EntityClassParameterFilter;
use Oro\Bundle\SoapBundle\Request\Parameters\Filter\HttpDateTimeParameterFilter;
use Oro\Bundle\SoapBundle\Request\Parameters\Filter\IdentifierToReferenceFilter;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API controller for data audit.
 */
class AuditController extends RestGetController
{
    /**
     * Get list of audit logs
     *
     *
     * @ApiDoc(
     *  description="Get list of all logged entities",
     *  resource=true
     * )
     *
     * @param Request $request
     * @return Response
     */
    #[QueryParam(
        name: 'page',
        requirements: '\d+',
        description: 'Page number, starting from 1. Defaults to 1.',
        nullable: true
    )]
    #[QueryParam(
        name: 'limit',
        requirements: '\d+',
        description: 'Number of items per page. defaults to 10.',
        nullable: true
    )]
    #[QueryParam(
        name: 'loggedAt',
        requirements: '\d{4}(-\d{2}(-\d{2}([T ]\d{2}:\d{2}(:\d{2}(\.\d+)?)?(Z|([-+]\d{2}(:?\d{2})?))?)?)?)?',
        description: 'Date in RFC 3339 format. For example: 2009-11-05T13:15:30Z, 2008-07-01T22:35:17+08:00',
        nullable: true
    )]
    #[QueryParam(
        name: 'action',
        requirements: 'create|update|remove',
        description: 'Logged action name',
        nullable: true
    )]
    #[QueryParam(
        name: 'user',
        requirements: '\d+',
        description: 'ID of User who has performed action',
        nullable: true
    )]
    #[QueryParam(
        name: 'objectClass',
        requirements: '\w+',
        description: 'Entity full class name; backslashes (\) should be replaced with underscore (_).',
        nullable: true
    )]
    #[AclAncestor('oro_dataaudit_view')]
    public function cgetAction(Request $request)
    {
        $page = (int)$request->get('page', 1);
        $limit = (int)$request->get('limit', self::ITEMS_PER_PAGE);
        $filterParameters = [
            'loggedAt'    => new HttpDateTimeParameterFilter(),
            'user' => new IdentifierToReferenceFilter($this->container->get('doctrine'), User::class),
            'objectClass' => new EntityClassParameterFilter(
                $this->container->get('oro_entity.entity_class_name_helper')
            )
        ];

        $criteria = $this->getFilterCriteria($this->getSupportedQueryParameters('cgetAction'), $filterParameters);

        return $this->handleGetListRequest($page, $limit, $criteria);
    }

    /**
     * Get page state
     *
     * @param int $id Page state id
     *
     * @return Response
     * @ApiDoc(
     *  description="Get audit entity",
     *  resource=true,
     *  requirements={
     *      {"name"="id", "dataType"="integer"},
     *  }
     * )
     */
    #[AclAncestor('oro_dataaudit_view')]
    public function getAction($id)
    {
        return $this->handleGetRequest($id);
    }

    /**
     * Get auditable entities with auditable fields
     *
     * @ApiDoc(
     *      description="Get auditable entities with auditable fields",
     *      resource=true
     * )
     *
     * @param Request $request
     * @return Response
     */
    #[QueryParam(
        name: 'with-relations',
        requirements: 'true|false',
        default: true,
        description: 'Indicates whether association fields should be returned as well.',
        strict: true,
        nullable: true
    )]
    #[AclAncestor('oro_dataaudit_view')]
    public function getFieldsAction(Request $request)
    {
        /* @var $provider EntityWithFieldsProvider */
        $provider = $this->container->get('oro_query_designer.entity_field_list_provider');
        $withRelations = filter_var($request->get('with-relations', true), FILTER_VALIDATE_BOOLEAN);
        $statusCode = Response::HTTP_OK;

        try {
            $entities = $provider->getFields(
                true,
                true,
                $withRelations,
                false
            );
            $result = $this->filterAuditableEntities($entities);
        } catch (InvalidEntityException $ex) {
            $statusCode = Response::HTTP_NOT_FOUND;
            $result = ['message' => $ex->getMessage()];
        }

        return $this->handleView($this->view($result, $statusCode));
    }

    /**
     * Get entity Manager
     *
     * @return ApiEntityManager
     */
    #[\Override]
    public function getManager()
    {
        return $this->container->get('oro_dataaudit.audit.manager.api');
    }

    #[\Override]
    protected function getPreparedItem($entity, $resultFields = [])
    {
        /** @var Audit $entity */
        $result = parent::getPreparedItem($entity, $resultFields);

        // process relations
        $result['user'] = $entity->getUser() ? $entity->getUser()->getId() : null;

        unset($result['fields']);
        $result['data'] = $this->container->get('oro_dataaudit.model.fields_transformer')
            ->getCollectionData($entity->getFields());

        return $result;
    }

    /**
     * @param array $entities
     *
     * @return array
     */
    private function filterAuditableEntities(array $entities = [])
    {
        $auditConfigProvider = $this->container->get('oro_entity_config.provider.dataaudit');

        $auditableEntities = [];
        foreach ($entities as $entityClass => $entityData) {
            if (!$auditConfigProvider->getConfig($entityClass)->is('auditable')) {
                continue;
            }

            $auditableEntities[$entityClass] = $entityData;
            $auditableEntities[$entityClass]['fields'] = [];

            foreach ($entityData['fields'] as $fieldData) {
                $class = $entityClass;
                $field = $fieldData['name'];

                $fieldChunks = explode('::', $fieldData['name']);
                if (count($fieldChunks) === 2) {
                    [$class, $field] = $fieldChunks;
                    if (!$auditConfigProvider->getConfig($class)->is('auditable')) {
                        continue;
                    }
                }

                if (!$auditConfigProvider->hasConfig($class, $field) ||
                    !$auditConfigProvider->getConfig($class, $field)->is('auditable')
                ) {
                    continue;
                }

                $auditableEntities[$entityClass]['fields'][] = $fieldData;
            }
        }

        return $auditableEntities;
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            ['doctrine' => ManagerRegistry::class]
        );
    }
}
