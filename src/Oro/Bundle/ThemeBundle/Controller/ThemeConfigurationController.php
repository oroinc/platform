<?php

namespace Oro\Bundle\ThemeBundle\Controller;

use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\ThemeBundle\Entity\ThemeConfiguration;
use Oro\Bundle\ThemeBundle\Form\Handler\ThemeConfigurationHandler;
use Oro\Bundle\ThemeBundle\Form\Type\ThemeConfigurationType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Contains CRUD actions for ThemeConfiguration
 */
#[Route("/theme-configuration", name:"oro_theme_configuration_")]
class ThemeConfigurationController extends AbstractController
{
    #[AclAncestor("oro_theme_configuration_view")]
    #[Route("/", name: "index")]
    #[Template]
    public function indexAction(): array
    {
        return [
            'entity_class' => ThemeConfiguration::class
        ];
    }

    #[AclAncestor("oro_theme_configuration_view")]
    #[Route("/view/{id}", name: "view", requirements: ["id" => "\d+"])]
    #[Template]
    public function viewAction(ThemeConfiguration $entity): array
    {
        return [
            'entity' => $entity,
        ];
    }

    #[AclAncestor("oro_theme_configuration_create")]
    #[Route("/create", name: "create", options: ["expose" => true])]
    #[Template("@OroTheme/ThemeConfiguration/update.html.twig")]
    public function createAction(Request $request): array|RedirectResponse
    {
        $createMessage = $this->container->get(TranslatorInterface::class)->trans(
            'oro.theme.themeconfiguration.controller.question.saved.message'
        );

        return $this->update(new ThemeConfiguration(), $request, $createMessage);
    }

    #[AclAncestor("oro_theme_configuration_update")]
    #[Route("/update/{id}", name: "update", requirements: ["id" => "\d+"])]
    #[Template]
    public function updateAction(
        ThemeConfiguration $entity,
        Request $request
    ): array|RedirectResponse {
        $updateMessage = $this->container->get(TranslatorInterface::class)->trans(
            'oro.theme.themeconfiguration.controller.question.saved.message'
        );

        return $this->update($entity, $request, $updateMessage);
    }

    protected function update(
        ThemeConfiguration $entity,
        Request $request,
        string $message = ''
    ): array|RedirectResponse {
        $form = $this->createForm(ThemeConfigurationType::class, $entity);
        if ($request->get($form->getName())) {
            if ($request->get(ThemeConfigurationHandler::WITHOUT_SAVING_KEY)) {
                $configuration = $request->get($form->getName());
                unset($configuration['configuration']);
                $request->request->set($form->getName(), $configuration);
            }

            $form->handleRequest($request);
            $form = $this->createForm(ThemeConfigurationType::class, $form->getData());
        }

        return $this->container->get(UpdateHandlerFacade::class)->update(
            $entity,
            $form,
            $message,
            $request,
            'oro_theme.form.handler.theme_configuration'
        );
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            TranslatorInterface::class,
            UpdateHandlerFacade::class
        ]);
    }
}
