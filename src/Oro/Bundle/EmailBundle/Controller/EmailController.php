<?php

namespace Oro\Bundle\EmailBundle\Controller;

use Doctrine\ORM\Query;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\EmailBundle\Cache\EmailCacheManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Decoder\ContentDecoder;
use Oro\Bundle\EmailBundle\Exception\EmailBodyNotFoundException;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

class EmailController extends Controller
{
    /**
     * @Route("/view/{id}", name="oro_email_view", requirements={"id"="\d+"})
     * @Acl(
     *      id="oro_email_view",
     *      type="entity",
     *      class="OroEmailBundle:Email",
     *      permission="VIEW"
     * )
     * @Template
     */
    public function viewAction(Email $entity)
    {
        $templateVars = array('entity' => $entity, 'noBodyFound' => false);
        try {
            $this->getEmailCacheManager()->ensureEmailBodyCached($entity);
        } catch (EmailBodyNotFoundException $e) {
            $templateVars['noBodyFound'] = true;
        }

        return $templateVars;
    }

    /**
     * This action is used to render the list of emails associated with the given entity
     * on the view page of this entity
     *
     * @Route(
     *      "/activity/view/{entityClass}/{entityId}",
     *      name="oro_email_activity_view"
     * )
     *
     * @AclAncestor("oro_email_view")
     * @Template
     */
    public function activityAction($entityClass, $entityId)
    {
        return array(
            'entity' => $this->get('oro_entity.routing_helper')->getEntity($entityClass, $entityId)
        );
    }

    /**
     * @Route("/create")
     * @Acl(
     *      id="oro_email_create",
     *      type="entity",
     *      class="OroEmailBundle:Email",
     *      permission="CREATE"
     * )
     * @Template("OroEmailBundle:Email:update.html.twig")
     */
    public function createAction()
    {
        $entity = new EmailModel();
        $responseData = array(
            'entity' => $entity,
            'saved' => false
        );
        if ($this->get('oro_email.form.handler.email')->process($entity)) {
            $responseData['saved'] = true;
        }
        $responseData['form'] = $this->get('oro_email.form.email')->createView();

        return $responseData;
    }

    /**
     * Get the given email body content
     *
     * @Route("/body/{id}", name="oro_email_body", requirements={"id"="\d+"})
     * @AclAncestor("oro_email_view")
     */
    public function bodyAction(EmailBody $entity)
    {
        return new Response($entity->getBodyContent());
    }

    /**
     * Get a response for download the given email attachment
     *
     * @Route("/attachment/{id}", name="oro_email_attachment", requirements={"id"="\d+"})
     * @AclAncestor("oro_email_view")
     */
    public function attachmentAction(EmailAttachment $entity)
    {
        $response = new Response();
        $response->headers->set('Content-Type', $entity->getContentType());
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $entity->getFileName()));
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        $content = ContentDecoder::decode(
            $entity->getContent()->getContent(),
            $entity->getContent()->getContentTransferEncoding()
        );
        $response->setContent($content);

        return $response;
    }

    /**
     * @Route("/widget", name="oro_email_widget_emails")
     * @Template
     * @AclAncestor("oro_email_view")
     *
     * @param Request $request
     * @return array
     */
    public function emailsAction(Request $request)
    {
        return array(
            'datagridParameters' => $request->query->all()
        );
    }

    /**
     * @Route("/base-widget", name="oro_email_widget_base_emails")
     * @Template
     * @AclAncestor("oro_email_view")
     *
     * @param Request $request
     * @return array
     */
    public function baseEmailsAction(Request $request)
    {
        return array(
            'datagridParameters' => $request->query->all()
        );
    }

    /**
     * @Route("/user-emails", name="oro_email_user_emails")
     * @AclAncestor("oro_email_view")
     * @Template
     */
    public function userEmailsAction()
    {
        return [];
    }

    /**
     * Get email cache manager
     *
     * @return EmailCacheManager
     */
    protected function getEmailCacheManager()
    {
        return $this->container->get('oro_email.email.cache.manager');
    }
}
