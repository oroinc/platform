<?php

namespace Oro\Bundle\EmailBundle\Controller\Configuration;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\TagBundle\Entity\Taggable;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Form\Type\MailboxType;

class MailboxController extends Controller
{
    const ACTIVE_GROUP = 'platform';
    const ACTIVE_SUBGROUP = 'email_configuration';

    /**
     * @Route(
     *      "/system/platform/email_configuration/mailbox/update/{mailbox}",
     *      name="oro_email_mailbox_update"
     * )
     * @Template
     * @AclAncestor("oro_email_mailbox_edit")
     *
     * @param $mailbox
     *
     * @return array
     */
    public function editAction($mailbox)
    {
        $mailboxRepository = $this->getDoctrine()->getRepository('OroEmailBundle:Mailbox');
        $data = $mailboxRepository->find($mailbox);

        return $this->update($data);
    }

    /**
     * Prepares and handles data of Mailbox update/create form.
     *
     * @param $data
     *
     * @return array
     */
    private function update($data)
    {
        $provider = $this->get('oro_config.provider.system_configuration.form_provider');

        list($activeGroup, $activeSubGroup) = $provider->chooseActiveGroups(self::ACTIVE_GROUP, self::ACTIVE_SUBGROUP);

        $tree = $provider->getTree();
        $bc = $provider->getSubtree(self::ACTIVE_SUBGROUP)->toBlockConfig();

        $form = $this->createForm('oro_email_mailbox', $data, [
            'block_config' => $bc,
        ]);

        $form->handleRequest($this->getRequest());

        if ($form->isSubmitted()) {
            if ($this->getRequest()->get(MailboxType::RELOAD_MARKER, false)) {
                $storage = $this->get('oro_email.mailbox.process_storage');

                $type = $form->get('processType')->getViewData();
                $data = $form->getData();

                if (!empty($type)) {
                    $processorEntity = $storage->getNewSettingsEntity($type);
                    $data->setProcessSettings($processorEntity);
                } else {
                    $data->setProcessSettings(null);
                }

                $newForm = $this->createForm('oro_email_mailbox', $data, [
                    'block_config' => $bc,
                ]);

                $form = $newForm;
            } else {
                if ($form->isValid()) {
                    $em = $this->getDoctrine()
                        ->getManager();
                    $em->persist($mailbox = $form->getData());
                    $em->flush();

                    if ($mailbox->getProcessSettings() instanceof Taggable) {
                        $this->get('oro_tag.tag.manager')->saveTagging($mailbox->getProcessSettings());
                    }

                    $this->get('session')->getFlashBag()->add(
                        'success',
                        $this->get('translator')->trans(
                            'oro.email.mailbox.action.saved',
                            ['%mailbox%' => $mailbox->getLabel()]
                        )
                    );

                    return $this->get('oro_ui.router')->redirectAfterSave(
                        ['route' => 'oro_email_mailbox_update', 'parameters' => ['mailbox' => $mailbox->getId()]],
                        [
                            'route' => 'oro_config_configuration_system',
                            'parameters' => [
                                'activeGroup' => self::ACTIVE_GROUP,
                                'activeSubGroup' => self::ACTIVE_SUBGROUP,
                            ]
                        ]
                    );
                }
            }
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
     *      "/system/platform/email_configuration/mailbox/create",
     *      name="oro_email_mailbox_create"
     * )
     * @Template("OroEmailBundle:Configuration/Mailbox:edit.html.twig")
     * @AclAncestor("oro_email_mailbox_create")
     *
     * @return array
     */
    public function createAction()
    {
        $data = new Mailbox();

        return $this->update($data);
    }
}
