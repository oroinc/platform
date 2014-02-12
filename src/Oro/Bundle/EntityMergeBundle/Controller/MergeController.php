<?php

namespace Oro\Bundle\EntityMergeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Doctrine\ORM\EntityManager;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionDispatcher;

use Oro\Bundle\EntityMergeBundle\Model\EntityMerger;
use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Data\EntityDataFactory;
use Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper;

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
            $className = $this->getRequest()->get('className');
            $ids = (array)$this->getRequest()->get('ids');

            $entityData = $this->getEntityDataFactory()->createEntityDataByIds($className, $ids);
        } else {
            $className = $entityData->getClassName();
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

                $merger = $this->getEntityMerger();
                $this->getEntityManager()->transactional(
                    function () use ($merger, $entityData) {
                        $merger->merge($entityData);
                    }
                );

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
            'formAction' => $this->generateUrl(
                'oro_entity_merge',
                array(
                    'className' => $className,
                    'ids' => $this->getDoctineHelper()->getEntityIds($entityData->getEntities()),
                )
            ),
            'entityPluralLabel' => $this->getEntityPluralLabel($className),
            'cancelPath' => $this->generateUrl($this->getEntityIndexRoute($className)),
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
        return $this->getConfigManager()->getEntityMetadata($className)->routeView;
    }

    /**
     * Get route name for entity index page by class name
     *
     * @param string $className
     * @return string
     */
    protected function getEntityIndexRoute($className)
    {
        return $this->getConfigManager()->getEntityMetadata($className)->routeName;
    }

    /**
     * Get plural label by entity name
     *
     * @param string $className
     * @return string
     */
    protected function getEntityPluralLabel($className)
    {
        return $this->getConfigManager()->getProvider('entity')->getConfig($className)->get('pluralLabel');
    }

    /**
     * @return \Oro\Bundle\EntityConfigBundle\Config\ConfigManager
     */
    protected function getConfigManager()
    {
        return $this->get('oro_entity_config.config_manager');
    }

    /**
     * @return EntityDataFactory
     */
    protected function getEntityDataFactory()
    {
        return $this->get('oro_entity_merge.data.entity_data_factory');
    }

    /**
     * @return DoctrineHelper
     */
    protected function getDoctineHelper()
    {
        return $this->get('oro_entity_merge.doctrine_helper');
    }

    /**
     * @return EntityMerger
     */
    protected function getEntityMerger()
    {
        return $this->get('oro_entity_merge.merger');
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->get('doctrine.orm.entity_manager');
    }
}
