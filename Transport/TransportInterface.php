<?php

namespace Diglin\Bundle\SmsCampaignBundle\Transport;

use Diglin\Bundle\SmsCampaignBundle\Entity\SmsCampaign;

interface TransportInterface
{
    /**
     * @param SmsCampaign $campaign
     * @param string      $entity
     * @param string[]    $to
     *
     * @return mixed
     */
    public function send(SmsCampaign $campaign, string $entity, array $to);

    /**
     * Get transport name.
     */
    public function getName(): string;

    /**
     * Get label used for transport selection.
     */
    public function getLabel(): string;

    /**
     * Returns form type name needed to setup transport.
     */
    public function getSettingsFormType(): string;

    /**
     * Returns entity name needed to store transport settings.
     */
    public function getSettingsEntityFQCN(): string;
}
