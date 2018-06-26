<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Doctrine\Common\Annotations\AnnotationReader;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Validator\Validation;

class FormProcessorTestCase extends \PHPUnit\Framework\TestCase
{
    protected const TEST_VERSION      = '1.1';
    protected const TEST_REQUEST_TYPE = RequestType::REST;

    /** @var FormContext|SingleItemContext */
    protected $context;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigProvider */
    protected $configProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|MetadataProvider */
    protected $metadataProvider;

    protected function setUp()
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->metadataProvider = $this->createMock(MetadataProvider::class);

        $this->context = $this->createContext();
        $this->context->setVersion(self::TEST_VERSION);
        $this->context->getRequestType()->add(self::TEST_REQUEST_TYPE);
        $this->context->setConfigExtras(
            [
                new EntityDefinitionConfigExtra($this->context->getAction())
            ]
        );
    }

    /**
     * @return FormContext
     */
    protected function createContext()
    {
        return new FormContextStub($this->configProvider, $this->metadataProvider);
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
