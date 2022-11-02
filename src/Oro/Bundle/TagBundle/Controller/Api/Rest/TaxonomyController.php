<?php

namespace Oro\Bundle\TagBundle\Controller\Api\Rest;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API controller for Taxonomy entity.
 */
class TaxonomyController extends RestController
{
    /**
     * REST DELETE
     *
     * @param int $id
     *
     * @ApiDoc(
     *      description="Delete taxonomy",
     *      resource=true
     * )
     * @Acl(
     *      id="oro_taxonomy_delete",
     *      type="entity",
     *      class="OroTagBundle:Taxonomy",
     *      permission="DELETE"
     * )
     * @return Response
     */
    public function deleteAction(int $id)
    {
        return $this->handleDeleteRequest($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->get('oro_tag.taxonomy.manager.api');
    }

    /**
     * {@inheritdoc}
     */
    public function getForm()
    {
        throw new \BadMethodCallException('Form is not available.');
    }

    /**
     * {@inheritdoc}
     */
    public function getFormHandler()
    {
        return $this->get('oro_tag.form.handler.api');
    }
}
