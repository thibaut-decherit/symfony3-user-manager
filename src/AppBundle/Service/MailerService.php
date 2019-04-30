<?php

namespace AppBundle\Service;

use AppBundle\Entity\User;
use Symfony\Component\Templating\EngineInterface;
use Swift_Mailer;

/**
 * Class MailerService
 * @package AppBundle\Service
 */
class MailerService
{
    /**
     * @var string
     */
    private $autoMailerAddress;

    /**
     * @var string
     */
    private $replyTo;

    /**
     * @var EngineInterface
     */
    private $twigEngine;

    /**
     * @var Swift_Mailer
     */
    private $swiftMailer;

    /**
     * MailerService constructor.
     * @param string $autoMailerAddress
     * @param string $replyTo
     * @param EngineInterface $twigEngine
     * @param Swift_Mailer $swiftMailer
     */
    public function __construct(
        string $autoMailerAddress,
        string $replyTo,
        EngineInterface $twigEngine,
        Swift_Mailer $swiftMailer
    )
    {
        $this->twigEngine = $twigEngine;
        $this->swiftMailer = $swiftMailer;
        $this->autoMailerAddress = $autoMailerAddress;
        $this->replyTo = $replyTo;
    }

    /**
     * Email sent when user requests password reset.
     *
     * @param User $user
     * @param string $passwordResetUrl
     * @param int $passwordResetTokenLifetime
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
     * Email sent after user registration.
     *
     * @param User $user
     * @param string $activationUrl
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
     * @param $subject
     * @param $from
     * @param $to
     * @param $replyTo
     * @param $body
     * @param null $attachment
     */
    private function sendEmail($subject, $from, $to, $replyTo, $body, $attachment = null)
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
