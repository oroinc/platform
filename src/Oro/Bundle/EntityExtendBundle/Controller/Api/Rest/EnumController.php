<?php


namespace Oro\Bundle\EntityExtendBundle\Controller\Api\Rest;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;

use Symfony\Component\HttpFoundation\Response;

use FOS\Rest\Util\Codes;
use FOS\RestBundle\Controller\FOSRestController;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

/**
 * @RouteResource("entity_extend_enum")
 * @NamePrefix("oro_api_")
 */
class EnumController extends FOSRestController
{
    /**
     * Get all values of the specified enumeration
     *
     * @param string $entityName Entity full class name; backslashes (\) should be replaced with underscore (_).
     *
     * @Get("/entity_extends/enum/{entityName}",
     *      name="oro_api_get_entity_extend_enum",
     *      requirements={"entityName"="((\w+)_)+(\w+)"}
     * )
     * @ApiDoc(
     *      description="Get all values of the specified enumeration",
     *      resource=true
     * )
     *
     * @return Response
     */
    public function getAction($entityName)
    {
        $extendEntitySuffix = str_replace('\\', '_', ExtendConfigDumper::ENTITY);
        $entityName         = strpos($entityName, $extendEntitySuffix) === 0
            ? ExtendConfigDumper::ENTITY . substr($entityName, strlen($extendEntitySuffix))
            : str_replace('_', '\\', $entityName);

        /** @var EntityManager $em */
        $em       = $this->get('doctrine')->getManagerForClass($entityName);
        $enumRepo = $em->getRepository($entityName);
        $data = $enumRepo->createQueryBuilder('e')
            ->getQuery()
            ->setHint(
                Query::HINT_CUSTOM_OUTPUT_WALKER,
                'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
            )
            ->getArrayResult();

        return $this->handleView(
            $this->view($data, Codes::HTTP_OK)
        );
    }
}
