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
        $mergeData = $this->getMergeDataRequestFactory()->createMergeData();

        $form = $this->createForm(
            'oro_entity_merge',
            $mergeData,
            array(
                'metadata' => $mergeData->getMetadata(),
                'entities' => $mergeData->getEntities(),
            )
        );

        return array(
            'form' => $form->createView()
        );
    }

    /**
     * @return MergeDataRequestFactory
     */
    protected function getMergeDataRequestFactory()
    {
        return $this->get('oro_entity_merge.http_foundation.merge_data_request_factory');
    }
}
