<?php
namespace Oro\Bundle\EmailBundle\Form\EventListener;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;

/**
 * BuildTemplateFormSubscriber used for populating templates choices
 *
 * @package Oro\Bundle\EmailBundle
 */
class BuildTemplateFormSubscriber implements EventSubscriberInterface
{
    /**
     * @var SecurityContextInterface
     */
    protected $securityContext;

    /**
     * @param SecurityContextInterface $securityContext
     */
    public function __construct(SecurityContextInterface $securityContext)
    {
        $this->securityContext = $securityContext;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::PRE_SUBMIT   => 'preSubmit'
        ];
    }

    /**
     * Adds a template field based on the entity set
     *
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        /** @var EmailNotification $eventObject */
        $eventObject = $event->getData();
        if (null === $eventObject || null === $eventObject->getEntityName()) {
            return;
        }

        $this->initChoicesByEntityName($eventObject->getEntityName(), 'template', $event->getForm());
    }

    /**
     * Adds a template field based on the entity set on submitted form
     *
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        /** @var EmailNotification $eventObject */
        $data = $event->getData();
        if (empty($data['entityName'])) {
            return;
        }

        $this->initChoicesByEntityName($data['entityName'], 'template', $event->getForm());
    }

    /**
     * Replace email template field with new choices configuration
     *
     * @param string        $entityName
     * @param string        $fieldName
     * @param FormInterface $form
     */
    protected function initChoicesByEntityName($entityName, $fieldName, FormInterface $form)
    {
        /** @var UsernamePasswordOrganizationToken $token */
        $token        = $this->securityContext->getToken();
        $organization = $token->getOrganizationContext();

        FormUtils::replaceField(
            $form,
            $fieldName,
            [
                'selectedEntity' => $entityName,
                'query_builder'  =>
                    function (EmailTemplateRepository $templateRepository) use (
                        $entityName,
                        $organization
                    ) {
                        return $templateRepository->getEntityTemplatesQueryBuilder($entityName, $organization);
                    },
            ],
            ['choice_list', 'choices']
        );
    }
}
