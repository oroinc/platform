<?php

namespace Oro\Bundle\AttachmentBundle\Form\EventSubscriber;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\AttachmentBundle\Form\Type\MultiImageType;
use Oro\Bundle\AttachmentBundle\Validator\ConfigMultipleFileValidator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * Validates uploaded file collection via ConfigMultipleFileValidator
 */
class MultipleFileSubscriber implements EventSubscriberInterface
{
    /** @var ConfigMultipleFileValidator */
    protected $validator;

    public function __construct(ConfigMultipleFileValidator $validator)
    {
        $this->validator = $validator;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::POST_SUBMIT => 'postSubmit',
        ];
    }

    /**
     * Trigger attachment entity update
     */
    public function postSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        if ($data instanceof Collection && $data->count()) {
            $this->validate($form, $data);
        }
    }

    protected function validate(FormInterface $form, Collection $data)
    {
        $fieldName = $form->getName();

        if ($form->getParent()->getConfig()->getOption('parentEntityClass', null)) {
            $dataClass = $form->getParent()->getConfig()->getOption('parentEntityClass', null);
            $fieldName = '';
        } else {
            $dataClass = $form->getParent()->getConfig()->getDataClass();
            if (!$dataClass) {
                $dataClass = $form->getParent()->getParent()->getConfig()->getDataClass();
            }
        }

        if ($form->getConfig()->getType()->getInnerType() instanceof MultiImageType) {
            $violations = $this->validator->validateImages($data, $dataClass, $fieldName);
        } else {
            $violations = $this->validator->validateFiles($data, $dataClass, $fieldName);
        }

        if ($violations->count()) {
            /** @var ConstraintViolation $violation */
            foreach ($violations as $violation) {
                $error = new FormError(
                    $violation->getMessage(),
                    $violation->getMessageTemplate(),
                    $violation->getParameters()
                );
                $form->addError($error);
            }
        }
    }
}
