<?php
/**
 * Diglin GmbH - Switzerland.
 *
 * @author      Louis Bataillard <support at diglin.com>
 * @category    SmsCampaignBundle
 * @copyright   2020 - Diglin (https://www.diglin.com)
 */

namespace Diglin\Bundle\SmsCampaignBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class SmsCampaignBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_0';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createDiglinCampaignSmsTable($schema);
        $this->updateOroCmpgnTransportStngsTable($schema);
        $this->createDiglinCampaignSmsStatsTable($schema);

        /** Foreign keys generation **/
        $this->addDiglinCampaignSmsForeignKeys($schema);
        $this->addOroCmpgnTransportStngsForeignKeys($schema);
        $this->addDiglinCampaignSmsStatsForeignKeys($schema);
    }

    /**
     * Create diglin_campaign_sms table
     *
     * @param Schema $schema
     */
    protected function createDiglinCampaignSmsTable(Schema $schema)
    {
        $table = $schema->createTable('diglin_campaign_sms');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('campaign_id', 'integer', ['notnull' => false]);
        $table->addColumn('marketing_list_id', 'integer', ['notnull' => false]);
        $table->addColumn('owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('transport_settings_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('text', 'text', ['notnull' => false, 'length' => 0]);
        $table->addColumn('is_sent', 'boolean', []);
        $table->addColumn('sent_at', 'datetime', ['notnull' => false, 'length' => 0, 'comment' => '(DC2Type:datetime)']);
        $table->addColumn('schedule', 'string', ['length' => 255]);
        $table->addColumn('scheduled_for', 'datetime', ['notnull' => false, 'length' => 0, 'comment' => '(DC2Type:datetime)']);
        $table->addColumn('transport', 'string', ['length' => 255]);
        $table->addColumn('created_at', 'datetime', ['length' => 0, 'comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['length' => 0, 'comment' => '(DC2Type:datetime)']);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['transport_settings_id'], 'UNIQ_20F6893ECFFA7B8F');
        $table->addIndex(['campaign_id'], 'IDX_20F6893EF639F774', []);
        $table->addIndex(['marketing_list_id'], 'IDX_20F6893E96434D04', []);
        $table->addIndex(['organization_id'], 'IDX_20F6893E32C8A3DE', []);
        $table->addIndex(['owner_id'], 'cmpgn_sms_owner_idx', []);
    }

    /**
     *
     * Update orocrm_cmpgn_transport_stngs table
     *
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function updateOroCmpgnTransportStngsTable(Schema $schema)
    {
        $table = $schema->getTable('orocrm_cmpgn_transport_stngs');
        $table->addColumn('sms_channel_id', 'integer', ['notnull' => false]);
    }

    /**
     * Create diglin_campaign_sms_stats table
     *
     * @param Schema $schema
     */
    protected function createDiglinCampaignSmsStatsTable(Schema $schema)
    {
        $table = $schema->createTable('diglin_campaign_sms_stats');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('marketing_list_item_id', 'integer', []);
        $table->addColumn('sms_campaign_id', 'integer', []);
        $table->addColumn('owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('bounce_count', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', ['length' => 0, 'comment' => '(DC2Type:datetime)']);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['sms_campaign_id', 'marketing_list_item_id'], 'diglin_sm_campaign_litem_unq');
        $table->addIndex(['marketing_list_item_id'], 'IDX_C4B93093D530662', []);
        $table->addIndex(['sms_campaign_id'], 'IDX_C4B930932FBADFEF', []);
        $table->addIndex(['owner_id'], 'IDX_C4B930937E3C61F9', []);
        $table->addIndex(['organization_id'], 'IDX_C4B9309332C8A3DE', []);
    }

    /**
     * Add diglin_campaign_sms foreign keys.
     *
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function addDiglinCampaignSmsForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('diglin_campaign_sms');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_marketing_list'),
            ['marketing_list_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_cmpgn_transport_stngs'),
            ['transport_settings_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_campaign'),
            ['campaign_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add orocrm_cmpgn_transport_stngs foreign keys.
     *
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function addOroCmpgnTransportStngsForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orocrm_cmpgn_transport_stngs');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_channel'),
            ['sms_channel_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    /**
     * Add diglin_campaign_sms_stats foreign keys.
     *
     * @param Schema $schema
     */
    protected function addDiglinCampaignSmsStatsForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('diglin_campaign_sms_stats');
        $table->addForeignKeyConstraint(
            $schema->getTable('diglin_campaign_sms'),
            ['sms_campaign_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_marketing_list_item'),
            ['marketing_list_item_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
