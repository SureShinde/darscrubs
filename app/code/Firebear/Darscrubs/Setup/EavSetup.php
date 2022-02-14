<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\Darscrubs\Setup;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Model\Entity\Setup\Context;
use Magento\Eav\Model\Entity\Setup\PropertyMapperInterface;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory;

/**
 * Eav Setup
 */
class EavSetup extends \Firebear\ImportExport\Setup\EavSetup
{
    /**
     * Attribute mapper
     *
     * @var PropertyMapperInterface
     */
    private $attributeMapper;

    /**
     * Setup model
     *
     * @var ModuleDataSetupInterface
     */
    private $setup;

    /**
     * General Attribute Group Name
     *
     * @var string
     */
    private $_generalGroupName = 'General';

    /**
     * @var array
     */
    private $storeLabelsCache = [];

    /**
     * Init
     *
     * @param ModuleDataSetupInterface $setup
     * @param Context $context
     * @param CacheInterface $cache
     * @param CollectionFactory $attrGroupCollectionFactory
     */
    public function __construct(
        ModuleDataSetupInterface $setup,
        Context $context,
        CacheInterface $cache,
        CollectionFactory $attrGroupCollectionFactory
    ) {
        $this->attributeMapper = $context->getAttributeMapper();
        $this->setup = $setup;

        parent::__construct(
            $setup,
            $context,
            $cache,
            $attrGroupCollectionFactory
        );
    }

    /**
     * Add Attribute Option
     *
     * @param array $optionData
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function addAttributeOption($optionData)
    {
        $optionTable = $this->setup->getTable('eav_attribute_option');
        $optionValueTable = $this->setup->getTable('eav_attribute_option_value');

        if (isset($optionData['value'])) {
            foreach ($optionData['value'] as $optionId => $values) {
                if ($optionId == 'new') {
                    $intOptionId = false;
                } else {
                    $intOptionId = (int) $optionId;
                }
                if (!empty($optionData['delete'][$optionId])) {
                    if ($intOptionId) {
                        $condition = ['option_id =?' => $intOptionId];
                        $this->setup->getConnection()->delete($optionTable, $condition);
                    }
                    continue;
                }

                if (!$intOptionId) {
                    if ($optionId == 'new') {
                        $sortOrder = 0;
                    } else {
                        $sortOrder = isset($optionData['order'][$optionId]) ? $optionData['order'][$optionId] : 0;
                    }
                    $data = [
                        'attribute_id' => $optionData['attribute_id'],
                        'sort_order' => $sortOrder,
                    ];
                    $this->setup->getConnection()->insert($optionTable, $data);
                    $intOptionId = $this->setup->getConnection()->lastInsertId($optionTable);
                } else {
                    $data = [
                        'sort_order' => isset($optionData['order'][$optionId]) ? $optionData['order'][$optionId] : 0,
                    ];
                    $this->setup->getConnection()->update($optionTable, $data, ['option_id=?' => $intOptionId]);
                }

                foreach ($values as $storeId => $value) {
                    $select = $this->setup->getConnection()->select();
                    $select->from($optionValueTable, 'value_id')
                        ->where('option_id = ?', $intOptionId)
                        ->where('store_id = ?', $storeId)
                        ->where('value = ?', $value);

                    $valueId = $this->setup->getConnection()->fetchOne($select);
                    if ($valueId) {
                        $data = ['value' => $value];
                        $this->setup->getConnection()->update($optionValueTable, $data, ['value_id=?' => $valueId]);
                    } else {
                        $data = ['option_id' => $intOptionId, 'store_id' => $storeId, 'value' => $value];
                        $this->setup->getConnection()->insert($optionValueTable, $data);
                    }
                    if (isset($optionData['swatch'])) {
                        $swatchOptionTable = $this->setup->getTable('eav_attribute_option_swatch');
                        $data = ['option_id' => $intOptionId, 'store_id' => $storeId, 'value' => $optionData['swatch'], 'type' => $optionData['swatch_type']];
                        $this->setup->getConnection()->insertOnDuplicate($swatchOptionTable, $data);
                    }
                }
            }
        } elseif (isset($optionData['values'])) {
            foreach ($optionData['values'] as $sortOrder => $label) {
                // add option
                $data = ['attribute_id' => $optionData['attribute_id'], 'sort_order' => $sortOrder];
                $this->setup->getConnection()->insert($optionTable, $data);
                $intOptionId = $this->setup->getConnection()->lastInsertId($optionTable);

                $data = ['option_id' => $intOptionId, 'store_id' => 0, 'value' => $label];
                $this->setup->getConnection()->insert($optionValueTable, $data);

                if (isset($optionData['swatch'])) {
                    $swatchOptionTable = $this->setup->getTable('eav_attribute_option_swatch');
                    /*customize*/
                    $data = ['option_id' => $intOptionId, 'store_id' => 0, 'value' => $optionData['swatch'], 'type' => $optionData['swatch_type']];
                    /*customize end*/
                    $this->setup->getConnection()->insertOnDuplicate($swatchOptionTable, $data);
                }
            }
        }
    }
}
