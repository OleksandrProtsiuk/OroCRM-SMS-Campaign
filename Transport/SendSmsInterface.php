<?php

namespace Diglin\Bundle\SmsCampaignBundle\Transport;

interface SendSmsInterface
{
    public function sendSms(string $number, string $body): bool;
}