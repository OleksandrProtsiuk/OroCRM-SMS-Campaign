<?php
/**
 * Diglin GmbH - Switzerland.
 *
 * @author      Louis Bataillard <support at diglin.com>
 * @category    SmsCampaignBundle
 * @copyright   2020 - Diglin (https://www.diglin.com)
 */

namespace Diglin\Bundle\SmsCampaignBundle\Provider;

use Diglin\Bundle\SmsCampaignBundle\Transport\TransportInterface;
use Oro\Bundle\CampaignBundle\Transport\VisibilityTransportInterface;

class SmsTransportProvider
{
    /**
     * @var array
     */
    protected $transports = [];

    public function addTransport(TransportInterface $transport)
    {
        $this->transports[$transport->getName()] = $transport;
    }

    public function getTransportByName(string $name): TransportInterface
    {
        if ($this->hasTransport($name)) {
            return $this->transports[$name];
        } else {
            throw new \RuntimeException(sprintf('Transport %s is unknown', $name));
        }
    }

    public function hasTransport(string $name): bool
    {
        return isset($this->transports[$name]);
    }

    public function getVisibleTransportChoices(): array
    {
        $choices = [];
        foreach ($this->getTransports() as $transport) {
            if ($this->isVisibleInForm($transport)) {
                $choices[$transport->getLabel()] = $transport->getName();
            }
        }

        return $choices;
    }

    /**
     * @return TransportInterface[]
     */
    public function getTransports(): array
    {
        return $this->transports;
    }

    protected function isVisibleInForm(TransportInterface $transport): bool
    {
        return !$transport instanceof VisibilityTransportInterface || $transport->isVisibleInForm();
    }
}
