<?php

namespace Oro\Bundle\UserBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\QueryParam;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\SoapBundle\Form\Handler\ApiFormHandler;
use Oro\Bundle\UserBundle\Entity\Email;
use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API CRUD controller for User entity.
 */
class UserController extends RestController
{
    /**
     * Get the list of users
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
     *     name="phone",
     *     requirements=".+",
     *     nullable=true,
     *     description="Phone number."
     * )
     *
     * @param Request $request
     * @return Response
     * @ApiDoc(
     *      description="Get the list of users",
     *      resource=true
     * )
     * @AclAncestor("oro_user_user_view")
     */
    public function cgetAction(Request $request)
    {
        $page  = (int) $request->get('page', 1);
        $limit = (int) $request->get('limit', self::ITEMS_PER_PAGE);

        $criteria = $this->getFilterCriteria($this->getSupportedQueryParameters(__FUNCTION__));

        return $this->handleGetListRequest($page, $limit, $criteria);
    }

    /**
     * Get user data
     *
     * @param int $id User id
     *
     * @ApiDoc(
     *      description="Get user data",
     *      resource=true,
     *      requirements={
     *          {"name"="id", "dataType"="integer"},
     *      }
     * )
     * @AclAncestor("oro_user_user_view")
     *
     * @return Response
     */
    public function getAction(int $id)
    {
        return $this->handleGetRequest($id);
    }

    /**
     * Create new user
     *
     * @return Response
     * @ApiDoc(
     *      description="Create new user",
     *      resource=true
     * )
     * @AclAncestor("oro_user_user_create")
     */
    public function postAction()
    {
        return $this->handleCreateRequest();
    }

    /**
     * Update existing user
     *
     * @param int $id User id
     *
     * @ApiDoc(
     *      description="Update existing user",
     *      resource=true,
     *      requirements={
     *          {"name"="id", "dataType"="integer"},
     *      }
     * )
     * @AclAncestor("oro_user_user_update")
     *
     * @return Response
     */
    public function putAction(int $id)
    {
        return $this->handleUpdateRequest($id);
    }

    /**
     * Delete user
     *
     * @param int $id User id
     *
     * @ApiDoc(
     *      description="Delete user",
     *      resource=true,
     *      requirements={
     *          {"name"="id", "dataType"="integer"},
     *      }
     * )
     * @Acl(
     *      id="oro_user_user_delete",
     *      type="entity",
     *      class="OroUserBundle:User",
     *      permission="DELETE"
     * )
     *
     * @return Response
     */
    public function deleteAction(int $id)
    {
        return $this->handleDeleteRequest($id);
    }

    /**
     * Get user roles
     *
     * @param int $id User id
     *
     * @ApiDoc(
     *      description="Get user roles",
     *      resource=true,
     *      requirements={
     *          {"name"="id", "dataType"="integer"},
     *      }
     * )
     * @AclAncestor("oro_user_role_view")
     *
     * @return Response
     */
    public function getRolesAction(int $id)
    {
        /** @var User|null $entity */
        $entity = $this->getManager()->find($id);

        if (!$entity) {
            return $this->handleView($this->view('', Response::HTTP_NOT_FOUND));
        }

        $serializedRoles = [];
        foreach ($entity->getUserRoles() as $role) {
            $serializedRoles[] = $this->serializeRole($role);
        }

        return $this->handleView($this->view($serializedRoles, Response::HTTP_OK));
    }

    /**
     * Get user groups
     *
     * @param int $id User id
     *
     * @ApiDoc(
     *      description="Get user groups",
     *      resource=true,
     *      requirements={
     *          {"name"="id", "dataType"="integer"},
     *      }
     * )
     * @AclAncestor("oro_user_group_view")
     *
     * @return Response
     */
    public function getGroupsAction(int $id)
    {
        /** @var User|null $entity */
        $entity = $this->getManager()->find($id);

        if (!$entity) {
            return $this->handleView($this->view('', Response::HTTP_NOT_FOUND));
        }

        $serializedGroups = [];
        foreach ($entity->getGroups() as $group) {
            $serializedGroups[] = $this->serializeGroup($group);
        }

        return $this->handleView($this->view($serializedGroups, Response::HTTP_OK));
    }

    /**
     * Filter user by username or email
     *
     * @QueryParam(
     *      name="email",
     *      requirements="[a-zA-Z0-9\-_\.@]+",
     *      nullable=true,
     *      description="Email to filter"
     * )
     * @QueryParam(
     *      name="username",
     *      requirements="[a-zA-Z0-9\-_\.]+",
     *      nullable=true,
     *      description="Username to filter"
     * )
     *
     * @param Request $request
     * @return Response
     *
     * @ApiDoc(
     *      description="Get user by username or email",
     *      resource=true
     * )
     * @AclAncestor("oro_user_user_view")
     */
    public function getFilterAction(Request $request)
    {
        $params = array_intersect_key(
            $request->query->all(),
            array_flip($this->getSupportedQueryParameters(__FUNCTION__))
        );

        if (empty($params)) {
            return $this->handleView($this->view('', Response::HTTP_NOT_FOUND));
        }

        /** @var User $entity */
        $entity = $this->getManager()->getRepository()->findOneBy($params);
        if (!$entity) {
            return $this->handleView($this->view('', Response::HTTP_NOT_FOUND));
        }

        $result = $this->getPreparedItem($entity);

        return $this->buildResponse($result, self::ACTION_READ, ['result' => $result], Response::HTTP_OK);
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function transformEntityField($field, &$value)
    {
        switch ($field) {
            case 'userRoles':
                $result = [];
                /** @var Role $role */
                foreach ($value as $index => $role) {
                    $result[$index] = $this->serializeRole($role);
                }
                $value = $result;
                break;
            case 'groups':
                $result = [];
                /** @var Group $group */
                foreach ($value as $index => $group) {
                    $result[$index] = $this->serializeGroup($group);
                }
                $value = $result;
                break;
            case 'emails':
                $result = [];
                /** @var Email $email */
                foreach ($value as $email) {
                    $result[] = $email->getEmail();
                }
                $value = $result;
                break;
            case 'businessUnits':
                $result = [];
                /** @var BusinessUnit $businessUnit */
                foreach ($value as $index => $businessUnit) {
                    $result[$index] = [
                        'id'   => $businessUnit->getId(),
                        'name' => $businessUnit->getName()
                    ];
                }
                $value = $result;
                break;
            case 'organization':
                if ($value) {
                    $value = $value->getName();
                }
                break;
            case 'owner':
                if ($value) {
                    $value = [
                        'id'   => $value->getId(),
                        'name' => $value->getName()
                    ];
                }
                break;
            default:
                parent::transformEntityField($field, $value);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getPreparedItem($entity, $resultFields = [])
    {
        $result = parent::getPreparedItem($entity);

        unset($result['salt']);
        unset($result['password']);
        unset($result['confirmationToken']);
        unset($result['passwordRequestedAt']);
        unset($result['imapConfiguration']);
        unset($result['apiKeys']);
        unset($result['organizations']);
        unset($result['emailOrigins']);

        return $result;
    }

    /**
     * @return ApiEntityManager
     */
    public function getManager()
    {
        return $this->get('oro_user.manager.api');
    }

    /**
     * @return FormInterface
     */
    public function getForm()
    {
        return $this->get('oro_user.form.user.api');
    }

    /**
     * @return ApiFormHandler
     */
    public function getFormHandler()
    {
        return $this->get('oro_user.form.handler.user.api');
    }

    private function serializeRole(Role $role): array
    {
        return [
            'id'    => $role->getId(),
            'role'  => $role->getRole(),
            'label' => $role->getLabel()
        ];
    }

    private function serializeGroup(Group $group): array
    {
        return [
            'id'   => $group->getId(),
            'name' => $group->getName()
        ];
    }
}
