<?php

namespace Oro\Bundle\EntityBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Util\Codes;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;

/**
 * @RouteResource("entity_alias")
 * @NamePrefix("oro_api_")
 */
class EntityAliasController extends FOSRestController implements ClassResourceInterface
{
    /**
     * Get entity aliases.
     *
     * @Get("/entities/aliases", name="")
     *
     * @ApiDoc(
     *      description="Get entity aliases",
     *      resource=true
     * )
     *
     * @return Response
     */
    public function cgetAction()
    {
        /** @var EntityAliasResolver $resolver */
        $resolver = $this->get('oro_entity.entity_alias_resolver');
        $result = $resolver->getAll();

        return $this->handleView($this->view($result, Codes::HTTP_OK));
    }
}
