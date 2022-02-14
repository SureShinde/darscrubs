<?php

namespace Amasty\ShopbySeo\Plugin\Catalog\Controller\Adminhtml\Product\Attribute\Validate;

use Amasty\Base\Model\Serializer;
use Amasty\ShopbyBase\Api\Data\FilterSettingInterface;
use Amasty\ShopbyBase\Helper\FilterSetting;
use Amasty\ShopbyBase\Model\ResourceModel\FilterSetting\CollectionFactory as SettingCollectionFactory;
use Amasty\ShopbySeo\Helper\Config as SeoConfig;
use Amasty\ShopbySeo\Model\Source\GenerateSeoUrl;
use Magento\Catalog\Controller\Adminhtml\Product\Attribute\Validate;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection as AttributeCollection;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\DataObject;

class ValidateAttributeAlias
{
    /**
     * @var SettingCollectionFactory
     */
    private $settingCollectionFactory;

    /**
     * @var SeoConfig
     */
    private $seoConfig;

    /**
     * @var AttributeCollectionFactory
     */
    private $attributeCollectionFactory;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    public function __construct(
        SettingCollectionFactory $settingCollectionFactory,
        SeoConfig $seoConfig,
        AttributeCollectionFactory $attributeCollectionFactory,
        Serializer $serializer,
        JsonFactory $resultJsonFactory
    ) {
        $this->settingCollectionFactory = $settingCollectionFactory;
        $this->seoConfig = $seoConfig;
        $this->attributeCollectionFactory = $attributeCollectionFactory;
        $this->serializer = $serializer;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    public function afterExecute(Validate $subject, $result)
    {
        $aliases = array_filter($subject->getRequest()->getParam('attribute_url_alias', []));
        if (!$aliases) {
            return $result;
        }
        $attributeId = $subject->getRequest()->getParam('attribute_id');
        $attributeCode = $subject->getRequest()->getParam('attribute_code');
        if ($attributeId) {
            $attribute = $this->attributeCollectionFactory->create()->getItemById($attributeId);
            $attributeCode = $attribute->getAttributeCode();
        }

        $duplicatesAttribute = $this->checkUniqueAttribute($attributeId, $aliases);
        $duplicatesAttributeAlias = $this->checkUniqueAttributeAlias($attributeCode, $aliases);

        if ($duplicatesAttribute || $duplicatesAttributeAlias) {
            /** @var DataObject $response */
            $response = new DataObject();
            $this->setMessageToResponse(
                $response,
                [__($this->getMessage($duplicatesAttribute, $duplicatesAttributeAlias))],
                $subject->getRequest()->getParam('message_key', 'message')
            );
            $response->setError(true);
            $result = $this->resultJsonFactory->create()->setJsonData($response->toJson());
        }

        return $result;
    }

    private function getMessage(array $duplicatesAttribute, array $duplicatesAttributeAlias): string
    {
        return sprintf(
            'The following value(s) you are trying to save in Attribute URL Alias configuration - (%s) -'
            . 'seem(s) to be already used in (%s) attribute(s). Please specify unique values in this setting.',
            implode(', ', array_merge($duplicatesAttribute, array_keys($duplicatesAttributeAlias))),
            implode(', ', array_merge($duplicatesAttribute, $duplicatesAttributeAlias))
        );
    }

    private function checkUniqueAttribute(int $attributeId, array $aliases): array
    {
        /** @var AttributeCollection $attributeCollection */
        $attributeCollection = $this->attributeCollectionFactory->create();
        $attributeCollection->addFieldToFilter('main_table.attribute_id', ['neq' => $attributeId]);
        $attributeCollection->addFieldToFilter('main_table.attribute_code', ['in' => $aliases]);

        return $attributeCollection->getColumnValues('attribute_code');
    }

    private function checkUniqueAttributeAlias(string $attributeCode, array $aliases): array
    {
        $duplicates = [];
        $collection = $this->settingCollectionFactory->create();
        $yesValue = $this->seoConfig->isGenerateSeoByDefault()
            ? [GenerateSeoUrl::YES, GenerateSeoUrl::USE_DEFAULT]
            : [GenerateSeoUrl::YES];
        $collection->addFieldToFilter(FilterSettingInterface::IS_SEO_SIGNIFICANT, $yesValue);
        $collection->addFieldToFilter(
            FilterSettingInterface::FILTER_CODE,
            ['neq' => FilterSetting::ATTR_PREFIX . $attributeCode]
        );
        foreach ($collection as $item) {
            $storeAliases = $this->serializer->unserialize($item->getData('attribute_url_alias'));
            if ($storeAliases) {
                foreach ($storeAliases as $store => $alias) {
                    if (isset($aliases[$store]) && $aliases[$store] == $alias) {
                        $code = $item->getData('filter_code');
                        if (substr($code, 0, 5) == \Amasty\ShopbyBase\Helper\FilterSetting::ATTR_PREFIX) {
                            $code = substr($code, 5);
                        }
                        $duplicates[$alias] = $code;
                    }
                }
            }
        }

        return $duplicates;
    }

    private function setMessageToResponse(DataObject $response, array $messages, string $messageKey): DataObject
    {
        if ($messageKey === 'message') {
            $messages = reset($messages);
        }

        return $response->setData($messageKey, $messages);
    }
}
