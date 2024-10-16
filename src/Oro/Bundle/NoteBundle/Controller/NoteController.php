<?php

namespace Oro\Bundle\NoteBundle\Controller;

use Oro\Bundle\AttachmentBundle\Provider\AttachmentProvider;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\NoteBundle\Entity\Manager\NoteManager;
use Oro\Bundle\NoteBundle\Entity\Note;
use Oro\Bundle\NoteBundle\Form\Handler\NoteHandler;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The controller for Note entity.
 */
#[Route(path: '/notes')]
class NoteController extends AbstractController
{
    #[Route(path: '/view/widget/{entityClass}/{entityId}', name: 'oro_note_widget_notes')]
    #[Template('@OroNote/Note/notes.html.twig')]
    #[AclAncestor('oro_note_view')]
    public function widgetAction($entityClass, $entityId)
    {
        $entity = $this->getEntityRoutingHelper()->getEntity($entityClass, $entityId);

        return [
            'entity' => $entity
        ];
    }

    /**
     *
     * @param Request $request
     * @param string $entityClass
     * @param int $entityId
     * @return Response
     */
    #[Route(path: '/view/{entityClass}/{entityId}', name: 'oro_note_notes')]
    #[AclAncestor('oro_note_view')]
    public function getAction(Request $request, $entityClass, $entityId)
    {
        $entityClass = $this->getEntityRoutingHelper()->resolveEntityClass($entityClass);

        $sorting = strtoupper($request->get('sorting', 'DESC'));

        $manager = $this->getNoteManager();

        $result = $manager->getEntityViewModels(
            $manager->getList($entityClass, $entityId, $sorting)
        );

        return new Response(json_encode($result), Response::HTTP_OK);
    }

    /**
     * @param Request $request
     * @param Note $entity
     * @param string $renderContexts
     * @return array
     */
    #[Route(
        path: '/widget/info/{id}/{renderContexts}',
        name: 'oro_note_widget_info',
        requirements: ['id' => '\d+', 'renderContexts' => '\d+'],
        defaults: ['renderContexts' => true]
    )]
    #[Template('@OroNote/Note/widget/info.html.twig')]
    #[AclAncestor('oro_note_view')]
    public function infoAction(Request $request, Note $entity, $renderContexts)
    {
        $attachmentProvider = $this->container->get(AttachmentProvider::class);
        $attachment = $attachmentProvider->getAttachmentInfo($entity);

        return [
            'entity'         => $entity,
            'target'         => $this->getTargetEntity($request),
            'renderContexts' => (bool)$renderContexts,
            'attachment'     => $attachment
        ];
    }

    /**
     * Get target entity
     *
     * @param Request $request
     * @return object|null
     */
    protected function getTargetEntity(Request $request)
    {
        $entityRoutingHelper = $this->getEntityRoutingHelper();
        $targetEntityClass   = $entityRoutingHelper->getEntityClassName($request, 'targetActivityClass');
        $targetEntityId      = $entityRoutingHelper->getEntityId($request, 'targetActivityId');
        if (!$targetEntityClass || !$targetEntityId) {
            return null;
        }

        return $entityRoutingHelper->getEntity($targetEntityClass, $targetEntityId);
    }

    /**
     *
     * @param Request $request
     * @return array
     */
    #[Route(path: '/create', name: 'oro_note_create')]
    #[Template('@OroNote/Note/update.html.twig')]
    #[AclAncestor('oro_note_create')]
    public function createAction(Request $request)
    {
        $entityRoutingHelper = $this->getEntityRoutingHelper();

        $entityClass = $entityRoutingHelper->getEntityClassName($request);
        $entityId = $entityRoutingHelper->getEntityId($request);

        $noteEntity = new Note();

        $formAction = $entityRoutingHelper->generateUrlByRequest(
            'oro_note_create',
            $request,
            $entityRoutingHelper->getRouteParameters($entityClass, $entityId)
        );

        return $this->update($noteEntity, $formAction);
    }

    #[Route(path: '/update/{id}', name: 'oro_note_update', requirements: ['id' => '\d+'])]
    #[Template]
    #[AclAncestor('oro_note_update')]
    public function updateAction(Note $entity)
    {
        $formAction = $this->container->get('router')->generate('oro_note_update', ['id' => $entity->getId()]);

        return $this->update($entity, $formAction);
    }

    protected function update(Note $entity, $formAction)
    {
        $responseData = [
            'entity' => $entity,
            'saved'  => false
        ];

        if ($this->container->get(NoteHandler::class)->process($entity)) {
            $responseData['saved'] = true;
            $responseData['model'] = $this->getNoteManager()->getEntityViewModel($entity);
        }
        $responseData['form']        = $this->getForm()->createView();
        $responseData['formAction']  = $formAction;
        $translator = $this->container->get(TranslatorInterface::class);
        if ($entity->getId()) {
            $responseData['submitLabel'] = $translator->trans('oro.note.save.label');
        } else {
            $responseData['submitLabel'] = $translator->trans('oro.note.add.label');
        }

        return $responseData;
    }

    public function getForm(): FormInterface
    {
        return $this->container->get('oro_note.form.note');
    }

    protected function getNoteManager(): NoteManager
    {
        return $this->container->get(NoteManager::class);
    }

    protected function getEntityRoutingHelper(): EntityRoutingHelper
    {
        return $this->container->get(EntityRoutingHelper::class);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                AttachmentProvider::class,
                NoteHandler::class,
                NoteManager::class,
                EntityRoutingHelper::class,
                'oro_note.form.note' => Form::class,
            ]
        );
    }
}
