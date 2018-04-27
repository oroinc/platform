<?php

namespace Oro\Bundle\EmailBundle\Form\EventListener;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\EmailBundle\Form\Type\AutoResponseTemplateChoiceType;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class AutoResponseRuleSubscriber implements EventSubscriberInterface
{
    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /**
     * @param TokenAccessorInterface $tokenAccessor
     */
    public function __construct(TokenAccessorInterface $tokenAccessor)
    {
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSet',
        ];
    }

    /**
     * @param FormEvent $event
     */
    public function preSet(FormEvent $event)
    {
        if (null === $rule = $event->getData()) {
            return;
        }

        if (null === $template = $rule->getTemplate()) {
            return;
        }

        $templateForm = $event->getForm()->get('template');
        $existingEntityForm = $templateForm->get('existing_entity');
        $templateForm->remove('existing_entity');

        $options = $existingEntityForm->getConfig()->getOptions();
        unset($options['choices']);

        $templateForm->add('existing_entity', AutoResponseTemplateChoiceType::class, array_merge(
            $options,
            [
                'choices' => null,
                'query_builder' => $this->createExistingEntityQueryBuilder($template),
            ]
        ));
    }

    /**
     * @param EmailTemplate $template
     *
     * @return \Closure
     */
    protected function createExistingEntityQueryBuilder(EmailTemplate $template)
    {
        return function (EmailTemplateRepository $repository) use ($template) {
            $qb = $repository->createQueryBuilder('e');

            return $qb
                ->orderBy('e.name', 'ASC')
                ->andWhere('e.entityName = :entityName OR e.entityName IS NULL')
                ->andWhere("e.organization = :organization")
                ->andWhere($qb->expr()->orX(
                    $qb->expr()->eq('e.visible', ':visible'),
                    $qb->expr()->eq('e.id', ':id')
                ))
                ->setParameter('entityName', Email::ENTITY_CLASS)
                ->setParameter('organization', $this->tokenAccessor->getOrganization())
                ->setParameter('id', $template->getId())
                ->setParameter('visible', true);
        };
    }
}
