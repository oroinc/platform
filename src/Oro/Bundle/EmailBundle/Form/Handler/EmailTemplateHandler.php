<?php

namespace Oro\Bundle\EmailBundle\Form\Handler;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

class EmailTemplateHandler
{
    use RequestHandlerTrait;

    /** @var FormInterface */
    protected $form;

    /** @var RequestStack */
    protected $requestStack;

    /** @var ObjectManager */
    protected $manager;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var string */
    protected $defaultLocale = 'en';

    /**
     * @param FormInterface       $form
     * @param RequestStack        $requestStack
     * @param ObjectManager       $manager
     * @param TranslatorInterface $translator
     */
    public function __construct(
        FormInterface $form,
        RequestStack $requestStack,
        ObjectManager $manager,
        TranslatorInterface $translator
    ) {
        $this->form       = $form;
        $this->requestStack = $requestStack;
        $this->manager    = $manager;
        $this->translator = $translator;
    }

    /**
     * Process form
     *
     * @param  EmailTemplate $entity
     *
     * @return bool True on successful processing, false otherwise
     */
    public function process(EmailTemplate $entity)
    {
        // always use default locale during template edit in order to allow update of default locale
        $entity->setLocale($this->defaultLocale);
        if ($entity->getId()) {
            // refresh translations
            $this->manager->refresh($entity);
        }

        $this->form->setData($entity);

        $request = $this->requestStack->getCurrentRequest();
        if (in_array($request->getMethod(), ['POST', 'PUT'], true)) {
            // deny to modify system templates
            if ($entity->getIsSystem() && !$entity->getIsEditable()) {
                $this->form->addError(
                    new FormError($this->translator->trans('oro.email.handler.attempt_save_system_template'))
                );

                return false;
            }

            $this->submitPostPutRequest($this->form, $request);

            if ($this->form->isValid()) {
                // mark an email template creating by an user as editable
                if (!$entity->getId()) {
                    $entity->setIsEditable(true);
                }
                $this->manager->persist($entity);
                $this->manager->flush();

                return true;
            }
        }

        return false;
    }

    /**
     * @param string $locale
     */
    public function setDefaultLocale($locale)
    {
        $this->defaultLocale = $locale;
    }
}
