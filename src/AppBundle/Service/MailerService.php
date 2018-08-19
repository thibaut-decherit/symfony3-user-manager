<?php

namespace AppBundle\Service;

use AppBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MailerService
{
    protected $container;

    protected $autoMailerAddress;

    protected $replyTo;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->autoMailerAddress = $container->getParameter('mailer_user');
        $this->replyTo = $container->getParameter('mailer_reply_to');
    }

    /**
     * @param $subject
     * @param $from
     * @param $to
     * @param $replyTo
     * @param $body
     * @param null $attachment
     */
    protected function sendEmail($subject, $from, $to, $replyTo, $body, $attachment = null)
    {
        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom($from)
            ->setTo($to)
            ->setReplyTo($replyTo)
            ->setBody($body, 'text/html');
        if ($attachment) {
            $message->attach($attachment);
        }
        $this->container->get('mailer')->send($message);
    }
}
