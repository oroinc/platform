<?php

namespace Oro\Bundle\DataAuditBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Util\Codes;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Oro\Bundle\EntityBundle\Exception\InvalidEntityException;
use Oro\Bundle\EntityBundle\Provider\EntityWithFieldsProvider;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestGetController;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\SoapBundle\Request\Parameters\Filter\EntityClassParameterFilter;
use Oro\Bundle\SoapBundle\Request\Parameters\Filter\HttpDateTimeParameterFilter;
use Oro\Bundle\SoapBundle\Request\Parameters\Filter\IdentifierToReferenceFilter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serve data audit api calls
 *
 * @NamePrefix("oro_api_")
 */
class AuditController extends RestGetController implements ClassResourceInterface
{
    /**
     * Get list of audit logs
     *
     * @QueryParam(
     *      name="page",
     *      requirements="\d+",
     *      nullable=true,
     *      description="Page number, starting from 1. Defaults to 1."
     * )
     * @QueryParam(
     *      name="limit",
     *      requirements="\d+",
     *      nullable=true,
     *      description="Number of items per page. defaults to 10."
     * )
     * @QueryParam(
     *     name="loggedAt",
     *     requirements="\d{4}(-\d{2}(-\d{2}([T ]\d{2}:\d{2}(:\d{2}(\.\d+)?)?(Z|([-+]\d{2}(:?\d{2})?))?)?)?)?",
     *     nullable=true,
     *     description="Date in RFC 3339 format. For example: 2009-11-05T13:15:30Z, 2008-07-01T22:35:17+08:00"
     * )
     * @QueryParam(
     *     name="action",
     *     requirements="create|update|remove",
     *     nullable=true,
     *     description="Logged action name"
     * )
     * @QueryParam(
     *     name="user",
     *     requirements="\d+",
     *     nullable=true,
     *     description="ID of User who has performed action"
     * )
     * @QueryParam(
     *     name="objectClass",
     *     requirements="\w+",
     *     nullable=true,
     *     description="Entity full class name; backslashes (\) should be replaced with underscore (_)."
     * )
     *
     * @ApiDoc(
     *  description="Get list of all logged entities",
     *  resource=true
     * )
     *
     * @AclAncestor("oro_dataaudit_view")
     * @param Request $request
     * @return Response
     */
    public function cgetAction(Request $request)
    {
        $page = (int)$request->get('page', 1);
        $limit = (int)$request->get('limit', self::ITEMS_PER_PAGE);
        $filterParameters = [
            'loggedAt'    => new HttpDateTimeParameterFilter(),
            'user'        => new IdentifierToReferenceFilter($this->getDoctrine(), 'OroUserBundle:User'),
            'objectClass' => new EntityClassParameterFilter($this->get('oro_entity.entity_class_name_helper'))
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
     *
     * @AclAncestor("oro_dataaudit_view")
     */
    public function getAction($id)
    {
        return $this->handleGetRequest($id);
    }

    /**
     * Get auditable entities with auditable fields
     *
     * @QueryParam(
     *      name="with-relations",
     *      nullable=true,
     *      requirements="true|false",
     *      default="true",
     *      strict=true,
     *      description="Indicates whether association fields should be returned as well."
     * )
     *
     * @ApiDoc(
     *      description="Get auditable entities with auditable fields",
     *      resource=true
     * )
     *
     * @AclAncestor("oro_dataaudit_view")
     * @param Request $request
     * @return Response
     */
    public function getFieldsAction(Request $request)
    {
        /* @var $provider EntityWithFieldsProvider */
        $provider = $this->get('oro_query_designer.entity_field_list_provider');
        $withRelations = filter_var($request->get('with-relations', true), FILTER_VALIDATE_BOOLEAN);
        $statusCode = Codes::HTTP_OK;

        try {
            $entities = $provider->getFields(true, true, $withRelations, false);
            $result = $this->filterAuditableEntities($entities);
        } catch (InvalidEntityException $ex) {
            $statusCode = Codes::HTTP_NOT_FOUND;
            $result = ['message' => $ex->getMessage()];
        }

        return $this->handleView($this->view($result, $statusCode));
    }

    /**
     * Get entity Manager
     *
     * @return ApiEntityManager
     */
    public function getManager()
    {
        return $this->get('oro_dataaudit.audit.manager.api');
    }

    /**
     * {@inheritdoc}
     */
    protected function getPreparedItem($entity, $resultFields = [])
    {
        /** @var Audit $entity */
        $result = parent::getPreparedItem($entity, $resultFields);

        // process relations
        $result['user'] = $entity->getUser() ? $entity->getUser()->getId() : null;

        // prevent BC breaks
        // @deprecated since 1.4.1
        $result['object_class'] = $result['objectClass'];
        $result['object_name']  = $result['objectName'];
        $result['username']     = $entity->getUser() ? $entity->getUser()->getUsername() : null;

        unset($result['fields']);
        $result['data'] = $this->get('oro_dataaudit.model.fields_transformer')->getCollectionData($entity->getFields());

        return $result;
    }

    /**
     * @param array $entities
     *
     * @return array
     */
    private function filterAuditableEntities(array $entities = [])
    {
        $auditConfigProvider = $this->get('oro_entity_config.provider.dataaudit');

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
                    list($class, $field) = $fieldChunks;
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
}
