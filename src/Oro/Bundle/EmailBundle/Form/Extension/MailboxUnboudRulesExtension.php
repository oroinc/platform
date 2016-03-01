<?php

namespace Oro\Bundle\EmailBundle\Form\Extension;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\FormBundle\Form\DataTransformer\ArrayToStringTransformer;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntitiesToIdsTransformer;

class MailboxUnboudRulesExtension extends AbstractTypeExtension
{
    /** @var Registry */
    protected $registry;

    /**
     * @param Registry $registry
     */
    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $unboundRules = $builder->create('unboundRules', 'hidden', [
            'mapped' => false,
        ]);

        $transformers = [
            new EntitiesToIdsTransformer(
                $this->getAutoResponseRuleManager(),
                'Oro\Bundle\EmailBundle\Entity\AutoResponseRule'
            ),
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

    /**
     * @return EntityManager
     */
    protected function getAutoResponseRuleManager()
    {
        return $this->registry->getManagerForClass('Oro\Bundle\EmailBundle\Entity\AutoResponseRule');
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'oro_email_mailbox';
    }
}
