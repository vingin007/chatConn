<?php
// src/Controller/MailerController.php
namespace App\Controller;

use App\Service\MailService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[Controller]
class MailerController extends AbstractController
{
    #[Inject]
    private MailerInterface $mailer;
    #[RequestMapping(path: 'email', methods: 'post')]
    public function sendEmail(MailerInterface $mailer)
    {
        $mailService = new MailService($this->mailer);
        $mailService->sendVerificationEmail('2681977867@qq.com', '123456');

    }
}