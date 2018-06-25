<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Manager;

use Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedForm;
use Oro\Bundle\EmbeddedFormBundle\Layout\Form\FormAccessor;
use Oro\Bundle\EmbeddedFormBundle\Manager\EmbeddedFormManager;
use Oro\Bundle\EmbeddedFormBundle\Manager\EmbedFormLayoutManager;
use Oro\Bundle\EmbeddedFormBundle\Manager\SessionIdProviderInterface;
use Oro\Component\Layout\LayoutBuilderInterface;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\LayoutManager;
use Symfony\Component\Form\Test\FormInterface;

class EmbedFormLayoutManagerTest extends \PHPUnit\Framework\TestCase
{
    const TEST_SESSION_FIELD_NAME = 'test_session_field';

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $layoutManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $formManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $sessionIdProvider;

    /** @var EmbedFormLayoutManager */
    protected $embedFormLayoutManager;

    protected function setUp()
    {
        $this->layoutManager = $this->getMockBuilder(LayoutManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->formManager = $this->getMockBuilder(EmbeddedFormManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sessionIdProvider = $this->createMock(SessionIdProviderInterface::class);

        $this->embedFormLayoutManager = new EmbedFormLayoutManager(
            $this->layoutManager,
            $this->formManager
        );
        $this->embedFormLayoutManager->setSessionIdProvider($this->sessionIdProvider);
        $this->embedFormLayoutManager->setSessionIdFieldName(self::TEST_SESSION_FIELD_NAME);
    }

    public function testGetLayoutWithoutForm()
    {
        $formEntity = new EmbeddedForm();
        $formEntity->setFormType('testForm');

        $this->formManager->expects(self::once())
            ->method('getCustomFormLayoutByFormType')
            ->with($formEntity->getFormType())
            ->willReturn(null);

        $layoutBuilder = $this->createMock(LayoutBuilderInterface::class);
        $this->layoutManager->expects(self::once())
            ->method('getLayoutBuilder')
            ->willReturn($layoutBuilder);
        $layoutBuilder->expects(self::once())
            ->method('add')
            ->with('root', null, 'root');
        $layoutBuilder->expects(self::once())
            ->method('getLayout')
            ->willReturnCallback(
                function (LayoutContext $context) use ($formEntity) {
                    $context->getResolver()->setDefined([
                        'theme',
                        'expressions_evaluate',
                        'expressions_evaluate_deferred'
                    ]);
                    $context->resolve();

                    self::assertEquals('embedded_default', $context->get('theme'));
                    self::assertNull($context->get('embedded_form'));
                    self::assertEquals($formEntity->getFormType(), $context->get('embedded_form_type'));
                    self::assertNull($context->get('embedded_form_custom_layout'));
                    self::assertFalse($context->get('embedded_form_inline'));
                    self::assertSame($formEntity, $context->data()->get('embedded_form_entity'));

                    return $context;
                }
            );

        self::assertInstanceOf(
            LayoutContext::class,
            $this->embedFormLayoutManager->getLayout($formEntity)
        );
    }

    public function testGetLayoutWithForm()
    {
        $formEntity = new EmbeddedForm();
        $formEntity->setFormType('testForm');
        $form = $this->createMock(FormInterface::class);

        $this->formManager->expects(self::once())
            ->method('getCustomFormLayoutByFormType')
            ->with($formEntity->getFormType())
            ->willReturn(null);

        $this->sessionIdProvider->expects(self::once())
            ->method('getSessionId')
            ->willReturn('test_session_id');

        $layoutBuilder = $this->createMock(LayoutBuilderInterface::class);
        $this->layoutManager->expects(self::once())
            ->method('getLayoutBuilder')
            ->willReturn($layoutBuilder);
        $layoutBuilder->expects(self::once())
            ->method('add')
            ->with('root', null, 'root');
        $layoutBuilder->expects(self::once())
            ->method('getLayout')
            ->willReturnCallback(
                function (LayoutContext $context) use ($formEntity) {
                    $context->getResolver()->setDefined([
                        'theme',
                        'expressions_evaluate',
                        'expressions_evaluate_deferred'
                    ]);
                    $context->resolve();

                    self::assertEquals('embedded_default', $context->get('theme'));
                    self::assertInstanceOf(FormAccessor::class, $context->get('embedded_form'));
                    self::assertEquals($formEntity->getFormType(), $context->get('embedded_form_type'));
                    self::assertNull($context->get('embedded_form_custom_layout'));
                    self::assertFalse($context->get('embedded_form_inline'));
                    self::assertSame($formEntity, $context->data()->get('embedded_form_entity'));
                    self::assertEquals(
                        self::TEST_SESSION_FIELD_NAME,
                        $context->data()->get('embedded_form_session_id_field_name')
                    );
                    self::assertEquals(
                        'test_session_id',
                        $context->data()->get('embedded_form_session_id')
                    );

                    return $context;
                }
            );

        self::assertInstanceOf(
            LayoutContext::class,
            $this->embedFormLayoutManager->getLayout($formEntity, $form)
        );
    }
}
