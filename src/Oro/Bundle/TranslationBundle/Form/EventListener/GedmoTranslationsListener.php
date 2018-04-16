<?php

namespace Oro\Bundle\TranslationBundle\Form\EventListener;

use Oro\Bundle\TranslationBundle\Form\TranslationForm\TranslationFormInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author David ALLIX
 */
class GedmoTranslationsListener implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [FormEvents::PRE_SET_DATA => 'preSetData'];
    }

    /**
     * @param FormEvent $event
     * @throws \LogicException
     */
    public function preSetData(FormEvent $event)
    {
        $form = $event->getForm();

        if (!$form instanceof TranslationFormInterface) {
            throw new \LogicException('Passed form must inherit TranslationFormInterface');
        }

        $translatableClass = $form->getParent() ? $form->getParent()->getConfig()->getDataClass() : null;

        $formOptions = $form->getConfig()->getOptions();
        $childrenOptions = $form->getChildrenOptions($translatableClass, $formOptions);

        foreach ($formOptions['locales'] as $locale) {
            if (isset($childrenOptions[$locale])) {
                $form->add($locale, 'a2lix_translationsFields', [
                    'fields' => $childrenOptions[$locale],
                    'translation_class' => $form->getTranslationClass($translatableClass),
                ]);
            }
        }
    }
}
