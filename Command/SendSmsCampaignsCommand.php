<?php
/**
 * Diglin GmbH - Switzerland.
 *
 * @author      Louis Bataillard <support at diglin.com>
 * @category    SmsCampaignBundle
 * @copyright   2020 - Diglin (https://www.diglin.com)
 */

namespace Diglin\Bundle\SmsCampaignBundle\Command;

use Diglin\Bundle\SmsCampaignBundle\Entity\Repository\SmsCampaignRepository;
use Diglin\Bundle\SmsCampaignBundle\Entity\SmsCampaign;
use Diglin\Bundle\SmsCampaignBundle\Model\SmsCampaignSenderBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to send scheduled SMS campaigns
 */
#[\Symfony\Component\Console\Attribute\AsCommand('oro:cron:send-sms-campaigns', 'Send SMS campaigns')]
class SendSmsCampaignsCommand extends Command implements CronCommandScheduleDefinitionInterface
{
    /** @var ManagerRegistry */
    private $registry;

    /** @var FeatureChecker */
    private $featureChecker;

    /** @var SmsCampaignSenderBuilder */
    private $smsCampaignSenderBuilder;

    /**
     * @param ManagerRegistry          $registry
     * @param FeatureChecker           $featureChecker
     * @param SmsCampaignSenderBuilder $smsCampaignSenderBuilder
     */
    public function __construct(
        ManagerRegistry $registry,
        FeatureChecker $featureChecker,
        SmsCampaignSenderBuilder $smsCampaignSenderBuilder
    ) {
        parent::__construct();

        $this->registry = $registry;
        $this->featureChecker = $featureChecker;
        $this->smsCampaignSenderBuilder = $smsCampaignSenderBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultDefinition()
    {
        return '*/1 * * * *';
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        $count = $this->getSmsCampaignRepository()->countSmsCampaignsToSend();

        return ($count > 0);
    }

    /**
     * @return SmsCampaignRepository
     */
    protected function getSmsCampaignRepository()
    {
        return $this->registry->getRepository('SmsCampaignBundle:SmsCampaign');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->featureChecker->isFeatureEnabled('campaign')) {
            $output->writeln('The campaign feature is disabled. The command will not run.');

            return \Symfony\Component\Console\Command\Command::SUCCESS;
        }

        $smsCampaigns = $this->getSmsCampaignRepository()->findSmsCampaignsToSend();

        if (!$smsCampaigns) {
            $output->writeln('<info>No SMS campaigns to send</info>');

            return \Symfony\Component\Console\Command\Command::SUCCESS;
        }

        $output->writeln(
            sprintf('<comment>SMS campaigns to send:</comment> %d', count($smsCampaigns))
        );

        $this->send($output, $smsCampaigns);
        $output->writeln(sprintf('<info>Finished SMS campaigns sending</info>'));

        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }

    /**
     * Send SMS campaigns
     *
     * @param OutputInterface $output
     * @param SmsCampaign[]   $smsCampaigns
     */
    protected function send($output, array $smsCampaigns)
    {
        foreach ($smsCampaigns as $smsCampaign) {
            $output->writeln(sprintf('<info>Sending SMS campaign</info>: %s', $smsCampaign->getName()));

            $sender = $this->smsCampaignSenderBuilder->getSender($smsCampaign);
            $sender->send();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
    }
}
