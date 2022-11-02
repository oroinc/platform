<?php

namespace Oro\Bundle\EntityBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API controller for entity aliases.
 */
class EntityAliasController extends AbstractFOSRestController
{
    /**
     * Get entity aliases.
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

        return $this->handleView($this->view($result, Response::HTTP_OK));
    }
}
