<?php

namespace Oro\Bundle\NoteBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\NoteBundle\Entity\EntityId;
use Oro\Bundle\NoteBundle\Entity\NoteSoap;
use Oro\Bundle\NoteBundle\Entity\Note;

class NoteApiHandler
{
    /** @var FormInterface */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var ObjectManager */
    protected $manager;

    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param FormInterface $form
     * @param Request       $request
     * @param ObjectManager $manager
     * @param ConfigManager $configManager
     */
    public function __construct
    (
        FormInterface $form,
        Request $request,
        ObjectManager $manager,
        ConfigManager $configManager
    ) {
        $this->form          = $form;
        $this->request       = $request;
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
        if (in_array($this->request->getMethod(), array('POST', 'PUT'))) {
            $request = $this->processRequest($this->request);
            $this->form->submit($request);
            if ($this->form->isValid()) {
                $this->onSuccess($entity);

                return true;
            }
        }

        return false;
    }

    protected function processRequest(Request $request)
    {
        /** @var NoteSoap $note */
        $note = $request->request->get('note');

        /** @var EntityId $association */
        $association = $note['entityId'];
        if ($association) {
            try {
                /** @var ConfigProvider $noteProvider */
                $noteProvider = $this->configManager->getProvider('note');
                $fieldName    = ExtendHelper::buildAssociationName($association['entity']);
                if ($noteProvider->hasConfig('Oro\Bundle\NoteBundle\Entity\Note', $fieldName)) {
                    $note[$fieldName] = $association['id'];
                    unset ($note['entityId']);
                    $request->request->set('note', $note);
                }
            } catch (\Exception $e) {
                throw new \SoapFault('NOT_FOUND', 'Associated entity OR it\'s instance Id not found.');
            }
        }

        return $request;
    }

    /**
     * @param Note $entity
     */
    protected function onSuccess(Note $entity)
    {
        $this->manager->persist($entity);
        $this->manager->flush();
    }
}
