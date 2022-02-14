<?php

declare(strict_types=1);

namespace Amasty\GroupedOptions\Ui\Component\Listing\Column\Group;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class Options extends Column
{
    /**
     * @var \Amasty\GroupedOptions\Model\GroupAttrFactory
     */
    private $groupAttrFactory;

    /**
     * @var \Magento\Framework\Json\Encoder
     */
    private $encoder;

    /**
     * @var \Magento\Swatches\Helper\Media
     */
    private $swatchHelper;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Amasty\GroupedOptions\Model\GroupAttrFactory $groupAttrFactory,
        \Magento\Framework\Json\Encoder $encoder,
        \Magento\Swatches\Helper\Media $swatchHelper,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->groupAttrFactory = $groupAttrFactory;
        $this->encoder = $encoder;
        $this->swatchHelper = $swatchHelper;
    }

    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $item[$this->getData('name')] = $this->prepareItem($item);
            }
        }

        return $dataSource;
    }

    /**
     * Get data
     *
     * @param array $item
     * @return string
     */
    protected function prepareItem(array $item)
    {
        list($items, $object) = $this->groupAttrFactory->create()->getResource()->getOptions($item);
        $array = ['items' => $items, 'code' => $item['attribute_id'], 'type' => $object];
        return $this->encoder->encode($array);
    }
}
