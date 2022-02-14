<?php

declare(strict_types=1);

namespace Amasty\GroupedOptions\Model\ResourceModel;

use Amasty\GroupedOptions\Api\GroupRepositoryInterface;
use Amasty\GroupedOptions\Model\GroupAttrOptionFactory;
use Amasty\GroupedOptions\Model\GroupAttrValueFactory;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class GroupAttr extends AbstractDb
{
    /**
     * @var GroupAttrOptionFactory
     */
    protected $option;

    /**
     * @var GroupAttrValueFactory
     */
    protected $value;

    /**
     * @var array
     */
    protected $relatedArray = ['option', 'value'];

    /**
     * @var \Magento\Framework\Indexer\IndexerRegistry
     */
    private $indexerRegistry;

    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        GroupAttrOptionFactory $option,
        GroupAttrValueFactory $value,
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->option = $option;
        $this->value = $value;
        $this->indexerRegistry = $indexerRegistry;
    }

    protected function _construct()
    {
        $this->_init(GroupRepositoryInterface::TABLE, 'group_id');
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $object
     */
    protected function _afterSave(\Magento\Framework\Model\AbstractModel $object)
    {
        if ($object->getId()) {
            foreach ($this->relatedArray as $relate) {
                if ($object->getData('attribute_' . $relate . 's')) {
                    $this->{'saveTo' . ucfirst($relate)}($object);
                }
            }
        }

        $this->invalidateEavIndex();
        return parent::_afterSave($object);
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $object
     */
    protected function _afterLoad(\Magento\Framework\Model\AbstractModel $object)
    {
        if ($object->getId()) {
            $object->setData('attribute_options', []);
            $object->setData('attribute_values', []);
            foreach ([$this->option, $this->value] as $factory) {
                $collection = $factory->create()->getCollection()->addFieldToFilter('group_id', $object->getId());
                if ($collection->getSize()) {
                    $data = [];
                    foreach ($collection as $value) {
                        if ($factory instanceof GroupAttrOptionFactory) {
                            $data[] = $value->getOptionId();
                        } elseif ($factory instanceof GroupAttrValueFactory) {
                            $data[] = $value->getValue();
                        }
                    }
                    if ($factory instanceof GroupAttrOptionFactory) {
                        $object->setData('attribute_options', $data);
                    } elseif ($factory instanceof GroupAttrValueFactory) {
                        $object->setData('attribute_values', $data);
                    }
                }
            }
        }

        return parent::_afterLoad($object);
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this
     */
    protected function _afterDelete(\Magento\Framework\Model\AbstractModel $object)
    {
        $this->invalidateEavIndex();
        return parent::_afterDelete($object);
    }

    /**
     * @return $this
     */
    private function invalidateEavIndex()
    {
        $indexer = $this->indexerRegistry->get(\Magento\Catalog\Model\Indexer\Product\Eav\Processor::INDEXER_ID);
        $indexer->invalidate();
        return $this;
    }

    /**
     * @param $method
     * @param $value
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __call($method, $value)
    {
        if (strpos($method, 'clear') === 0) {
            $variable = lcfirst(substr($method, 5));
            if (property_exists(self::class, $variable)) {
                $collection = $this->$variable->create()->getCollection()->addFieldToFilter('group_id', $value[0]);
                foreach ($collection as $item) {
                    $item->delete();
                }
            }

            return true;
        }
        if (strpos($method, 'saveTo') === 0) {
            $variable = lcfirst(substr($method, 6));
            if (property_exists(self::class, $variable)) {
                foreach ($this->relatedArray as $relate) {
                    $this->{'clear' . $relate}($value[0]->getId());
                }
                $data = $value[0]->getData('attribute_' . $variable . 's');
                if ($this->$variable instanceof GroupAttrValueFactory) {
                    $data += [0]; // add empty value if array starts with 1 index; case when from price not set;
                    ksort($data);
                }

                foreach ($data as $item) {
                    $newOptions = [
                        'group_id' => $value[0]->getId()
                    ];
                    if ($this->$variable instanceof GroupAttrOptionFactory) {
                        $newOptions['option_id'] = $item;
                    }
                    if ($this->$variable instanceof GroupAttrValueFactory) {
                        $newOptions['value'] = $item;
                    }

                    $option = $this->$variable->create();
                    $option->setData($newOptions);
                    $option->save();
                }
            }

            return true;
        }

        throw new \Magento\Framework\Exception\LocalizedException(
            __("Invalid method %1::%2", get_class($this), $method)
        );
    }

    /**
     * @param $item
     * @return array
     */
    public function getOptions($item)
    {
        $object = null;
        foreach ($this->relatedArray as $record) {
            if ($this->isOptions((int)$item['group_id'], $record)) {
                $object = $record;
                break;
            }
        }

        if ($object) {
            $select = $this->getConnection()->select()->from(
                ['op' => $this->getTable('amasty_grouped_options_group_' . $object)],
                ['code' => new \Zend_Db_Expr(sprintf('%s', $item['attribute_id'])), 'sort_order']
            )->where(
                'group_id = :group_id'
            );
            $bind = ['group_id' => (int)$item['group_id']];

            if ($object == 'option') {
                $select->columns(['id' => 'option_id']);
                $select->joinLeft(
                    ['eaov' => $this->getTable('eav_attribute_option_value')],
                    'eaov.option_id=op.option_id',
                    ['value']
                )->joinLeft(
                    ['eaos' => $this->getTable('eav_attribute_option_swatch')],
                    'eaos.option_id=op.option_id and eaos.type <> 0',
                    ['swatch' => 'value', 'type']
                );
            } else {
                $select->columns([
                    'value',
                    'type' => new \Zend_Db_Expr('0'),
                    'id' => 'group_option_id',
                    'swatch' => new \Zend_Db_Expr('0')
                ]);
            }

            $select->group('op.group_option_id');

            return [$this->getConnection()->fetchAll($select, $bind), $object];
        }

        return [[], null];
    }

    /**
     * @param $groupId
     * @param string $part
     * @return string
     */
    private function isOptions($groupId, $part = 'option')
    {
        $select = $this->getConnection()->select()->from(
            ['op' => $this->getTable('amasty_grouped_options_group_' . $part)],
            ['count' => new \Zend_Db_Expr('COUNT(group_id)')]
        )->where(
            'group_id = :group_id'
        );
        $bind = ['group_id' => $groupId];

        return $this->getConnection()->fetchOne($select, $bind);
    }
}
