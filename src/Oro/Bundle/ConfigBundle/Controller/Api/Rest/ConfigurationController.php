<?php

namespace Oro\Bundle\ConfigBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Util\Codes;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\ConfigBundle\Exception\ItemNotFoundException;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @RouteResource("configuration")
 * @NamePrefix("oro_api_")
 */
class ConfigurationController extends FOSRestController
{
    /**
     * Get the list of all configuration sections
     *
     * @Get("/configuration")
     * @ApiDoc(
     *      description="Get the list of all configuration sections",
     *      resource=true
     * )
     * @AclAncestor("oro_config_system")
     *
     * @return Response
     */
    public function cgetAction()
    {
        $manager = $this->get('oro_config.manager.api');

        $data = $manager->getSections();

        return $this->handleView(
            $this->view($data, Codes::HTTP_OK)
        );
    }

    /**
     * Get all configuration data of the specified section
     *
     * @param Request $request
     * @param string $path The configuration section path. For example: look-and-feel/grid
     *
     * @Get("/configuration/{path}",
     *      requirements={"path"="[\w-]+[\w-\/]*"}
     * )
     * @ApiDoc(
     *      description="Get all configuration data of the specified section",
     *      resource=true,
     *      filters={
     *          {"name"="scope", "dataType"="string", "description"="Scope name. By default - user"},
     *          {"name"="locale", "dataType"="string", "description"="The preferred locale for configuration values"}
     *      }
     * )
     * @AclAncestor("oro_config_system")
     *
     * @return Response
     */
    public function getAction(Request $request, $path)
    {
        $manager = $this->get('oro_config.manager.api');

        try {
            $data = $manager->getData($path, $request->get('scope', 'user'));
        } catch (ItemNotFoundException $e) {
            return $this->handleView($this->view(null, Codes::HTTP_NOT_FOUND));
        }

        return $this->handleView(
            $this->view($data, Codes::HTTP_OK)
        );
    }
}
