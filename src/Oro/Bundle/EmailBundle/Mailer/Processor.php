<?php

namespace Oro\Bundle\EmailBundle\Mailer;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Entity\Util\EmailUtil;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin;

class Processor
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var EmailEntityBuilder
     */
    protected $emailEntityBuilder;

    /**
     * @var \Swift_Mailer
     */
    protected $mailer;

    /**
     * @param EntityManager $em
     * @param EmailEntityBuilder $emailEntityBuilder
     * @param \Swift_Mailer $mailer
     */
    public function __construct(
        EntityManager $em,
        EmailEntityBuilder $emailEntityBuilder,
        \Swift_Mailer $mailer
    ) {
        $this->em = $em;
        $this->emailEntityBuilder = $emailEntityBuilder;
        $this->mailer = $mailer;
    }

    /**
     * Process email model sending.
     *
     * @param Email $model
     * @param string $originName
     * @throws \Swift_SwiftException
     */
    public function process(Email $model, $originName = InternalEmailOrigin::BAP)
    {
        $this->assertModel($model);
        $messageDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $message = $this->mailer->createMessage();
        $message->setDate($messageDate->getTimestamp());
        $message->setFrom($this->getAddresses($model->getFrom()));
        $message->setTo($this->getAddresses($model->getTo()));
        $message->setSubject($model->getSubject());
        $message->setBody($model->getBody(), 'text/plain');

        if (!$this->mailer->send($message)) {
            throw new \Swift_SwiftException('An email was not delivered.');
        }

        $origin = $this->em
            ->getRepository('OroEmailBundle:InternalEmailOrigin')
            ->findOneBy(array('name' => $originName));
        $this->emailEntityBuilder->setOrigin($origin);
        $email = $this->emailEntityBuilder->email(
            $model->getSubject(),
            $model->getFrom(),
            $model->getTo(),
            $messageDate,
            $messageDate,
            $messageDate
        );
        $email->setFolder($origin->getFolder(EmailFolder::SENT));
        $emailBody = $this->emailEntityBuilder->body($model->getBody(), false, true);
        $email->setEmailBody($emailBody);
        $this->emailEntityBuilder->getBatch()->persist($this->em);
        $this->em->flush();
    }

    /**
     * @param Email $model
     * @throws \InvalidArgumentException
     */
    protected function assertModel(Email $model)
    {
        if (!$model->getFrom()) {
            throw new \InvalidArgumentException('Sender can not be empty');
        }
        if (!$model->getTo()) {
            throw new \InvalidArgumentException('Recipient can not be empty');
        }
    }

    /**
     * Converts emails addresses to a form acceptable to \Swift_Mime_Message class
     *
     * @param string|string[] $addresses Examples of correct email addresses: john@example.com, <john@example.com>,
     *                                   John Smith <john@example.com> or "John Smith" <john@example.com>
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function getAddresses($addresses)
    {
        $result = array();

        if (is_string($addresses)) {
            $addresses = array($addresses);
        }
        if (!is_array($addresses) && !$addresses instanceof \Iterator) {
            throw new \InvalidArgumentException(
                'The $addresses argument must be a string or a list of strings (array or Iterator)'
            );
        }

        foreach ($addresses as $address) {
            $name = EmailUtil::extractEmailAddressName($address);
            if (empty($name)) {
                $result[] = EmailUtil::extractPureEmailAddress($address);
            } else {
                $result[EmailUtil::extractPureEmailAddress($address)] = $name;
            }
        }

        return $result;
    }
}
