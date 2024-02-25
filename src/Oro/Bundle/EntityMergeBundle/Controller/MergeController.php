<?php

namespace Oro\Bundle\EntityMergeBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionDispatcher;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Data\EntityDataFactory;
use Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper;
use Oro\Bundle\EntityMergeBundle\Exception\ValidationException;
use Oro\Bundle\EntityMergeBundle\Form\Type\MergeType;
use Oro\Bundle\EntityMergeBundle\Model\EntityMergerInterface;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides action for simple and multiple merge.
 */
#[Route(path: '/merge')]
class MergeController extends AbstractController
{
    /**
     * @param Request $request
     * @param string $gridName
     * @param string $actionName
     * @return array|RedirectResponse
     */
    #[Route(path: '/{gridName}/massAction/{actionName}', name: 'oro_entity_merge_massaction')]
    #[Template('@OroEntityMerge/Merge/merge.html.twig')]
    #[AclAncestor('oro_entity_merge')]
    public function mergeMassActionAction(Request $request, $gridName, $actionName)
    {
        /** @var MassActionDispatcher $massActionDispatcher */
        $massActionDispatcher = $this->container->get(MassActionDispatcher::class);

        $response = $massActionDispatcher->dispatchByRequest($gridName, $actionName, $request);

        $entityData = $this->getEntityDataFactory()->createEntityData(
            $response->getOption('entity_name'),
            $response->getOption('entities')
        );

        return $this->mergeAction($request, $entityData);
    }

    /**
     * @param Request $request
     * @param EntityData|null $entityData
     * @return array|RedirectResponse
     */
    #[Route(name: 'oro_entity_merge')]
    #[Template]
    #[Acl(id: 'oro_entity_merge', label: 'oro.entity_merge.acl.merge', type: 'action', category: 'entity')]
    public function mergeAction(Request $request, EntityData $entityData = null)
    {
        if (!$entityData) {
            $className = $request->get('className');
            $ids = (array)$request->get('ids');

            $entityData = $this->getEntityDataFactory()->createEntityDataByIds($className, $ids);
        } else {
            $className = $entityData->getClassName();
        }

        $flashBag = $request->getSession()->getFlashBag();

        $constraintViolations = $this->getValidator()->validate($entityData, null, ['validateCount']);
        if ($constraintViolations->count()) {
            foreach ($constraintViolations as $violation) {
                /* @var ConstraintViolation $violation */
                $flashBag->add('error', $violation->getMessage());
            }

            return $this->redirect($this->generateUrl($this->getEntityIndexRoute($entityData->getClassName())));
        }

        $form = $this->createForm(
            MergeType::class,
            $entityData,
            [
                'metadata' => $entityData->getMetadata(),
                'entities' => $entityData->getEntities(),
            ]
        );

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $merger = $this->getEntityMerger();

                try {
                    $this->container->get(ManagerRegistry::class)->getManager()->transactional(
                        function () use ($merger, $entityData) {
                            $merger->merge($entityData);
                        }
                    );
                } catch (ValidationException $exception) {
                    foreach ($exception->getConstraintViolations() as $violation) {
                        /* @var ConstraintViolation $violation */
                        $flashBag->add('error', $violation->getMessage());
                    }
                }

                $flashBag->add(
                    'success',
                    $this->container
                        ->get(TranslatorInterface::class)
                        ->trans('oro.entity_merge.controller.merged_successful')
                );

                return $this->redirect(
                    $this->generateUrl(
                        $this->getEntityViewRoute($entityData->getClassName()),
                        ['id' => $entityData->getMasterEntity()->getId()]
                    )
                );
            }
        }

        return [
            'formAction' => $this->generateUrl(
                'oro_entity_merge',
                [
                    'className' => $className,
                    'ids' => $this->getDoctineHelper()->getEntityIds($entityData->getEntities()),
                ]
            ),
            'entityLabel' => $entityData->getMetadata()->get('label'),
            'cancelPath' => $this->generateUrl($this->getEntityIndexRoute($className)),
            'form' => $form->createView()
        ];
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
     * @return ConfigManager
     */
    protected function getConfigManager()
    {
        return $this->container->get(ConfigManager::class);
    }

    /**
     * @return EntityDataFactory
     */
    protected function getEntityDataFactory()
    {
        return $this->container->get(EntityDataFactory::class);
    }

    /**
     * @return DoctrineHelper
     */
    protected function getDoctineHelper()
    {
        return $this->container->get(DoctrineHelper::class);
    }

    /**
     * @return EntityMergerInterface
     */
    protected function getEntityMerger()
    {
        return $this->container->get(EntityMergerInterface::class);
    }

    /**
     * @return ValidatorInterface
     */
    protected function getValidator()
    {
        return $this->container->get(ValidatorInterface::class);
    }

    /**
     * @return array
     */
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                ConfigManager::class,
                EntityDataFactory::class,
                DoctrineHelper::class,
                EntityMergerInterface::class,
                ValidatorInterface::class,
                MassActionDispatcher::class,
                TranslatorInterface::class,
                ManagerRegistry::class,
            ]
        );
    }
}
