<?php
/**
 * Diglin GmbH - Switzerland.
 *
 * @author      Louis Bataillard <support at diglin.com>
 * @category    SmsCampaignBundle
 * @copyright   2020 - Diglin (https://www.diglin.com)
 */

namespace Diglin\Bundle\SmsCampaignBundle\Model;

use Diglin\Bundle\SmsCampaignBundle\Entity\SmsCampaign;
use Diglin\Bundle\SmsCampaignBundle\Provider\SmsTransportProvider;
use Diglin\Bundle\SmsCampaignBundle\Transport\TransportInterface;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\MarketingListBundle\Provider\ContactInformationFieldsProvider;
use Oro\Bundle\MarketingListBundle\Provider\MarketingListProvider;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Sends sms campaign for each entity from marketing list.
 */
class SmsCampaignSender
{
    /**
     * @var MarketingListProvider
     */
    protected $marketingListProvider;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var SmsCampaignStatisticsConnector
     */
    protected $statisticsConnector;

    /**
     * @var ContactInformationFieldsProvider
     */
    protected $contactInformationFieldsProvider;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var SmsTransportProvider
     */
    protected $smsTransportProvider;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var TransportInterface
     */
    protected $transport;

    /**
     * @var SmsCampaign
     */
    protected $smsCampaign;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @param MarketingListProvider            $marketingListProvider
     * @param ConfigManager                    $configManager
     * @param SmsCampaignStatisticsConnector   $statisticsConnector
     * @param ContactInformationFieldsProvider $contactInformationFieldsProvider
     * @param ManagerRegistry                  $registry
     * @param SmsTransportProvider             $smsTransportProvider
     */
    public function __construct(
        MarketingListProvider $marketingListProvider,
        ConfigManager $configManager,
        SmsCampaignStatisticsConnector $statisticsConnector,
        ContactInformationFieldsProvider $contactInformationFieldsProvider,
        ManagerRegistry $registry,
        SmsTransportProvider $smsTransportProvider
    ) {
        $this->marketingListProvider = $marketingListProvider;
        $this->configManager = $configManager;
        $this->statisticsConnector = $statisticsConnector;
        $this->contactInformationFieldsProvider = $contactInformationFieldsProvider;
        $this->registry = $registry;
        $this->SmsTransportProvider = $smsTransportProvider;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function setSmsCampaign(SmsCampaign $smsCampaign)
    {
        $this->smsCampaign = $smsCampaign;

        $this->transport = $this->SmsTransportProvider
            ->getTransportByName($smsCampaign->getTransport());
    }

    public function send()
    {
        if (!$this->assertTransport()) {
            return;
        }

        $marketingList = $this->smsCampaign->getMarketingList();
        if (is_null($marketingList)) {
            return;
        }

        $iterator = $this->getIterator();
        /** @var EntityManager $manager */
        $manager = $this->registry->getManager();
        $smsFields = $this->contactInformationFieldsProvider
            ->getMarketingListTypedFields(
                $marketingList,
                ContactInformationFieldsProvider::CONTACT_INFORMATION_SCOPE_PHONE
            );

        foreach ($iterator as $item) {
            $entity = array_shift($item);

            $toFromFields = $this->contactInformationFieldsProvider->getTypedFieldsValues($smsFields, $item);

            try {
                $toFromEntity = $this->contactInformationFieldsProvider->getTypedFieldsValues($smsFields, $entity);
            } catch (NoSuchPropertyException $e) {
                $toFromEntity = [];
            }
            $to = array_filter(array_unique(array_merge($toFromFields, $toFromEntity)));

            try {
                $manager->beginTransaction();

                // Do actual send
                $this->transport->send(
                    $this->smsCampaign,
                    $entity,
                    $to
                );

                $statisticsRecord = $this->statisticsConnector->getStatisticsRecord($this->smsCampaign, $entity);

                // Mark marketing list item as contacted
                $statisticsRecord->getMarketingListItem()->contact();

                $manager->flush($statisticsRecord);
                $manager->commit();
            } catch (\Exception $e) {
                $manager->rollback();

                if ($this->logger) {
                    $this->logger->error(
                        sprintf('SMS sending to "%s" failed.', implode(', ', $to)),
                        ['exception' => $e]
                    );
                }
            }
        }

        $this->smsCampaign->setSent(true);
        $manager->persist($this->smsCampaign);
        $manager->flush();
    }

    /**
     * Assert that transport is present.
     *
     * @return bool
     * @throws \RuntimeException
     */
    protected function assertTransport()
    {
        if (!$this->transport) {
            throw new \RuntimeException('Transport is required to perform send');
        }

        $transportSettings = $this->smsCampaign->getTransportSettings();
        if ($transportSettings) {
            $errors = $this->validator->validate($transportSettings);

            if (count($errors) > 0) {
                $this->logger->error('SMS sending failed. Transport settings are not valid.');

                return false;
            }
        }

        return true;
    }

    protected function getIterator(): \Iterator
    {
        return $this->marketingListProvider->getEntitiesIterator(
            $this->smsCampaign->getMarketingList()
        );
    }

    public function setValidator(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }
}
