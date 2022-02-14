<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\Darscrubs\Model\Export;

use DateTime;
use Exception;
use Firebear\ImportExport\Model\Export\Product\Additional;
use Firebear\ImportExport\Model\Export\RowCustomizer\ProductVideo;
use Firebear\ImportExport\Model\ExportJob\Processor;
use IntlDateFormatter;
use Magento\Catalog\Model\Product\LinkTypeProvider;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\ProductFactory;
use Magento\CatalogImportExport\Model\Export\Product\Type\Factory;
use Magento\CatalogImportExport\Model\Export\RowCustomizerInterface;
use Magento\CatalogImportExport\Model\Import\Product as ImportProduct;
use Magento\CatalogInventory\Model\ResourceModel\Stock\ItemFactory;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\Collection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Module\Manager;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\ImportExport\Model\Export;
use Magento\ImportExport\Model\Export\ConfigInterface;
use Magento\ImportExport\Model\Import;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Swatches\Helper\Data;
use Magento\Swatches\Model\ResourceModel\Swatch\CollectionFactory as SwatchCollectionFactory;
use Magestore\InventorySuccess\Api\Warehouse\WarehouseStockRegistryInterface;
use Psr\Log\LoggerInterface;
use Zend_Db_Statement_Exception;
use Firebear\ImportExport\Model\Export\EntityInterface;

use function array_keys;
use function array_merge;

/**
 * Class Product
 *
 * @package Firebear\ImportExport\Model\Export
 */
class Product extends \Firebear\ImportExport\Model\Export\Product
{
    const CACHE_TAG = 'config_scopes';
    const COL_CATEGORY_IDS = 'category_ids';

    /**
     * @var array
     */
    protected $attributeStoreValues = [];

    protected $headColumns;

    protected $additional;
    /**
     * @var SwatchCollectionFactory
     */
    protected $swatchCollectionFactory;
    /**
     * @var Data
     */
    protected $swatchesHelperData;

    private $userDefinedAttributes = [];

    protected $keysAdditional;

    /** @var Manager */
    protected $moduleManager;

    /** @var string */
    protected $multipleValueSeparator;

    /**
     * Product media gallery cache
     *
     * @var array[]
     */
    protected $mediaGalleryCache = [];

    /**
     * @var CacheInterface
     */
    protected $cache;

    /** @var array */
    private $cachedSwatchOptions = [];

    /**
     * Total entities limit to be fetched during export job. NULL to disable
     *
     * @var null|int
     */
    private $totalEntitiesLimit = null;

    /**
     * @var array|null
     */
    private $stores = null;

    /**
     * Attributes that should be exported
     *
     * @var string[]
     */
    protected $_bannedAttributes = ['media_gallery', 'giftcard_amounts', 'allow_open_amount'];

    /**
     * @var array
     */
    private $isLastPageExported = [];

    /**
     * Json Serializer
     *
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * Product constructor.
     *
     * @param TimezoneInterface $localeDate
     * @param Config $config
     * @param ResourceConnection $resource
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory
     * @param ConfigInterface $exportConfig
     * @param ProductFactory $productFactory
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $attrSetColFactory
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryColFactory
     * @param ItemFactory $itemFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product\Option\CollectionFactory $optionColFactory
     * @param CollectionFactory $attributeColFactory
     * @param Factory $_typeFactory
     * @param LinkTypeProvider $linkTypeProvider
     * @param RowCustomizerInterface $rowCustomizer
     * @param Additional $additional
     * @param Manager $moduleManager
     * @param Data $swatchesHelperData
     * @param SwatchCollectionFactory $swatchCollectionFactory
     * @param CacheInterface $cache
     * @param SerializerInterface $serializer
     * @param array $dateAttrCodes
     *
     * @throws LocalizedException
     */
    public function __construct(
        TimezoneInterface $localeDate,
        Config $config,
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory,
        ConfigInterface $exportConfig,
        ProductFactory $productFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $attrSetColFactory,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryColFactory,
        ItemFactory $itemFactory,
        \Magento\Catalog\Model\ResourceModel\Product\Option\CollectionFactory $optionColFactory,
        CollectionFactory $attributeColFactory,
        Factory $_typeFactory,
        LinkTypeProvider $linkTypeProvider,
        RowCustomizerInterface $rowCustomizer,
        \Firebear\ImportExport\Model\Export\Product\Additional $additional,
        Manager $moduleManager,
        Data $swatchesHelperData,
        SwatchCollectionFactory $swatchCollectionFactory,
        CacheInterface $cache,
        SerializerInterface $serializer,
        array $dateAttrCodes = []
    ) {
        $this->swatchCollectionFactory = $swatchCollectionFactory;
        $this->swatchesHelperData = $swatchesHelperData;
        $this->_fieldsMap += [self::COL_CATEGORY . '_position' => $this->_fieldsMap[self::COL_CATEGORY] . '_position'];

        parent::__construct(
            $localeDate,
            $config,
            $resource,
            $storeManager,
            $logger,
            $collectionFactory,
            $exportConfig,
            $productFactory,
            $attrSetColFactory,
            $categoryColFactory,
            $itemFactory,
            $optionColFactory,
            $attributeColFactory,
            $_typeFactory,
            $linkTypeProvider,
            $rowCustomizer,
            $additional,
            $moduleManager,
            $swatchesHelperData,
            $swatchCollectionFactory,
            $cache,
            $serializer,
            $dateAttrCodes
        );

        $this->additional = $additional;
        $this->moduleManager = $moduleManager;
        $this->multipleValueSeparator = Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR;
        $this->cache = $cache;
        $this->serializer = $serializer;
    }


    /**
     * @return array
     * @throws LocalizedException
     */
    public function export()
    {
        $this->keysAdditional = [];

        set_time_limit(0);

        $writer = $this->getWriter();
        $page = 0;
        $this->_parameters['export_by_page'] = true;

        $counts = 0;
        if (isset($this->_parameters[Processor::BEHAVIOR_DATA]['multiple_value_separator'])
            && $this->_parameters[Processor::BEHAVIOR_DATA]['multiple_value_separator']) {
            $this->multipleValueSeparator = $this->_parameters[Processor::BEHAVIOR_DATA]['multiple_value_separator'];
        }

        if (!empty($this->_parameters[Processor::BEHAVIOR_DATA]['export_by_page']) &&
            $this->_parameters[Processor::BEHAVIOR_DATA]['file_format'] == 'csv') {
            $page = $this->cache->load('current_page');

            if ($page == 1) {
                $this->cacheSave(null, 'last_page_exported');
            }
            $isAllStoresExported = $this->getAllStoresExported();
            if (!$isAllStoresExported) {
                $entityCollection = $this->_getEntityCollection(true);
                $entityCollection->setOrder('entity_id', 'asc');
                $entityCollection->setStoreId(Store::DEFAULT_STORE_ID);
                if (isset($this->_parameters[Processor::LAST_ENTITY_ID])
                    && $this->_parameters[Processor::LAST_ENTITY_ID] > 0
                    && $this->_parameters[Processor::LAST_ENTITY_SWITCH] > 0
                ) {
                    $entityCollection->addFieldToFilter(
                        'entity_id',
                        ['gt' => $this->_parameters[Processor::LAST_ENTITY_ID]]
                    );
                }
                $this->_prepareEntityCollection($entityCollection);

                if (!empty($this->_parameters[Processor::BEHAVIOR_DATA]['page_size'])) {
                    $pageSize = $this->_parameters[Processor::BEHAVIOR_DATA]['page_size'];
                } else {
                    $pageSize = 500;
                }

                $this->paginateCollection($page, $pageSize);

                if ($entityCollection->count()) {
                    $exportData = $this->getExportData();
                    if ($page == 1) {
                        $writer->setHeaderCols($this->_getHeaderColumns());
                    }
                    $exportData = $this->customBunchesData($exportData);
                    foreach ($exportData as $dataRow) {
                        if ($this->_parameters[Processor::LAST_ENTITY_SWITCH] > 0) {
                            $this->lastEntityId = $dataRow['product_id'];
                        }
                        $writer->writeRow($this->_customFieldsMapping($dataRow));
                        $counts++;
                    }
                }

                $isAllStoresExported = $this->getAllStoresExported();
                if ($page == $entityCollection->getLastPageNumber() || $isAllStoresExported) {
                    $this->cacheSave(0, 'export_by_page');
                } else {
                    $this->cacheSave(1, 'export_by_page');
                }
            }
        } else {
            while (true) {
                ++$page;

                $entityCollection = $this->_getEntityCollection(true);
                $entityCollection->setOrder('entity_id', 'asc');
                $entityCollection->setStoreId(Store::DEFAULT_STORE_ID);
                if ($page == 1) {
                    $this->cacheSave(null, 'last_page_exported');
                }
                if (isset($this->_parameters[Processor::LAST_ENTITY_ID])
                    && $this->_parameters[Processor::LAST_ENTITY_ID] > 0
                    && $this->_parameters[Processor::LAST_ENTITY_SWITCH] > 0
                ) {
                    $entityCollection->addFieldToFilter(
                        'entity_id',
                        ['gt' => $this->_parameters[Processor::LAST_ENTITY_ID]]
                    );
                }

                $this->_prepareEntityCollection($entityCollection);
                $this->paginateCollection($page, $this->getItemsPerPage());

                $entitiesCount = $entityCollection->count();
                if ($entitiesCount == 0) {
                    break;
                }

                $this->cache->save($entitiesCount, 'export_entities_count');

                $exportData = $this->getExportData();
                if ($page == 1) {
                    $writer->setHeaderCols($this->_getHeaderColumns());
                }

                $exportData = $this->customBunchesData($exportData);
                foreach ($exportData as $dataRow) {
                    /*customize*/
                    if(isset($dataRow['configurable_variations']) && strlen($dataRow['configurable_variations']) >= 32767) {
                        $dataRow['configurable_variations'] = "error";
                    }
                    /////////////

                    if ($this->_parameters[Processor::LAST_ENTITY_SWITCH] > 0) {
                        $this->lastEntityId = $dataRow['product_id'];
                    }
                    $writer->writeRow($this->_customFieldsMapping($dataRow));
                    /*try {
                        $writer->writeRow($this->_customFieldsMapping($dataRow));
                    } catch(Exception $e) {
                        $i = 1;
                    }*/
                    $counts++;

                    /*customize*/
                    /*if(isset($dataRow['configurable_variations']) && $dataRow['configurable_variations'] == "error") {
                        $this->cache->remove('export_entities_count');
                        return [$writer->getContents(), $counts, $this->lastEntityId];
                    }*/
                    /////////////
                }

                if ($this->isCollectionLastPage($entityCollection)) {
                    break;
                }
            }

            $this->cache->remove('export_entities_count');
        }

        return [$writer->getContents(), $counts, $this->lastEntityId];
    }

    /**
     * @return bool
     */
    private function getAllStoresExported()
    {
        $lastPageExportedByStores = $this->cache->load('last_page_exported');
        if ($lastPageExportedByStores) {
            $lastPageExportedByStores = $this->serializer->unserialize($lastPageExportedByStores);
        }
        $isAllStoresExported = true;
        foreach (array_keys($this->getStores()) as $storeId) {
            if (!isset($lastPageExportedByStores[$storeId])) {
                $isAllStoresExported = false;
            }
        }

        return $isAllStoresExported;
    }


}
