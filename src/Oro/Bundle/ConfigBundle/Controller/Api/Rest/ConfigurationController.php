<?php

namespace Oro\Bundle\ConfigBundle\Controller\Api\Rest;

use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\Controller\FOSRestController;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use Oro\Bundle\ConfigBundle\Exception\ItemNotFoundException;

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
    public function getAction($path)
    {
        $manager = $this->get('oro_config.manager.api');

        try {
            $data = $manager->getData($path, $this->getRequest()->get('scope', 'user'));
        } catch (ItemNotFoundException $e) {
            return $this->handleView($this->view(null, Codes::HTTP_NOT_FOUND));
        }

        return $this->handleView(
            $this->view($data, Codes::HTTP_OK)
        );
    }
}
