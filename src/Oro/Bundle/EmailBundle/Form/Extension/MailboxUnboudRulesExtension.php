<?php

namespace Oro\Bundle\EmailBundle\Form\Extension;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\AutoResponseRule;
use Oro\Bundle\EmailBundle\Form\Type\MailboxType;
use Oro\Bundle\FormBundle\Form\DataTransformer\ArrayToStringTransformer;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntitiesToIdsTransformer;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Adds "unboundRules" hidden field to {@see MailboxType} form type.
 */
class MailboxUnboudRulesExtension extends AbstractTypeExtension
{
    public function __construct(
        private ManagerRegistry $doctrine
    ) {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $unboundRules = $builder->create('unboundRules', HiddenType::class, [
            'mapped' => false,
        ]);

        $transformers = [
            new EntitiesToIdsTransformer($this->doctrine, AutoResponseRule::class),
            new ArrayToStringTransformer(',', true),
        ];

        array_map([$unboundRules, 'addViewTransformer'], $transformers);

        $builder->add($unboundRules);
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $mailbox = $event->getData();
            $data = $event->getForm()->get('unboundRules')->getData();
            if (!$mailbox || !$data) {
                return;
            }

            $mailbox->setAutoResponseRules(new ArrayCollection($data));
        });
    }

    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [MailboxType::class];
    }
}
