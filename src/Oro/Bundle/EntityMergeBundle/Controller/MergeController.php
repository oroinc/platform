<?php

namespace Oro\Bundle\EntityMergeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionDispatcher;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Data\EntityDataFactory;

/**
 * @Route("/merge")
 */
class MergeController extends Controller
{

    /**
     * @Route("/{gridName}/massAction/{actionName}", name="oro_entity_merge_massaction")
     * @AclAncestor("oro_entity_merge")
     * @Template("OroEntityMergeBundle:Merge:merge.html.twig")
     */
    public function mergeMassActionAction($gridName, $actionName)
    {
        /** @var MassActionDispatcher $massActionDispatcher */
        $massActionDispatcher = $this->get('oro_datagrid.mass_action.dispatcher');

        $response = $massActionDispatcher->dispatchByRequest($gridName, $actionName, $this->getRequest());

        $entityData =  $this->getEntityDataFactory()->createEntityData(
            $response->getOption('entity_name'),
            $response->getOption('entities')
        );

        return $this->mergeAction($entityData);
    }

    /**
     * @Route(name="oro_entity_merge")
     * @Acl(
     *      id="oro_entity_merge",
     *      label="oro.entity_merge.action.merge",
     *      type="action"
     * )
     * @Template()
     */
    public function mergeAction(EntityData $entityData = null)
    {
        if (!$entityData) {
            $entityName = $this->getRequest()->get('entityName');
            $ids = (array)$this->getRequest()->get('ids');

            $entityData = $this->getEntityDataFactory()->createEntityDataByIds($entityName, $ids);
        }

        $form = $this->createForm(
            'oro_entity_merge',
            $entityData,
            array(
                'metadata' => $entityData->getMetadata(),
                'entities' => $entityData->getEntities(),
            )
        );

        if ($this->getRequest()->isMethod('POST')) {
            $form->submit($this->getRequest());
            if ($form->isValid()) {
                // @todo Run merge and flush (use transations)

                // @todo Validate master entity once more

                // @todo Flash message with success or error

                return $this->redirect(
                    $this->generateUrl(
                        $this->getEntityViewRoute($entityData->getClassName()),
                        array('id' => $entityData->getMasterEntity()->getId())
                    )
                );
            }
        }

        return array(
            'cancelRoute' => $this->getEntityIndexRoute($entityData->getClassName()),
            'form' => $form->createView()
        );
    }

    /**
     * Get route name for entity view page by class name
     *
     * @param string $className
     * @return string
     */
    protected function getEntityViewRoute($className)
    {
        /** @var \Oro\Bundle\EntityConfigBundle\Config\ConfigManager $configManager */
        $configManager = $this->get('oro_entity_config.config_manager');
        return $configManager->getEntityMetadata($className)->routeView;
    }

    /**
     * Get route name for entity index page by class name
     *
     * @param string $className
     * @return string
     */
    protected function getEntityIndexRoute($className)
    {
        /** @var \Oro\Bundle\EntityConfigBundle\Config\ConfigManager $configManager */
        $configManager = $this->get('oro_entity_config.config_manager');
        return $configManager->getEntityMetadata($className)->routeName;
    }

    /**
     * @return EntityDataFactory
     */
    protected function getEntityDataFactory()
    {
        return $this->get('oro_entity_merge.data.entity_data_factory');
    }
}
