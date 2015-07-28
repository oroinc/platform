<?php

namespace Oro\Bundle\EntityBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

use FOS\RestBundle\Util\Codes;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * Entities controller.
 * @Route("/dictionary")
 */
class DictionaryController extends Controller
{
    /**
     * Grid of Custom/Extend entity.
     *
     * @param string $entityName
     *
     * @return array
     *
     * @Route(
     *      "filter/{dictionary}",
     *      name="oro_dictionary_filter"
     * )
     * @Template()
     */
    public function filterAction($dictionary)
    {
        $value = $this->getRequest()->get('q');

        $manager = $this->container->get('oro_entity.manager.dictionary.api');
        $manager->setClass($manager->resolveEntityClass($dictionary, true));

        $qb         = $manager->getListQueryBuilder(-1, 1, [], null, []);
        $qb->andWhere('e.name LIKE :like')
            ->setParameter('like', '%'.$value.'%');
        $results = $qb->getQuery()->getResult();

        $resultD= [];
        foreach($results as $result) {
            $resultD[] = [
                'id'=> $result->getName(),
                'value'=> $result->getName(),
                'text'=> $result->getName()
            ];
        }

        $responseContext = ['results' => $resultD, 'query' => $qb];

        return new JsonResponse($responseContext);
    }
}
