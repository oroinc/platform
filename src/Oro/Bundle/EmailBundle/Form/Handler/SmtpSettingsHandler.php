<?php

namespace Oro\Bundle\EmailBundle\Form\Handler;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\EmailBundle\Event\SmtpSettingsSaved;
use Oro\Bundle\EmailBundle\Entity\SmtpSettings;
use Oro\Bundle\SoapBundle\Controller\Api\FormAwareInterface;

class SmtpSettingsHandler implements FormAwareInterface
{
    const UPDATE_MARKER = 'formUpdateMarker';

    /** @var RegistryInterface */
    protected $registry;

    /** @var FormInterface */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /**
     * @param Request                  $request
     * @param FormInterface            $form
     * @param RegistryInterface        $registry
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(
        Request $request,
        FormInterface $form,
        RegistryInterface $registry,
        EventDispatcherInterface $dispatcher
    ) {
        $this->registry    = $registry;
        $this->form        = $form;
        $this->request     = $request;
        $this->dispatcher  = $dispatcher;
    }

    /**
     * Process form.
     *
     * @param SmtpSettings $smtpSettings
     *
     * @return bool True on success.
     */
    public function process(SmtpSettings $smtpSettings)
    {
        $this->form->setData($smtpSettings);

        if (in_array($this->request->getMethod(), ['POST', 'PUT'])) {
            $this->form->submit($this->request);
            if (!$this->request->get(self::UPDATE_MARKER, false) && $this->form->isValid()) {
                $this->onSuccess();

                return true;
            }
        }

        return false;
    }

    /**
     * Form validated and can be processed.
     */
    protected function onSuccess()
    {
        /** @var SmtpSettings $smtpSettings */
        $smtpSettings = $this->form->getData();
        $manager = $this->registry->getManager();

        $manager->persist($smtpSettings);
        $manager->flush();

        if ($this->dispatcher->hasListeners(SmtpSettingsSaved::NAME)) {
            $this->dispatcher->dispatch(SmtpSettingsSaved::NAME, new SmtpSettingsSaved($smtpSettings));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getForm()
    {
        return $this->form;
    }
}
