<?php

namespace Oro\Bundle\EmbeddedFormBundle\Manager;

use Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedForm;
use Oro\Bundle\EmbeddedFormBundle\Layout\Form\FormAccessor;
use Oro\Component\Layout\Layout;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\LayoutManager;
use Symfony\Component\Form\FormInterface;

/**
 * Manages creating layouts for embedded forms.
 */
class EmbedFormLayoutManager
{
    /** @var LayoutManager */
    protected $layoutManager;

    /** @var SessionIdProviderInterface */
    protected $sessionIdProvider;

    /** @var string */
    protected $sessionIdFieldName;

    /**
     * Use inline layout
     * @var bool
     */
    protected $inline = false;

    public function __construct(LayoutManager $layoutManager)
    {
        $this->layoutManager = $layoutManager;
    }

    /**
     * @param EmbeddedForm  $formEntity
     * @param FormInterface $form
     *
     * @return Layout
     */
    public function getLayout(EmbeddedForm $formEntity, FormInterface $form = null)
    {
        $formTypeName = $formEntity->getFormType();

        $layoutContext = new LayoutContext();

        $layoutContext->set('expressions_evaluate', true);
        $layoutContext->set('expressions_evaluate_deferred', false);

        $layoutContext->getResolver()
            ->setRequired([
                'embedded_form',
                'embedded_form_type',
                'embedded_form_inline'
            ]);

        $layoutContext->set('theme', 'embedded_default');
        $layoutContext->set('embedded_form', null === $form ? null : new FormAccessor($form));
        $layoutContext->set('embedded_form_type', $formTypeName);
        $layoutContext->set('embedded_form_inline', $this->inline);
        $layoutContext->data()->set('embedded_form_entity', $formEntity);
        if (null !== $form) {
            $layoutContext->data()->set('embedded_form_session_id_field_name', $this->sessionIdFieldName);
            $layoutContext->data()->set('embedded_form_session_id', $this->getSessionId());
        }

        $layoutBuilder = $this->layoutManager->getLayoutBuilder();
        $layoutBuilder->add('root', null, 'root');

        return $layoutBuilder->getLayout($layoutContext);
    }

    /**
     * @param bool $inline
     */
    public function setInline($inline)
    {
        $this->inline = (bool)$inline;
    }

    public function setSessionIdProvider(SessionIdProviderInterface $sessionIdProvider)
    {
        $this->sessionIdProvider = $sessionIdProvider;
    }

    /**
     * @param string $sessionIdFieldName
     */
    public function setSessionIdFieldName($sessionIdFieldName)
    {
        $this->sessionIdFieldName = $sessionIdFieldName;
    }

    /**
     * @return string|null
     */
    protected function getSessionId()
    {
        $sessionId = null;
        if ($this->sessionIdFieldName && null !== $this->sessionIdProvider) {
            $sessionId = $this->sessionIdProvider->getSessionId();
        }

        return $sessionId;
    }
}
