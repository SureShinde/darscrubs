<?php

namespace Amasty\Shopby\Plugin\Catalog\Model\Category\DataProvider;

use Magento\Catalog\Model\Category\DataProvider as NativeDataProvider;
use Magento\Ui\DataProvider\Modifier\PoolInterface;

class ModifyDataProvider
{
    /**
     * @var PoolInterface
     */
    private $pool;

    public function __construct(
        PoolInterface $pool
    ) {
        $this->pool = $pool;
    }

    /**
     * @param NativeDataProvider $subject
     * @param array $data
     *
     * @return array
     */
    public function afterGetData(NativeDataProvider $subject, $data)
    {
        foreach ($this->pool->getModifiersInstances() as $modifier) {
            $data = $modifier->modifyData($data);
        }

        return $data;
    }

    /**
     * @param NativeDataProvider $subject
     * @param array $data
     *
     * @return array
     */
    public function afterGetMeta(NativeDataProvider $subject, $data)
    {
        if (!isset($meta['display_settings']['children']['content']['arguments']['data']['config']['notice'])) {
            $category = $subject->getCurrentCategory();
            foreach ($this->pool->getModifiersInstances() as $modifier) {
                $modifier->setCategory($category);
                $data = $modifier->modifyMeta($data);
            }
        }

        return $data;
    }
}
