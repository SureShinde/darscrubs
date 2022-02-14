<?php

declare(strict_types=1);

namespace Amasty\ShopbyBrand\Ui\Component\Listing\Columns;

use Amasty\ShopbyBase\Helper\FilterSetting;
use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;

class BrandAttribute extends \Magento\Ui\Component\Listing\Columns\Column
{
    const ORIG_FILTER_CODE = 'orig_filter_code';

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        AttributeRepositoryInterface $attributeRepository,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->urlBuilder = $urlBuilder;
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        foreach (($dataSource['data']['items'] ?? []) as $key => $item) {
            $dataSource['data']['items'][$key][self::ORIG_FILTER_CODE] = $item[$this->getData('name')];
            $attributeCode = str_replace(FilterSetting::ATTR_PREFIX, '', $item[$this->getData('name')]);
            try {
                $attribute = $this->attributeRepository->get(Product::ENTITY, $attributeCode);
                $viewLink = $this->urlBuilder->getUrl(
                    'catalog/product_attribute/edit',
                    ['attribute_id' => $attribute->getAttributeId()]
                );

                $dataSource['data']['items'][$key][$this->getData('name')] = sprintf(
                    '<a href="%s" target="_blank">%s</a>',
                    $viewLink,
                    $attributeCode
                );
            } catch (\Exception $ex) {
                $dataSource['data']['items'][$key][$this->getData('name')] = $attributeCode;
            }
        }

        return $dataSource;
    }
}
