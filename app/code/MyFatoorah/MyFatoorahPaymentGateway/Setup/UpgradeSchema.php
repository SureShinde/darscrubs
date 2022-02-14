<?php

namespace MyFatoorah\MyFatoorahPaymentGateway\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Ddl\Table;

class UpgradeSchema implements UpgradeSchemaInterface {

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context) {
        $setup->startSetup();
        $conn      = $setup->getConnection();
        $tableName = $setup->getTable('myfatoorah_invoice');

        if ($conn->isTableExists($tableName) != true) {
            //for ver 3.0.0 in etc/module.xml to be ver 3.0.1 or ver 3.0.2
            $table = $conn->newTable($tableName)
                    ->addColumn(
                            'id',
                            Table::TYPE_INTEGER,
                            null,
                            ['primary' => true, 'identity' => true, 'unsigned' => true, 'nullable' => false]
                    )
                    ->addColumn(
                            'order_id',
                            Table::TYPE_INTEGER,
                            null,
                            ['unsigned' => true, 'nullable' => false]
                    )
                    ->addColumn(
                            'invoice_id',
                            Table::TYPE_TEXT,
                            25,
                            ['nullable' => true]
                    )
                    ->addColumn(
                            'invoice_url',
                            Table::TYPE_TEXT,
                            255,
                            ['nullable' => true],
                            'The Invoice or Payment URL'
                    )
                    ->addColumn(
                            'gateway_id',
                            Table::TYPE_TEXT,
                            10,
                            ['nullable' => false, 'default' => 'myfatoorah'],
                            'The used Payment Gateway'
                    )
                    ->addColumn(
                            'gateway_transaction_id',
                            Table::TYPE_TEXT,
                            25,
                            ['nullable' => true],
                            'The used Payment Gateway Transaction ID'
                    )
                    ->setOption('charset', 'utf8');
            $conn->createTable($table);
        } else {
            //for ver 3.0.1 in etc/module.xml to be ver 3.0.2
            $conn->addColumn($tableName, 'invoice_url', [
                'type'     => Table::TYPE_TEXT,
                'length'   => 255,
                'nullable' => true,
                'comment'  => 'The Invoice/Payment URL',
            ]);
            $conn->addColumn($tableName, 'gateway_id', [
                'type'     => Table::TYPE_TEXT,
                'length'   => 10,
                'nullable' => false,
                'default'  => 'myfatoorah',
                'comment'  => 'The used Payment Gateway',
            ]);
            $conn->addColumn($tableName, 'gateway_transaction_id', [
                'type'     => Table::TYPE_TEXT,
                'length'   => 25,
                'nullable' => true,
                'comment'  => 'The used Payment Gateway Transaction ID',
            ]);
        }
        $setup->endSetup();
    }

}
