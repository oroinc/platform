<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form;

use Oro\Bundle\ApiBundle\Form\ApiFormBuilder;
use Oro\Bundle\ApiBundle\Form\ApiResolvedFormTypeFactory;
use Oro\Bundle\ApiBundle\Form\Extension\CustomizeFormDataExtension;
use Oro\Bundle\ApiBundle\Form\Extension\EmptyDataExtension;
use Oro\Bundle\ApiBundle\Form\FormExtensionCheckerInterface;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataHandler;
use Oro\Bundle\ApiBundle\Util\EntityInstantiator;
use Oro\Component\ChainProcessor\ActionProcessorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\ResolvedFormTypeFactory;

class ApiFormTypeTestCase extends \PHPUnit\Framework\TestCase
{
    /** @var FormFactoryInterface */
    protected $factory;

    /** @var ApiFormBuilder */
    protected $builder;

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    protected function setUp(): void
    {
        $formExtensionChecker = $this->createMock(FormExtensionCheckerInterface::class);
        $formExtensionChecker->expects(self::any())
            ->method('isApiFormExtensionActivated')
            ->willReturn(true);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->factory = Forms::createFormFactoryBuilder()
            ->addExtensions($this->getExtensions())
            ->addTypeExtensions($this->getTypeExtensions())
            ->addTypes($this->getTypes())
            ->addTypeGuessers($this->getTypeGuessers())
            ->setResolvedTypeFactory(
                new ApiResolvedFormTypeFactory(new ResolvedFormTypeFactory(), $formExtensionChecker)
            )
            ->getFormFactory();
        $this->builder = new ApiFormBuilder('', null, $this->dispatcher, $this->factory);
    }

    protected function getApiTypeExtensions(): array
    {
        $customizationProcessor = $this->createMock(ActionProcessorInterface::class);
        $customizationProcessor->expects(self::any())
            ->method('createContext')
            ->willReturn($this->createMock(CustomizeFormDataContext::class));
        $entityInstantiator = $this->createMock(EntityInstantiator::class);
        $entityInstantiator->expects(self::any())
            ->method('instantiate')
            ->willReturnCallback(function ($class) {
                return new $class();
            });

        return [
            FormType::class => [
                new CustomizeFormDataExtension(
                    $customizationProcessor,
                    $this->createMock(CustomizeFormDataHandler::class)
                ),
                new EmptyDataExtension($entityInstantiator)
            ]
        ];
    }

    protected function getExtensions(): array
    {
        return [];
    }

    protected function getTypeExtensions(): array
    {
        return [];
    }

    protected function getTypes(): array
    {
        return [];
    }

    protected function getTypeGuessers(): array
    {
        return [];
    }

    public static function assertDateTimeEquals(\DateTime $expected, \DateTime $actual): void
    {
        self::assertEquals($expected->format('c'), $actual->format('c'));
    }

    public static function assertDateIntervalEquals(\DateInterval $expected, \DateInterval $actual): void
    {
        self::assertEquals($expected->format('%RP%yY%mM%dDT%hH%iM%sS'), $actual->format('%RP%yY%mM%dDT%hH%iM%sS'));
    }
}
