<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CustomizeFormData;

use Doctrine\Common\Annotations\AnnotationReader;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Validator\Constraints\AccessGrantedValidator;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\ContainerConstraintValidatorFactory;
use Symfony\Component\Validator\Validation;

class CustomizeFormDataProcessorTestCase extends \PHPUnit\Framework\TestCase
{
    protected const TEST_VERSION = '1.1';
    protected const TEST_REQUEST_TYPE = RequestType::REST;

    protected CustomizeFormDataContext $context;

    protected function setUp(): void
    {
        $this->context = $this->createContext();
        $this->context->setAction('customize_form_data');
        $this->context->setVersion(self::TEST_VERSION);
        $this->context->getRequestType()->add(self::TEST_REQUEST_TYPE);
    }

    protected function createContext(): CustomizeFormDataContext
    {
        return new CustomizeFormDataContext();
    }

    /**
     * @param FormExtensionInterface[] $extensions
     *
     * @return FormBuilder
     */
    protected function createFormBuilder(array $extensions = []): FormBuilder
    {
        $formFactory = Forms::createFormFactoryBuilder()
            ->addExtensions(array_merge($this->getFormExtensions(), $extensions))
            ->getFormFactory();
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        return new FormBuilder(null, null, $dispatcher, $formFactory);
    }

    /**
     * @return FormExtensionInterface[]
     */
    protected function getFormExtensions(): array
    {
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker->expects(self::any())
            ->method('isGranted')
            ->willReturn(true);

        $constraintValidatorFactoryContainer = TestContainerBuilder::create()
            ->add(AccessGrantedValidator::class, new AccessGrantedValidator($authorizationChecker))
            ->getContainer($this);

        $validator = Validation::createValidatorBuilder()
            ->enableAnnotationMapping(true)
            ->setDoctrineAnnotationReader(new AnnotationReader())
            ->setConstraintValidatorFactory(
                new ContainerConstraintValidatorFactory($constraintValidatorFactoryContainer)
            )
            ->getValidator();

        return [new ValidatorExtension($validator)];
    }
}
