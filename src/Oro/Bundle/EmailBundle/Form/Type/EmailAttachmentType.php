<?php

namespace Oro\Bundle\EmailBundle\Form\Type;

use Doctrine\Common\Persistence\ObjectManager;

use Gaufrette\Filesystem;

use Knp\Bundle\GaufretteBundle\FilesystemMap;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\EmailBundle\Entity\EmailAttachmentContent;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment as EmailAttachmentEntity;
use Oro\Bundle\EmailBundle\Form\Model\EmailAttachment as AttachmentModel;
use Oro\Bundle\FormBundle\Form\Exception\FormException;

class EmailAttachmentType extends AbstractType
{
    /**
     * @var ObjectManager
     */
    protected $em;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @param ObjectManager $objectManager
     */
    public function __construct(ObjectManager $objectManager, FilesystemMap $filesystemMap)
    {
        $this->em = $objectManager;
        $this->filesystem = $filesystemMap->get('attachments');
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'oro_email_attachment';
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class'         => 'Oro\Bundle\EmailBundle\Form\Model\EmailAttachment',
            'intention'          => 'email_attachment',
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('id', 'text', ['required' => true]);
        $builder->add('type', 'text', ['required' => true]);

        $builder->addEventListener(FormEvents::SUBMIT, [$this, 'initAttachmentEntity']);
    }

    /**
     * @param FormEvent $event
     *
     * @throws FormException
     */
    public function initAttachmentEntity(FormEvent $event)
    {
        /** @var AttachmentModel $attachment */
        $attachment = $event->getData();

        if (!$attachment->getEmailAttachment()) {
            switch ($attachment->getType()) {
                case AttachmentModel::TYPE_ATTACHMENT:
                    $repo = $this->em->getRepository('OroAttachmentBundle:Attachment');
                    $oroAttachment = $repo->find($attachment->getId());
                    $emailAttachment = $this->createEmailAttachmentFromOroAttachment($oroAttachment);

                    break;
                case AttachmentModel::TYPE_EMAIL_ATTACHMENT:
                    $repo = $this->em->getRepository('OroEmailBundle:EmailAttachment');
                    $emailAttachment = $repo->find($attachment->getId());

                    break;
                default:
                    throw new FormException(sprintf('Invalid attachment type: %s', $attachment->getType()));
            }

            $attachment->setEmailAttachment($emailAttachment);
        }

        $event->setData($attachment);
    }

    /**
     * @param Attachment $oroAttachment
     *
     * @return EmailAttachmentEntity
     */
    protected function createEmailAttachmentFromOroAttachment(Attachment $oroAttachment)
    {
        $emailAttachmentEntity = new EmailAttachmentEntity();

        $emailAttachmentEntity->setFileName($oroAttachment->getFile()->getFilename());

        $emailAttachmentContent = new EmailAttachmentContent();
        $emailAttachmentContent->setContent(
            base64_encode(file_get_contents($oroAttachment->getFile()->getFilename()))
        );

        $emailAttachmentContent->setContentTransferEncoding('base64');
        $emailAttachmentContent->setEmailAttachment($emailAttachmentEntity);

        $emailAttachmentEntity->setContent($emailAttachmentContent);
        $emailAttachmentEntity->setContentType($oroAttachment->getFile()->getMimeType());
        $emailAttachmentEntity->setFileName(
            $this->filesystem->get($oroAttachment->getFile()->getFilename())->getContent()
        );

        return $emailAttachmentEntity;
    }
}
