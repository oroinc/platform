<?php

namespace Oro\Bundle\EmailBundle\Controller;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EmailBundle\Entity\AutoResponseRule;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Entity\Repository\AutoResponseRuleRepository;
use Oro\Bundle\EmailBundle\Form\Type\AutoResponseRuleType;
use Oro\Bundle\EmailBundle\Form\Type\AutoResponseTemplateType;
use Oro\Bundle\EmailBundle\Manager\AutoResponseManager;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/autoresponserule")
 */
class AutoResponseRuleController extends Controller
{
    /**
     * @Route("/create/{mailbox}")
     * @Acl(
     *      id="oro_email_autoresponserule_create",
     *      type="entity",
     *      class="OroEmailBundle:AutoResponseRule",
     *      permission="CREATE"
     * )
     * @Template("OroEmailBundle:AutoResponseRule:dialog/update.html.twig")
     * @param Request $request
     * @param Mailbox|null $mailbox
     * @return array
     */
    public function createAction(Request $request, Mailbox $mailbox = null)
    {
        $rule = new AutoResponseRule();
        if ($mailbox) {
            $rule->setMailbox($mailbox);
        }

        return $this->update($request, $rule);
    }

    /**
     * @Route("/update/{id}", requirements={"id"="\d+"})
     * @Acl(
     *      id="oro_email_autoresponserule_update",
     *      type="entity",
     *      class="OroEmailBundle:AutoResponseRule",
     *      permission="EDIT"
     * )
     * @Template("OroEmailBundle:AutoResponseRule:dialog/update.html.twig")
     * @param AutoResponseRule $rule
     * @param Request $request
     * @return array
     */
    public function updateAction(AutoResponseRule $rule, Request $request)
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

    /**
     * @Route("/template/{id}", options={"expose"=true})
     * @AclAncestor("oro_email_emailtemplate_update")
     * @Template
     */
    public function editTemplateAction(EmailTemplate $template)
    {
        $form = $this->createForm(AutoResponseTemplateType::class, $template);

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @param Request $request
     * @param AutoResponseRule $rule
     *
     * @return array
     */
    protected function update(Request $request, AutoResponseRule $rule)
    {
        $form = $this->createForm(AutoResponseRuleType::class, $rule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getAutoResponseRuleManager();
            $em->persist($rule);
            $em->flush();

            $this->clearAutoResponses();
        }

        $entity = $this->getAutoResponseManager()->createEmailEntity();

        return [
            'form'  => $form->createView(),
            'saved' => $form->isValid(),
            'emailEntityData' => $entity,
            'metadata' => $this->get('oro_query_designer.query_designer.manager')->getMetadata('string')
        ];
    }

    /**
     * Cleans old unassigned auto response rules
     */
    private function clearAutoResponses()
    {
        $this->getEventDispatcher()->addListener(
            'kernel.terminate',
            [$this->getAutoResponseRuleRepository(), 'clearAutoResponses']
        );
    }

    /**
     * @return EventDispatcherInterface
     */
    protected function getEventDispatcher()
    {
        return $this->get('event_dispatcher');
    }

    /**
     * @return AutoResponseManager
     */
    protected function getAutoResponseManager()
    {
        return $this->get('oro_email.autoresponserule_manager');
    }

    /**
     * @return AutoResponseRuleRepository
     */
    protected function getAutoResponseRuleRepository()
    {
        return $this->getDoctrine()->getRepository('OroEmailBundle:AutoResponseRule');
    }

    /**
     * @return EntityManager
     */
    protected function getAutoResponseRuleManager()
    {
        return $this->getDoctrine()->getManagerForClass('OroEmailBundle:AutoResponseRule');
    }
}
