<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CustomizeFormData;

use Doctrine\Common\Annotations\AnnotationReader;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Validator\Validation;

class CustomizeFormDataProcessorTestCase extends \PHPUnit\Framework\TestCase
{
    protected const TEST_VERSION      = '1.1';
    protected const TEST_REQUEST_TYPE = RequestType::REST;

    /** @var CustomizeFormDataContext */
    protected $context;

    protected function setUp(): void
    {
        $this->context = $this->createContext();
        $this->context->setVersion(self::TEST_VERSION);
        $this->context->getRequestType()->add(self::TEST_REQUEST_TYPE);
    }

    /**
     * @return CustomizeFormDataContext
     */
    protected function createContext()
    {
        return new CustomizeFormDataContext();
    }

    /**
     * @param FormExtensionInterface[] $extensions
     *
     * @return FormBuilder
     */
    protected function createFormBuilder(array $extensions = [])
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
    protected function getFormExtensions()
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAnnotationMapping(new AnnotationReader())
            ->getValidator();

        return [new ValidatorExtension($validator)];
    }
}
