<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Manager;

use Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedForm;
use Oro\Bundle\EmbeddedFormBundle\Layout\Form\FormAccessor;
use Oro\Bundle\EmbeddedFormBundle\Manager\EmbedFormLayoutManager;
use Oro\Bundle\EmbeddedFormBundle\Manager\SessionIdProviderInterface;
use Oro\Component\Layout\LayoutBuilderInterface;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\LayoutManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Test\FormInterface;

class EmbedFormLayoutManagerTest extends TestCase
{
    private const TEST_SESSION_FIELD_NAME = 'test_session_field';

    private LayoutManager&MockObject $layoutManager;
    private SessionIdProviderInterface&MockObject $sessionIdProvider;
    private EmbedFormLayoutManager $embedFormLayoutManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->layoutManager = $this->createMock(LayoutManager::class);
        $this->sessionIdProvider = $this->createMock(SessionIdProviderInterface::class);

        $this->embedFormLayoutManager = new EmbedFormLayoutManager($this->layoutManager);
        $this->embedFormLayoutManager->setSessionIdProvider($this->sessionIdProvider);
        $this->embedFormLayoutManager->setSessionIdFieldName(self::TEST_SESSION_FIELD_NAME);
    }

    public function testGetLayoutWithoutForm(): void
    {
        $formEntity = new EmbeddedForm();
        $formEntity->setFormType('testForm');

        $layoutBuilder = $this->createMock(LayoutBuilderInterface::class);
        $this->layoutManager->expects(self::once())
            ->method('getLayoutBuilder')
            ->willReturn($layoutBuilder);
        $layoutBuilder->expects(self::once())
            ->method('add')
            ->with('root', null, 'root');
        $layoutBuilder->expects(self::once())
            ->method('getLayout')
            ->willReturnCallback(function (LayoutContext $context) use ($formEntity) {
                $context->getResolver()->setDefined([
                    'theme',
                    'expressions_evaluate',
                    'expressions_evaluate_deferred'
                ]);
                $context->resolve();

                self::assertEquals('embedded_default', $context->get('theme'));
                self::assertNull($context->get('embedded_form'));
                self::assertEquals($formEntity->getFormType(), $context->get('embedded_form_type'));
                self::assertFalse($context->get('embedded_form_inline'));
                self::assertSame($formEntity, $context->data()->get('embedded_form_entity'));

                return $context;
            });

        self::assertInstanceOf(
            LayoutContext::class,
            $this->embedFormLayoutManager->getLayout($formEntity)
        );
    }

    public function testGetLayoutWithForm(): void
    {
        $formEntity = new EmbeddedForm();
        $formEntity->setFormType('testForm');
        $form = $this->createMock(FormInterface::class);

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
            ->willReturnCallback(function (LayoutContext $context) use ($formEntity) {
                $context->getResolver()->setDefined([
                    'theme',
                    'expressions_evaluate',
                    'expressions_evaluate_deferred'
                ]);
                $context->resolve();

                self::assertEquals('embedded_default', $context->get('theme'));
                self::assertInstanceOf(FormAccessor::class, $context->get('embedded_form'));
                self::assertEquals($formEntity->getFormType(), $context->get('embedded_form_type'));
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
            });

        self::assertInstanceOf(
            LayoutContext::class,
            $this->embedFormLayoutManager->getLayout($formEntity, $form)
        );
    }
}
