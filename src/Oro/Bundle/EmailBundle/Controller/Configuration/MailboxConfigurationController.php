<?php

namespace Oro\Bundle\EmailBundle\Controller\Configuration;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Oro\Bundle\EmailBundle\Entity\Mailbox;

class MailboxConfigurationController extends Controller
{
    const ACTIVE_GROUP = 'platform';
    const ACTIVE_SUBGROUP = 'mailbox';

    /**
     * @Route(
     *      "/system/platform/mailbox/update/{mailbox}",
     *      name="oro_email_mailbox_update"
     * )
     * @Template
     * @param $mailbox
     *
     * @return array
     */
    public function editAction($mailbox)
    {
        $provider = $this->get('oro_config.provider.system_configuration.form_provider');

        list($activeGroup, $activeSubGroup) = $provider->chooseActiveGroups(self::ACTIVE_GROUP, self::ACTIVE_SUBGROUP);

        $tree = $provider->getTree();
        $bc = $provider->getSubtree(self::ACTIVE_SUBGROUP)->toBlockConfig();

        $mailboxRepository = $this->getDoctrine()->getRepository('OroEmailBundle:Mailbox');
        $data = $mailboxRepository->find($mailbox);

        $form = $this->createForm('oro_email_mailbox', $data, [
            'block_config' => $bc,
        ]);

        $form->handleRequest($this->getRequest());

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($form->getData());
            $em->flush();
        }

        return [
            'data'           => $tree,
            'form'           => $form->createView(),
            'activeGroup'    => $activeGroup,
            'activeSubGroup' => $activeSubGroup,
        ];
    }

    /**
     * @Route(
     *      "/system/platform/mailbox/create",
     *      name="oro_email_mailbox_create"
     * )
     * @Template("OroEmailBundle:Configuration/MailboxConfiguration:edit.html.twig")
     *
     * @return array
     */
    public function createAction()
    {
        $provider = $this->get('oro_config.provider.system_configuration.form_provider');

        list($activeGroup, $activeSubGroup) = $provider->chooseActiveGroups(self::ACTIVE_GROUP, self::ACTIVE_SUBGROUP);

        $tree = $provider->getTree();
        $bc = $provider->getSubtree(self::ACTIVE_SUBGROUP)->toBlockConfig();

        $form = $this->createForm('oro_email_mailbox', new Mailbox(), [
            'block_config' => $bc,
        ]);

        $form->handleRequest($this->getRequest());

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($form->getData());
            $em->flush();
        }

        return [
            'data'           => $tree,
            'form'           => $form->createView(),
            'activeGroup'    => $activeGroup,
            'activeSubGroup' => $activeSubGroup,
        ];
    }
}
