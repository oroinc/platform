<?php

namespace Oro\Bundle\EmailBundle\Validator;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Validator\Constraints\VariablesConstraint;

class VariablesValidator extends ConstraintValidator
{
    /** @var \Twig_Environment */
    protected $twig;

    /** @var SecurityContextInterface */
    protected $securityContext;

    /** @var EntityManager */
    protected $entityManager;

    public function __construct(
        \Twig_Environment $twig,
        SecurityContextInterface $securityContext,
        EntityManager $entityManager
    ) {
        $this->twig            = $twig;
        $this->securityContext = $securityContext;
        $this->entityManager   = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($emailTemplate, Constraint $constraint)
    {
        /** @var EmailTemplate $emailTemplate */
        /** @var VariablesConstraint $constraint */

        $fieldsToValidate = array(
            'subject' => $emailTemplate->getSubject(),
            'content' => $emailTemplate->getContent(),
        );

        foreach ($emailTemplate->getTranslations() as $trans) {
            if (in_array($trans->getField(), array('subject', 'content'))) {
                $fieldsToValidate[$trans->getLocale() . '.' . $trans->getField()] = $trans->getContent();
            }
        }

        $relatedEntity = false;
        if (class_exists($emailTemplate->getEntityName())) {
            $className     = $emailTemplate->getEntityName();
            $relatedEntity = new $className;

            $metadata = $this->entityManager->getClassMetadata($className);

            foreach ($metadata->getAssociationMappings() as $mapping) {
                $targetEntity = new $mapping['targetEntity'];
                $fieldName    = $mapping['fieldName'];
                if ($mapping['type'] == ClassMetadataInfo::ONE_TO_MANY) {
                    $targetEntity = new ArrayCollection([$targetEntity]);
                }
                $relatedEntity->{'set' . ucfirst($fieldName)}($targetEntity);
            }
        }

        $errors = array();
        foreach ($fieldsToValidate as $field => $value) {
            try {
                $this->twig->render(
                    $value,
                    array(
                        'entity' => $relatedEntity,
                        'user'   => $this->getUser()
                    )
                );
            } catch (\Exception $e) {
                $errors[$field] = true;
            }
        }

        if (!empty($errors)) {
            $this->context->addViolation($constraint->message);
        }
    }

    /**
     * Return current user
     *
     * @return UserInterface|bool
     */
    private function getUser()
    {
        return $this->securityContext->getToken() && !is_string($this->securityContext->getToken()->getUser())
            ? $this->securityContext->getToken()->getUser() : false;
    }
}
