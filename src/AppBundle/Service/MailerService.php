<?php

namespace AppBundle\Service;

use AppBundle\Entity\User;
use Symfony\Bundle\TwigBundle\TwigEngine;

/**
 * Class MailerService
 * @package AppBundle\Service
 */
class MailerService
{
    /**
     * @var TwigEngine
     */
    protected $twigEngine;

    /**
     * @var \Swift_Mailer
     */
    protected $swiftMailer;

    /**
     * @var string
     */
    protected $autoMailerAddress;

    /**
     * @var string
     */
    protected $replyTo;

    /**
     * MailerService constructor.
     * @param TwigEngine $twigEngine
     * @param \Swift_Mailer $swiftMailer
     * @param string $autoMailerAddress
     * @param string $replyTo
     */
    public function __construct(
        TwigEngine $twigEngine,
        \Swift_Mailer $swiftMailer,
        string $autoMailerAddress,
        string $replyTo
    )
    {
        $this->twigEngine = $twigEngine;
        $this->swiftMailer = $swiftMailer;
        $this->autoMailerAddress = $autoMailerAddress;
        $this->replyTo = $replyTo;
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
        $emailBody = $this->twigEngine->render(
            'Email/registration-email.html.twig', [
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
     * Email sent when user requests password reset.
     *
     * @param User $user
     * @param string $passwordResetUrl
     * @param int $passwordResetTokenLifetime
     * @throws \Twig\Error\Error
     */
    public function passwordReset(User $user, string $passwordResetUrl, int $passwordResetTokenLifetime)
    {
        $passwordResetTokenLifetimeInMinutes = ceil($passwordResetTokenLifetime / 60);

        $emailBody = $this->twigEngine->render(
            'Email/password-reset-email.html.twig', [
                'user' => $user,
                'passwordResetUrl' => $passwordResetUrl,
                'passwordResetTokenLifetimeInMinutes' => $passwordResetTokenLifetimeInMinutes
            ]
        );

        $this->sendEmail(
            'Password Reset',
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

        $this->swiftMailer->send($message);
    }
}
