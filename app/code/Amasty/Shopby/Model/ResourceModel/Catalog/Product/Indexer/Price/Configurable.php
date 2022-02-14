<?php

declare(strict_types=1);

namespace Amasty\Shopby\Model\ResourceModel\Catalog\Product\Indexer\Price;

use Magento\Framework\App\ResourceConnection;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;

class Configurable
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var string
     */
    private $productIdLink;

    public function __construct(ResourceConnection $resourceConnection, ProductResource $productResource)
    {
        $this->resourceConnection = $resourceConnection;
        $this->productIdLink = $productResource->getLinkField();
    }

    /**
     * * Method adds modifications to catalog_price_index, related to special prices of composite products
     *
     * @param string $tableName
     * @param string $indexTableName
     * @param array $entityIds
     */
    public function addSpecialPrice(string $tableName, string $indexTableName, array $entityIds): void
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()->from(['main_table' => $tableName]);

        $select->joinInner(
            ['simple_link' => $this->resourceConnection->getTableName('catalog_product_super_link')],
            'simple_link.product_id=main_table.entity_id',
            []
        );
        if ($this->productIdLink == 'row_id') {
            $select->joinInner(
                ['product_link' => $this->resourceConnection->getTableName('catalog_product_entity')],
                'simple_link.parent_id=product_link.row_id',
                ['parent_id' => 'product_link.entity_id']
            );
            $select->where('product_link.entity_id IN (?)', $entityIds);
        } else {
            $select->columns(['parent_id' => 'simple_link.parent_id']);
            $select->where('simple_link.parent_id IN (?)', $entityIds);
        }

        $select->where('main_table.price > main_table.final_price and main_table.final_price > 0');

        $select->group(['simple_link.parent_id', 'main_table.customer_group_id', 'main_table.website_id']);

        $insertData = $connection->fetchAll($select);

        if (!empty($insertData)) {
            foreach ($insertData as &$row) {
                if (isset($row['parent_id'])) {
                    $row['entity_id'] = $row['parent_id'];
                    unset($row['parent_id']);
                }
            }

            $connection->insertOnDuplicate(
                $indexTableName,
                $insertData,
                ['price', 'final_price']
            );
        }
    }
}
