<?php
/**
 * Diglin GmbH - Switzerland.
 *
 * @author      Louis Bataillard <support at diglin.com>
 * @category    SmsCampaignBundle
 * @copyright   2020 - Diglin (https://www.diglin.com)
 */

namespace Diglin\Bundle\SmsCampaignBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TransportPass implements CompilerPassInterface
{
    const TAG     = 'diglin_sms_campaign.sms_transport';
    const SERVICE = 'diglin_sms_campaign.sms_transport.provider';

    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::SERVICE)) {
            return;
        }

        $contentProviderManagerDefinition = $container->getDefinition(self::SERVICE);
        $taggedServices = $container->findTaggedServiceIds(self::TAG);
        foreach (array_keys($taggedServices) as $id) {
            $contentProviderManagerDefinition->addMethodCall(
                'addTransport',
                [new Reference($id)]
            );
        }
    }
}
