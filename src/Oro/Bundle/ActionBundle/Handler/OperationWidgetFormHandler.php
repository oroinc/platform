<?php

namespace Oro\Bundle\ActionBundle\Handler;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Oro\Bundle\ActionBundle\Exception\OperationNotFoundException;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OperationRegistry;

class OperationWidgetFormHandler
{
    const DEFAULT_FORM_TEMPLATE = 'OroActionBundle:Operation:form.html.twig';
    const DEFAULT_PAGE_TEMPLATE = 'OroActionBundle:Operation:page.html.twig';

    /**
     * @var ContextHelper
     */
    private $contextHelper;

    /**
     * @var OperationRegistry
     */
    private $operationRegistry;

    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param FormFactoryInterface $formFactory
     * @param ContextHelper $contextHelper
     * @param OperationRegistry $operationRegistry
     * @param TranslatorInterface $translator
     */
    public function __construct(
        FormFactoryInterface $formFactory,
        ContextHelper $contextHelper,
        OperationRegistry $operationRegistry,
        TranslatorInterface $translator
    ) {
        $this->contextHelper = $contextHelper;
        $this->operationRegistry = $operationRegistry;
        $this->formFactory = $formFactory;
        $this->translator = $translator;
    }

    public function getData($name, Request $request)
    {
        $data = $this->contextHelper->getActionData();

        $operation = $this->getOperation($name, $data);

        return [
            '_wid' => $request->get('_wid'),
            'fromUrl' => $request->get('fromUrl'),
            'operation' => $operation,
            'actionData' => $data,
            'errors' => new ArrayCollection(),
            'messages' => [],
        ];
    }

    /**
     * @param array $data
     * @param Request $request
     * @param FlashBagInterface $flashBag
     *
     * @return string The view name
     */
    public function handle(array $data, Request $request, FlashBagInterface $flashBag)
    {
        //todo checks?
        $actionData = $data['actionData'];
        $operation = $data['operation'];

        /** @var FormInterface $form */
        $form = $this->getOperationForm($operation, $actionData);

        $actionData['form'] = $form;

        $form->handleRequest($request);

        if ($form->isValid()) {
            $operation->execute($actionData, $data['errors']);

            $data['response'] = $this->getResponseData($actionData, $flashBag);

            if ($this->hasRedirect($data)) {
                return new RedirectResponse($data['response']['redirectUrl'], 302);
            }
        }

        $data['form'] = $form->createView();
        $data['context'] = $actionData->getValues();

        return $data;
    }

    /**
     * @param array $data
     * @return string
     * @internal param string $operationName
     */
    public function getTemplate(array $data)
    {
        $template = self::DEFAULT_FORM_TEMPLATE;

        if (isset($data['operation']) && $data['operation'] instanceof Operation) {
            $frontendOptions = $data['operation']->getDefinition()->getFrontendOptions();

            if (array_key_exists('template', $frontendOptions)) {
                $template = $frontendOptions['template'];
            } elseif (array_key_exists('show_dialog', $frontendOptions) && !$frontendOptions['show_dialog']) {
                $template = self::DEFAULT_PAGE_TEMPLATE;
            }
        }

        return $template;
    }

    /**
     * @param array $params
     * @param \Exception $exception
     * @param FlashBagInterface $flashBag
     * @return array of updated params
     */
    public function handleError(array $params, \Exception $exception, FlashBagInterface $flashBag)
    {
        return array_merge($params, $this->getErrorResponse(
            $params,
            $this->getErrorMessages($exception, $params['errors']),
            $flashBag
        ));
    }

    /**
     * @param Operation $operation
     * @param ActionData $data
     *
     * @return FormInterface
     */
    private function getOperationForm(Operation $operation, ActionData $data)
    {
        return $this->formFactory->create(
            $operation->getDefinition()->getFormType(),
            $data,
            array_merge($operation->getFormOptions($data), ['operation' => $operation])
        );
    }

    /**
     * @param string $name
     * @param ActionData $data
     * @return Operation
     * @throws OperationNotFoundException
     */
    private function getOperation($name, ActionData $data)
    {
        $operation = $this->operationRegistry->findByName($name);
        if (!$operation instanceof Operation || !$operation->isAvailable($data)) {
            throw new OperationNotFoundException($name);
        }

        return $operation;
    }

    /**
     * @param \Exception $e
     * @param Collection $errors
     *
     * @return ArrayCollection
     */
    private function getErrorMessages(\Exception $e, Collection $errors = null)
    {
        $messages = new ArrayCollection();

        if (!$errors->count()) {
            $messages->add(['message' => $e->getMessage(), 'parameters' => []]);
        } else {
            foreach ($errors as $key => $error) {
                $messages->set($key, [
                    'message' => sprintf('%s: %s', $e->getMessage(), $error['message']),
                    'parameters' => $error['parameters'],
                ]);
            }
        }

        return $messages;
    }

    /**
     * @param array $params
     * @param Collection $messages
     * @param FlashBagInterface $flashBag
     *
     * @return array
     */
    private function getErrorResponse(array $params, Collection $messages, FlashBagInterface $flashBag)
    {
        if (!empty($params['_wid'])) {
            return [
                'errors' => $messages,
                'messages' => $flashBag->all(),
            ];
        }

        foreach ($messages as $message) {
            $flashBag->add('error', $this->translator->trans($message['message'], $message['parameters']));
        }

        return [];
    }

    /**
     * @param ActionData $context
     *
     * @param FlashBagInterface $flashBag
     * @return array
     */
    private function getResponseData(ActionData $context, FlashBagInterface $flashBag)
    {
        $response = ['success' => true];

        if ($context->getRedirectUrl()) {
            $response['redirectUrl'] = $context->getRedirectUrl();
        } elseif ($context->getRefreshGrid()) {
            $response['refreshGrid'] = $context->getRefreshGrid();
            $response['flashMessages'] = $flashBag->all();
        }

        return $response;
    }

    /**
     * @param array $params
     *
     * @return bool
     */
    private function hasRedirect(array $params)
    {
        return empty($params['_wid']) && !empty($params['response']['redirectUrl']);
    }
}
