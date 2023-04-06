<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use function Hyperf\ViewEngine\view;

class MailService
{
    protected $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function sendVerificationEmail(string $toEmail, string $verificationCode)
    {
        $subject = '验证码';
        $body = view('email_verification', ['code' => $verificationCode])->render();

        $email = (new Email())
            ->from('wxl199293@gmail.com')
            ->to($toEmail)
            ->subject($subject)
            ->text($body);

        $this->mailer->send($email);
    }
}
