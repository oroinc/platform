<?php

namespace Oro\Bundle\ConfigBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\ConfigBundle\Exception\ItemNotFoundException;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API controller for system configuration.
 */
class ConfigurationController extends AbstractFOSRestController
{
    /**
     * Get the list of all configuration sections
     *
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
            $this->view($data, Response::HTTP_OK)
        );
    }

    /**
     * Get all configuration data of the specified section
     *
     * @param Request $request
     * @param string $path The configuration section path. For example: look-and-feel/grid
     *
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
            return $this->handleView($this->view(null, Response::HTTP_NOT_FOUND));
        }

        return $this->handleView(
            $this->view($data, Response::HTTP_OK)
        );
    }
}
