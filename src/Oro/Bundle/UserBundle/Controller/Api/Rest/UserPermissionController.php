<?php

namespace Oro\Bundle\UserBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\QueryParam;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestGetController;
use Oro\Bundle\SoapBundle\Request\Parameters\Filter\ChainParameterFilter;
use Oro\Bundle\SoapBundle\Request\Parameters\Filter\EntityClassParameterFilter;
use Oro\Bundle\SoapBundle\Request\Parameters\Filter\StringToArrayParameterFilter;
use Oro\Bundle\UserBundle\Entity\Manager\UserPermissionApiEntityManager;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * REST API controller for user permissions.
 */
class UserPermissionController extends RestGetController
{
    /**
     * Get user permissions
     *
     * @param int $id User id
     *
     * @ApiDoc(
     *      description="Get user permissions",
     *      resource=true
     * )
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    #[QueryParam(name: 'entities', requirements: '.+', description: '"Theentityclassname', nullable: true)]
    #[AclAncestor('oro_user_permission_view')]
    public function cgetAction(int $id)
    {
        $manager = $this->getManager();
        /** @var User $user */
        $user = $manager->find($id);
        if (!$user) {
            return $this->buildNotFoundResponse();
        }

        $criteria = $this->getFilterCriteria(
            $this->getSupportedQueryParameters(__FUNCTION__),
            [
                'entities' => new ChainParameterFilter(
                    [
                        new StringToArrayParameterFilter(),
                        new EntityClassParameterFilter($this->container->get('oro_entity.entity_class_name_helper'))
                    ]
                )
            ],
            [
                'entities' => 'resource'
            ]
        );

        $result = $manager->getUserPermissions($user, $criteria);

        return $this->buildResponse(
            $result,
            self::ACTION_LIST,
            ['result' => $result]
        );
    }

    /**
     * @return UserPermissionApiEntityManager
     */
    public function getManager()
    {
        return $this->container->get('oro_user.permission_manager.api');
    }
}
