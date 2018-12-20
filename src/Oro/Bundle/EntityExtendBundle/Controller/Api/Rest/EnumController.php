<?php

namespace Oro\Bundle\EntityExtendBundle\Controller\Api\Rest;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Util\Codes;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\TranslationBundle\Translation\TranslatableQueryTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * Manage result data by specified entity class
 *
 * @RouteResource("entity_extend_enum")
 * @NamePrefix("oro_api_")
 */
class EnumController extends FOSRestController
{
    use TranslatableQueryTrait;

    /**
     * Get all values of the specified enumeration.
     * Deprecated since 1.9. Use /api/rest/{version}/{dictionary}.{_format} instead.
     * For example /api/rest/latest/leadsources.json or /api/rest/latest/Extend_Entity_EV_Lead_Source.json.
     * Take into account that 'priority' field is replaced with 'order' field.
     * Also by default only the first 10 items are returned. To get all items add '?limit=-1'.
     *
     * @param string $entityName Entity full class name; backslashes (\) should be replaced with underscore (_).
     *
     * @Get("/entity_extends/enum/{entityName}",
     *      requirements={"entityName"="((\w+)_)+(\w+)"}
     * )
     * @ApiDoc(
     *      description="Get all values of the specified enumeration",
     *      resource=true
     * )
     *
     * @return Response
     *
     * @deprecated since 1.9. Use /api/rest/{version}/{dictionary}.{_format} (oro_api_get_dictionary_values) instead
     */
    public function getAction($entityName)
    {
        $entityName = $this->get('oro_entity.routing_helper')->resolveEntityClass($entityName);

        /** @var EntityManager $em */
        $em       = $this->get('doctrine')->getManagerForClass($entityName);
        $enumRepo = $em->getRepository($entityName);
        $query = $enumRepo
            ->createQueryBuilder('e')
            ->getQuery()
            ->setHint(
                Query::HINT_CUSTOM_OUTPUT_WALKER,
                'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
            );

        $this->addTranslatableLocaleHint($query, $em);

        $data = $query->getArrayResult();

        return $this->handleView(
            $this->view($data, Codes::HTTP_OK)
        );
    }
}
