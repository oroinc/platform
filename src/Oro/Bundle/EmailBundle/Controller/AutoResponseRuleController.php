<?php

namespace Oro\Bundle\EmailBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\AutoResponseRule;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Entity\Repository\AutoResponseRuleRepository;
use Oro\Bundle\EmailBundle\Form\Type\AutoResponseRuleType;
use Oro\Bundle\EmailBundle\Form\Type\AutoResponseTemplateType;
use Oro\Bundle\EmailBundle\Manager\AutoResponseManager;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\Manager;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The controller for the auto response rule functionality.
 */
#[Route(path: '/autoresponserule')]
class AutoResponseRuleController extends AbstractController
{
    #[Route(path: '/create/{mailbox}')]
    #[Template('@OroEmail/AutoResponseRule/dialog/update.html.twig')]
    #[Acl(
        id: 'oro_email_autoresponserule_create',
        type: 'entity',
        class: AutoResponseRule::class,
        permission: 'CREATE'
    )]
    public function createAction(Request $request, ?Mailbox $mailbox = null): array
    {
        $rule = new AutoResponseRule();
        if ($mailbox) {
            $rule->setMailbox($mailbox);
        }

        return $this->update($request, $rule);
    }

    #[Route(path: '/update/{id}', requirements: ['id' => '\d+'])]
    #[Template('@OroEmail/AutoResponseRule/dialog/update.html.twig')]
    #[Acl(id: 'oro_email_autoresponserule_update', type: 'entity', class: AutoResponseRule::class, permission: 'EDIT')]
    public function updateAction(AutoResponseRule $rule, Request $request): array
    {
        if ($request->isMethod('POST')) {
            $params = $request->request->get(AutoResponseRuleType::NAME);
            if (!$params['template']['existing_entity'] && $rule->getTemplate()) {
                $oldTemplate = $rule->getTemplate();
                if (!$oldTemplate->isVisible()) {
                    $em = $this->getAutoResponseRuleManager();
                    $em->remove($oldTemplate);
                }
                $rule->setTemplate(new EmailTemplate());
            }
        }

        return $this->update($request, $rule);
    }

    #[Route(path: '/template/{id}', defaults: ['id' => 0], options: ['expose' => true])]
    #[Template('@OroEmail/AutoResponseRule/editTemplate.html.twig')]
    #[AclAncestor('oro_email_emailtemplate_update')]
    public function editTemplateAction(?EmailTemplate $template = null): array
    {
        $form = $this->createForm(AutoResponseTemplateType::class, $template);

        return [
            'form' => $form->createView()
        ];
    }

    private function update(Request $request, AutoResponseRule $rule): array
    {
        $form = $this->createForm(AutoResponseRuleType::class, $rule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getAutoResponseRuleManager();
            $em->persist($rule);
            $em->flush();

            $this->clearAutoResponses();
        }

        /** @var AutoResponseManager $autoResponseManager */
        $autoResponseManager = $this->container->get(AutoResponseManager::class);
        $entity = $autoResponseManager->createEmailEntity();

        return [
            'form'  => $form->createView(),
            'saved' => $form->isSubmitted() && $form->isValid(),
            'emailEntityData' => $entity,
            'metadata' => $this->container->get(Manager::class)->getMetadata('string')
        ];
    }

    /**
     * Cleans old unassigned auto response rules
     */
    private function clearAutoResponses(): void
    {
        $this->getEventDispatcher()->addListener(
            'kernel.terminate',
            [$this->getAutoResponseRuleRepository(), 'clearAutoResponses']
        );
    }

    private function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->container->get(EventDispatcherInterface::class);
    }

    private function getAutoResponseRuleRepository(): AutoResponseRuleRepository
    {
        return $this->container->get('doctrine')->getRepository(AutoResponseRule::class);
    }

    private function getAutoResponseRuleManager(): EntityManagerInterface
    {
        return $this->container->get('doctrine')->getManagerForClass(AutoResponseRule::class);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                AutoResponseManager::class,
                Manager::class,
                EventDispatcherInterface::class,
                'doctrine' => ManagerRegistry::class,
            ]
        );
    }
}
