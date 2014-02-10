<?php

namespace Oro\Bundle\EntityMergeBundle\Controller;

use Oro\Bundle\EntityMergeBundle\HttpFoundation\MergeDataRequestFactory;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Metadata\MetadataFactory;
use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;

/**
 * @Route("/merge")
 */
class MergeController extends Controller
{
    /**
     * @Route(name="oro_entity_merge")
     * @Acl(
     *      id="oro_entity_merge",
     *      label="oro.entity_merge.action.merge",
     *      type="action"
     * )
     * @Template()
     */
    public function mergeAction()
    {
        /**
         * @var MergeDataRequestFactory $requestFactory
         */
        $requestFactory = $data  = $this->get('oro_entity_merge.http_foundation.merge_data_request_factory');
        $data = $requestFactory->createMergeData();
        ob_start();
        print_r(count($data));
        print_r($data->getEntities());
        $result = ob_get_contents();

        ob_end_clean();

        return array('result'=>$result);
    }
    /**
     * @Route("/test", name="oro_entity_merge_form_test")
     * @AclAncestor("oro_entity_merge")
     * @Template()
     */
    public function testAction()
    {
        $entityName = $this->container->getParameter('orocrm_account.account.entity.class');

        $entities = $this->getTestEntities($entityName);

        $entityMetadata = $this->getMetadataFactory()->createMergeMetadata($entityName);
        $data = $this->createEntityMergeData($entities, $entityMetadata);

        $form = $this->createForm(
            'oro_entity_merge',
            $data,
            array(
                'metadata' => $entityMetadata,
                'entities' => $entities,
            )
        );

        return array(
            'form' => $form->createView()
        );
    }

    protected function getTestEntities($entityName)
    {
        /** @var \Doctrine\ORM\EntityRepository $repository */
        $repository = $this->getDoctrine()->getRepository($entityName);
        return $repository->createQueryBuilder('e')
            ->orderBy('e.id')
            ->setFirstResult(0)
            ->setMaxResults(5)
            ->getQuery()
            ->execute();
    }

    /**
     * @return MetadataFactory
     */
    protected function getMetadataFactory()
    {
        return $this->get('oro_entity_merge.metadata.factory');
    }

    /**
     * @param object[] $entities
     * @param EntityMetadata $metadata
     * @return EntityData
     */
    protected function createEntityMergeData(array $entities, EntityMetadata $metadata)
    {
        $data = new EntityData($metadata);

        $data->setEntities($entities);

        foreach ($metadata->getFieldsMetadata() as $fieldMetadata) {
            $field = $data->addNewField($fieldMetadata);
        }

        return $data;
    }
}
