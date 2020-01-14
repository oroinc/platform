<?php

namespace Oro\Bundle\TagBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Symfony\Component\HttpFoundation\Response;

/**
 * API CRUD controller for Tag entity.
 *
 * @RouteResource("tag")
 * @NamePrefix("oro_api_")
 */
class TagController extends RestController implements ClassResourceInterface
{
    /**
     * REST DELETE
     *
     * @param int $id
     *
     * @Rest\Delete(requirements={"id"="\d+"})
     *
     * @ApiDoc(
     *      description="Delete tag",
     *      resource=true
     * )
     * @Acl(
     *      id="oro_tag_delete",
     *      type="entity",
     *      class="OroTagBundle:Tag",
     *      permission="DELETE"
     * )
     * @return Response
     */
    public function deleteAction(int $id)
    {
        return $this->handleDeleteRequest($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->get('oro_tag.tag.manager.api');
    }

    /**
     * {@inheritdoc}
     */
    public function getForm()
    {
        return $this->get('oro_tag.form.tag.api');
    }

    /**
     * {@inheritdoc}
     */
    public function getFormHandler()
    {
        return $this->get('oro_tag.form.handler.api');
    }
}
