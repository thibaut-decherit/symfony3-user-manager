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
     * Email sent after user registration.
     *
     * @param User $user
     * @param string $activationUrl
     * @throws \Twig\Error\Error
     */
    public function registrationSuccess(User $user, string $activationUrl)
    {
        $emailBody = $this->container->get('templating')->render(
            'email/registration-email.html.twig', [
                'user' => $user,
                'activationUrl' => $activationUrl
            ]
        );

        $this->sendEmail(
            'Welcome',
            [$this->autoMailerAddress => 'UserManager'],
            $user->getEmail(),
            $this->replyTo,
            $emailBody
        );
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
