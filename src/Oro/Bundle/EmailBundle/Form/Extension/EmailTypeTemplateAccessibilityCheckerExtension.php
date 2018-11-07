<?php

namespace Oro\Bundle\EmailBundle\Form\Extension;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Form\Type\EmailType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * This extension will remove a 'template' field from a form
 * if current user does not have a VIEW permission for EmailTemplate entity
 */
class EmailTypeTemplateAccessibilityCheckerExtension extends AbstractTypeExtension
{
    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    /***
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return EmailType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!$this->authorizationChecker->isGranted('VIEW', 'entity:' . EmailTemplate::class)) {
            $builder->remove('template');
        }
    }
}
