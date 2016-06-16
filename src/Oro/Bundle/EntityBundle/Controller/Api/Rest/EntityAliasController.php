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
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;

/**
 * @RouteResource("entity_alias")
 * @NamePrefix("oro_api_")
 */
class EntityAliasController extends FOSRestController implements ClassResourceInterface
{
    /**
     * Get entity aliases.
     *
     * @Get("/entities/aliases")
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
        /** @var EntityClassNameHelper $entityClassNameHelper */
        $entityClassNameHelper = $this->get('oro_entity.entity_class_name_helper');
        /** @var  $entityClassNameHelper */
        $entityConfigManager = $this->get('oro_entity_config.config_manager');

        $result = [];
        foreach ($resolver->getAll() as $className => $entityAlias) {
            $isCustomEntity = false;
            if ($entityConfigManager->hasConfig($className)) {
                $config = $entityConfigManager->getEntityConfig('extend', $className);
                $isCustomEntity = $config->get('owner') !== 'System';
            }

            $result[] = [
                'entity'      => $className,
                'alias'       => $entityAlias->getAlias(),
                'pluralAlias' => $entityAlias->getPluralAlias(),
                'urlSafeName' => $entityClassNameHelper->getUrlSafeClassName($className),
                'isCustomEntity' => $isCustomEntity
            ];
        }

        return $this->handleView($this->view($result, Codes::HTTP_OK));
    }
}
