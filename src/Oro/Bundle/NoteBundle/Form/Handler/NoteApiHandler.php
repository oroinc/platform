<?php

namespace Oro\Bundle\NoteBundle\Form\Handler;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Oro\Bundle\NoteBundle\Entity\Note;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles REST API requests for Note entity.
 */
class NoteApiHandler
{
    use RequestHandlerTrait;

    /** @var FormInterface */
    protected $form;

    /** @var RequestStack */
    protected $requestStack;

    /** @var ObjectManager */
    protected $manager;

    /** @var ConfigManager */
    protected $configManager;

    public function __construct(
        FormInterface $form,
        RequestStack $requestStack,
        ObjectManager $manager,
        ConfigManager $configManager
    ) {
        $this->form          = $form;
        $this->requestStack  = $requestStack;
        $this->manager       = $manager;
        $this->configManager = $configManager;
    }

    /**
     * Process form
     *
     * @param  Note $entity
     *
     * @return bool
     */
    public function process(Note $entity)
    {
        $this->form->setData($entity);
        $request = $this->requestStack->getCurrentRequest();
        if (in_array($request->getMethod(), ['POST', 'PUT'], true)) {
            $request = $this->processRequest($request);
            $this->submitPostPutRequest($this->form, $request);
            if ($this->form->isValid()) {
                $this->onSuccess($entity);

                return true;
            }
        }

        return false;
    }

    protected function processRequest(Request $request)
    {
        $note = $request->request->get('note');

        $association = $note['entityId'];
        if ($association && $association['id']) {
            /** @var ConfigProvider $noteProvider */
            $noteProvider = $this->configManager->getProvider('note');

            /** @var ConfigProvider $extendProvider */
            $extendProvider = $this->configManager->getProvider('extend');

            $fieldName    = ExtendHelper::buildAssociationName($association['entity']);
            if ($noteProvider->hasConfig(Note::class, $fieldName)
                && $extendProvider->getConfig(Note::class, $fieldName)->is('state', ExtendScope::STATE_ACTIVE)
            ) {
                $note[$fieldName] = $association['id'];
                unset($note['entityId']);
                $request->request->set('note', $note);
            } else {
                throw new \SoapFault(
                    'NOT_FOUND',
                    sprintf(
                        'Notes do not enabled or schema not updated for given entity "%s".',
                        $association['entity']
                    )
                );
            }
        } else {
            throw new \SoapFault('NOT_FOUND', 'Associated entity OR it\'s instance Id not found.');
        }

        return $request;
    }

    protected function onSuccess(Note $entity)
    {
        $this->manager->persist($entity);
        $this->manager->flush();
    }
}
