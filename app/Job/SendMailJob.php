<?php

declare(strict_types=1);

namespace App\Job;

use App\Service\MailService;
use Hyperf\AsyncQueue\Job;

class SendMailJob extends Job
{
    public function __construct($params)
    {
        $this->params = $params;
    }

    public function handle()
    {
        $mailService = new MailService($this->mailer);
        $mailService->sendVerificationEmail($input, $code);
    }
}
