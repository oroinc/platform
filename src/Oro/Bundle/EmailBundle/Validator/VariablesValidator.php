<?php

namespace Oro\Bundle\EmailBundle\Validator;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Provider\VariablesProvider;
use Oro\Bundle\EmailBundle\Validator\Constraints\VariablesConstraint;

class VariablesValidator extends ConstraintValidator
{
    /** @var \Twig_Environment */
    protected $twig;

    /** @var VariablesProvider */
    protected $variablesProvider;

    /** @var EntityManager */
    protected $entityManager;

    /**
     * @param \Twig_Environment $twig
     * @param VariablesProvider $variablesProvider
     * @param EntityManager     $entityManager
     */
    public function __construct(
        \Twig_Environment $twig,
        VariablesProvider $variablesProvider,
        EntityManager $entityManager
    ) {
        $this->twig              = $twig;
        $this->variablesProvider = $variablesProvider;
        $this->entityManager     = $entityManager;
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

        $className = $emailTemplate->getEntityName();
        if (class_exists($className)) {
            /** @var ClassMetadataInfo $metadata */
            $classMetadata = $this->entityManager->getClassMetadata($className);
            if ($classMetadata->getReflectionClass()->isAbstract()) {
                $this->context->addViolation(
                    sprintf('Its not possible to create template for "%s"', $className)
                );
            }

            $entity = $classMetadata->newInstance();

            /** @var \Twig_Extension_Sandbox $sandbox */
            $sandbox = $this->twig->getExtension('sandbox');
            $sandbox->enableSandbox();

            $hasErrors = false;
            foreach ($fieldsToValidate as $template) {
                try {
                    $this->twig->render(
                        $template,
                        array(
                            'entity' => $entity,
                            'system' => $this->variablesProvider->getSystemVariableValues()
                        )
                    );
                } catch (\Twig_Error $e) {
                    $hasErrors = true;
                }
            }

            $sandbox->disableSandbox();

            if ($hasErrors) {
                $this->context->addViolation($constraint->message);
            }
        }
    }
}
