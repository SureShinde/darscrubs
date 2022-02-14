<?php

declare(strict_types=1);

namespace Amasty\Shopby\Plugin\Framework\Search\Adapter\Mysql\Aggregation\Builder\Dynamic;

use Amasty\Shopby\Model\ResourceModel\Search\Aggregation\DataProvider as SearchDataProvider;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\App\ScopeResolverInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Search\Dynamic\EntityStorageFactory;
use Magento\Framework\Search\Request\BucketInterface;
use Magento\Framework\Search\Adapter\Mysql\Aggregation\DataProviderInterface as MysqlDataProviderInterface;
use Magento\Framework\Search\Adapter\Mysql\Aggregation\Builder\Dynamic as OriginDynamic;
use Magento\Framework\Search\Dynamic\DataProviderInterface as PriceDataProvider;
use Magento\Eav\Model\Config;

class BuildDynamicAggregations
{
    /**
     * @var PriceDataProvider
     */
    private $priceDataProvider;

    /**
     * @var ScopeResolverInterface
     */
    protected $scopeResolver;

    /**
     * @var Config
     */
    protected $eavConfig;

    /**
     * @var EntityStorageFactory
     */
    protected $entityStorageFactory;

    /**
     * @var SearchDataProvider
     */
    private $searchDataProvider;

    /**
     * @var array
     */
    private $data = [];

    public function __construct(
        ScopeResolverInterface $scopeResolver,
        Config $eavConfig,
        PriceDataProvider $priceDataProvider,
        EntityStorageFactory $entityStorageFactory,
        SearchDataProvider $searchDataProvider
    ) {
        $this->scopeResolver = $scopeResolver;
        $this->eavConfig = $eavConfig;
        $this->priceDataProvider = $priceDataProvider;
        $this->entityStorageFactory = $entityStorageFactory;
        $this->searchDataProvider = $searchDataProvider;
    }

    /**
     * @param OriginDynamic $subject
     * @param \Closure $closure
     * @param MysqlDataProviderInterface $dataProvider
     * @param array $dimensions
     * @param BucketInterface $bucket
     * @param Table $entityIdsTable
     * @return array|mixed
     * @SuppressWarnings(PHPMD.UnusedFormatParameter)
     */
    public function aroundBuild(
        OriginDynamic $subject,
        \Closure $closure,
        MysqlDataProviderInterface $dataProvider,
        array $dimensions,
        BucketInterface $bucket,
        Table $entityIdsTable
    ) {
        $dataKey = $bucket->getName() . $bucket->getField() . $bucket->getType();
        if (!isset($this->data[$dataKey])) {
            $attribute = $this->getAttribute($bucket->getField());

            if ($attribute->getBackendType() == 'decimal') {
                if ($attribute->getAttributeCode() == 'price') {
                    $minMaxData['data'] = $this->priceDataProvider->getAggregations(
                        $this->entityStorageFactory->create($entityIdsTable)
                    );
                    $minMaxData['data']['value'] = 'data';
                } else {
                    $currentScope = isset($dimensions['scope']) ? $dimensions['scope']->getValue() : null;

                    $select = $this->searchDataProvider->getMinMaxSelect(
                        (int) $attribute->getAttributeId(),
                        $entityIdsTable->getName(),
                        (int) $this->scopeResolver->getScope($currentScope)->getId()
                    );
                    $minMaxData = $dataProvider->execute($select);
                }

                $defaultData = $closure($dataProvider, $dimensions, $bucket, $entityIdsTable);

                return array_replace($minMaxData, $defaultData);
            }

            $this->data[$dataKey] = $closure($dataProvider, $dimensions, $bucket, $entityIdsTable);
        }

        return $this->data[$dataKey];
    }

    private function getAttribute(string $attributeCode): AbstractAttribute
    {
        return $this->eavConfig->getAttribute(Product::ENTITY, $attributeCode);
    }
}
