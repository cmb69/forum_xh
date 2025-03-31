<?php

namespace Forum\Infra;

class FakeMailer extends Mailer
{
    private $lastMail = [];

    public function lastMail(): array
    {
        return $this->lastMail;
    }

    protected function mail(string $to, string $subject, string $message, string $headers): bool
    {
        $this->lastMail = func_get_args();
        return true;
    }
}
