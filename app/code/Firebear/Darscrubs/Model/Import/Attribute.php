<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\Darscrubs\Model\Import;

use Exception;
use Firebear\ImportExport\Helper\Data;
use Firebear\ImportExport\Setup\EavSetup;
use Firebear\ImportExport\Traits\Import\Entity as ImportTrait;
use InvalidArgumentException;
use Magento\Catalog\Model\Product\Media\Config;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Attribute as Attr;
use Magento\Eav\Model\Entity\Attribute\Set;
use Magento\Eav\Model\Entity\Attribute\SetFactory;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\AbstractEntity;
use Magento\ImportExport\Model\Import\AbstractSource;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingError;
use Magento\ImportExport\Model\ImportFactory;
use Magento\Swatches\Helper\Media;
use Zend\Serializer\Serializer;
use Zend_Validate;

/**
 * Attribute Import
 */
class Attribute extends \Firebear\ImportExport\Model\Import\Attribute
{
    use ImportTrait;

    /**
     * Entity Type Code
     */
    const ENTITY_TYPE_CODE = 'attribute';

    /**
     * Attribute Id column name
     */
    const COLUMN_ENTITY_ID = 'attribute_id';

    /**
     * Store Id column name
     */
    const COLUMN_STORE_ID = 'store_id';

    /**
     * Attribute code column name
     */
    const COLUMN_ATTRIBUTE_CODE = 'attribute_code';

    /**
     * Column product attribute set
     */
    const COLUMN_ATTRIBUTE_SET = 'attribute_set';

    /**
     * Column product attribute group
     */
    const COLUMN_ATTRIBUTE_GROUP = 'group:name';

    /**
     * Column product attribute group
     */
    const COLUMN_ATTRIBUTE_GROUP_SORT_ORDER = 'group:sort_order';

    /**
     * Column product attribute base option
     */
    const COLUMN_ATTRIBUTE_BASE_OPTION = 'option:base_value';

    /**
     * Column product attribute option
     */
    const COLUMN_ATTRIBUTE_OPTION = 'option:value';

    /**
     * Column product attribute option for swatches
     */
    const COLUMN_SWATCH_ATTRIBUTE_OPTION = 'option:swatch_value';

    /**
     * Column for swatches type
     */
    const COLUMN_SWATCH_ATTRIBUTE_TYPE = 'option:swatch_type';

    /**
     * Column product attribute delete option value
     */
    const COLUMN_ATTRIBUTE_DELETE_VALUES = 'option:delete_values';

    /**
     * Column product attribute delete option
     */
    const COLUMN_ATTRIBUTE_DELETE_OPTIONS = 'option:delete_options';

    /**
     * Column product attribute group
     */
    const COLUMN_ATTRIBUTE_OPTION_SORT_ORDER = 'option:sort_order';

    /**
     * Main Table Name
     *
     * @var string
     */
    protected $_mainTable = 'eav_attribute';

    /**
     * Attribute set
     *
     * @var \Magento\Eav\Model\Entity\Attribute\Set
     */
    protected $_set;

    /**
     * Attribute set list
     *
     * @var []
     */
    protected $_setList = [];

    /**
     * Default attribute set id
     *
     * @var integer
     */
    protected $_defaultSetId;

    /**
     * EAV config
     *
     * @var array
     */
    protected $_eavConfig;

    /**
     * EAV setup
     *
     * @var \Magento\Eav\Setup\EavSetup
     */
    protected $_eavSetup;

    /**
     * @var ResourceConnection
     */
    protected $_resource;

    /**
     * Catalog product entity typeId
     *
     * @var int
     */
    protected $_entityTypeId;

    /**
     * Permanent entity columns
     *
     * @var string[]
     */
    protected $_permanentAttributes = [
        self::COLUMN_ATTRIBUTE_CODE
    ];

    /**
     * Permanent entity columns
     *
     * @var string[]
     */
    protected $_storeDependentAttributes = [
        'option:value',
        'frontend_label'
    ];

    /**
     * Error Codes
     */
    const ERROR_DUPLICATE_ATTRIBUTE_CODE = 'duplicateAttributeCode';
    const ERROR_ATTRIBUTE_CODE_IS_EMPTY = 'attributeCodeIsEmpty';
    const ERROR_STORE_ID_IS_EMPTY = 'attributeStoreIdIsEmpty';
    const ERROR_ATTRIBUTE_SET_IS_EMPTY = 'attributeSetIsEmpty';

    /**
     * Validation Failure Message Template Definitions
     *
     * @var array
     */
    protected $_messageTemplates = [
        self::ERROR_DUPLICATE_ATTRIBUTE_CODE => 'Attribute code is found more than once in the import file',
        self::ERROR_ATTRIBUTE_CODE_IS_EMPTY => 'Attribute code is empty',
        self::ERROR_STORE_ID_IS_EMPTY => 'Attribute store_id is empty',
        self::ERROR_ATTRIBUTE_SET_IS_EMPTY => 'Attribute set is empty',
    ];

    /**
     * Deleted attributes
     *
     * @var array
     */
    private $_alreadyDeleted = [];

    /**
     * Recorded attributes
     *
     * @var array
     */
    private $_alreadyRecorded = [];

    /**
     * @var \Magento\Catalog\Model\Product\Media\Config
     */
    protected $mediaConfig;

    /**
     * @var \Magento\CatalogImportExport\Model\Import\Uploader
     */
    protected $_fileUploader;

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $_mediaDirectory;

    /**
     * @var \Firebear\ImportExport\Model\Import\UploaderFactory
     */
    protected $_uploaderFactory;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    /**
     * @var \Magento\Swatches\Helper\Data
     */
    protected $swatchesHelperData;

    /**
     * @var \Magento\Swatches\Helper\Media
     */
    protected $swatchHelperMedia;

    /**
     * @var SetFactory
     */
    protected $attributeSetFactory;

    /**
     * Attribute constructor.
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param ImportFactory $importFactory
     * @param Set $set
     * @param EavConfig $eavConfig
     * @param EavSetup $eavSetup
     * @param Data $helper
     * @param Config $mediaConfig
     * @param Uploader $_fileUploader
     * @param Filesystem $filesystem
     * @param \Magento\Swatches\Helper\Data $swatchesHelperData
     * @param Media $swatchHelperMedia
     * @param UploaderFactory $_uploaderFactory
     * @param ConsoleOutput $output
     * @param ImportExportData $importExportData
     * @param JsonHelper $jsonHelper
     * @param Data $helper
     * @param SetFactory $attributeSetFactory
     * @param array $data
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        \Firebear\ImportExport\Model\Import\Context $context,
        ScopeConfigInterface $scopeConfig,
        ImportFactory $importFactory,
        Set $set,
        EavConfig $eavConfig,
        EavSetup $eavSetup,
        Data $helper,
        Config $mediaConfig,
        \Firebear\ImportExport\Model\Import\Uploader $_fileUploader,
        Filesystem $filesystem,
        \Magento\Swatches\Helper\Data $swatchesHelperData,
        Media $swatchHelperMedia,
        \Firebear\ImportExport\Model\Import\UploaderFactory $_uploaderFactory,
        SetFactory $attributeSetFactory,
        array $data = []
    ) {
        $this->_logger = $context->getLogger();
        $this->output = $context->getOutput();
        $this->_resource = $context->getResource();
        $this->_importExportData = $context->getImportExportData();
        $this->_resourceHelper = $context->getResourceHelper();
        $this->jsonHelper = $context->getJsonHelper();
        $this->_set = $set;
        $this->_eavConfig = $eavConfig;
        $this->_eavSetup = $eavSetup;
        $this->_helper = $helper;
        $this->attributeSetFactory = $attributeSetFactory;

        $this->mediaConfig = $mediaConfig;
        $this->_fileUploader = $_fileUploader;
        $this->filesystem = $filesystem;
        $this->_mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::ROOT);
        $this->_uploaderFactory = $_uploaderFactory;
        $this->swatchesHelperData = $swatchesHelperData;
        $this->swatchHelperMedia = $swatchHelperMedia;

        parent::__construct(
            $context,
            $scopeConfig,
            $importFactory,
            $set,
            $eavConfig,
            $eavSetup,
            $helper,
            $mediaConfig,
            $_fileUploader,
            $filesystem,
            $swatchesHelperData,
            $swatchHelperMedia,
            $_uploaderFactory,
            $attributeSetFactory,
            $data
        );
    }

    /**
     * Prepare Data For Update
     *
     * @param array $rowData
     *
     * @return array
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareDataForUpdate(array $rowData)
    {
        $entityId = $this->_getExistEntityId($rowData);
        $rowData[self::COLUMN_ENTITY_ID] = $entityId ?: null;

        if (!empty($rowData[self::COLUMN_ATTRIBUTE_SET])) {
            $setName = trim($rowData[self::COLUMN_ATTRIBUTE_SET]);
            unset($rowData[self::COLUMN_ATTRIBUTE_SET]);
            $rowData['attribute_set_id'] = isset($this->_setList[$setName])
                ? $this->_setList[$setName]
                : $this->createNewSet($setName);
        } else {
            $rowData['attribute_set_id'] = null;
        }

        if (!empty($rowData[self::COLUMN_ATTRIBUTE_GROUP])) {
            $rowData['group'] = $rowData[self::COLUMN_ATTRIBUTE_GROUP];
            unset($rowData[self::COLUMN_ATTRIBUTE_GROUP]);
        }

        if (!empty($rowData[self::COLUMN_ATTRIBUTE_GROUP_SORT_ORDER])) {
            $rowData['sort_order'] = $rowData[self::COLUMN_ATTRIBUTE_GROUP_SORT_ORDER];
            unset($rowData[self::COLUMN_ATTRIBUTE_GROUP_SORT_ORDER]);
        }

        if (isset($rowData[self::COLUMN_ATTRIBUTE_OPTION])) {
            $optionId = 0;
            $storeId = $rowData[self::COLUMN_STORE_ID];
            $value = trim($rowData[self::COLUMN_ATTRIBUTE_OPTION]);
            if ($entityId) {
                if (isset($rowData[self::COLUMN_ATTRIBUTE_BASE_OPTION]) && $storeId > 0) {
                    $baseValue = trim($rowData[self::COLUMN_ATTRIBUTE_BASE_OPTION]);
                    $optionId = $this->_getExistOptionId($entityId, 0, $baseValue);
                } else {
                    $optionId = $this->_getExistOptionId($entityId, $storeId, $value);
                }
            }

            $rowData['option'] = [];

            if ($optionId == 0) {
                $rowData['option']['value']['new'][$storeId] = $value;
            } else {
                $rowData['option']['value'][$optionId][$storeId] = $value;
            }

            unset($rowData[self::COLUMN_ATTRIBUTE_OPTION]);

            if (!empty($rowData[self::COLUMN_ATTRIBUTE_OPTION_SORT_ORDER])) {
                if ($optionId == 0) {
                    $rowData['option']['order']['new'] = $rowData[self::COLUMN_ATTRIBUTE_OPTION_SORT_ORDER];
                } else {
                    $rowData['option']['order'][$optionId] = $rowData[self::COLUMN_ATTRIBUTE_OPTION_SORT_ORDER];
                }
                unset($rowData[self::COLUMN_ATTRIBUTE_OPTION_SORT_ORDER]);
            }

            if (!empty($rowData[self::COLUMN_SWATCH_ATTRIBUTE_OPTION])) {
                if (!empty($rowData[self::COLUMN_SWATCH_ATTRIBUTE_TYPE])
                    && (int)$rowData[self::COLUMN_SWATCH_ATTRIBUTE_TYPE] === 2
                ) {
                    $valueSwatch = $this->uploadVisualSwatchImage(trim($rowData[self::COLUMN_SWATCH_ATTRIBUTE_OPTION]));
                } else {
                    $valueSwatch = trim($rowData[self::COLUMN_SWATCH_ATTRIBUTE_OPTION]);

                }
                if ($valueSwatch) {
                    $rowData['option']['swatch'] = $valueSwatch;
                    /*customize*/
                    $rowData['option']['swatch_type'] = $rowData[self::COLUMN_SWATCH_ATTRIBUTE_TYPE];
                    /*customize end*/
                    unset($rowData[self::COLUMN_SWATCH_ATTRIBUTE_OPTION]);
                }
            }
        }

        if (!empty($rowData[self::COLUMN_ATTRIBUTE_DELETE_VALUES])) {
            if ($this->getBehavior() == Import::BEHAVIOR_APPEND) {
                $valuesForDelete = array_map('trim', explode(',', $rowData[self::COLUMN_ATTRIBUTE_DELETE_VALUES]));
                $optionIdsForDelete = [];
                $valueIdsForDelete = [];

                foreach ($valuesForDelete as $valueForDelete) {
                    if ($entityId) {
                        $optionId = $this->_getExistOptionId($entityId, 0, $valueForDelete);
                        if (!empty($optionId)) {
                            $optionIdsForDelete[] = $optionId;
                        }
                        $valueId = $this->_getExistOptionValueId($entityId, $valueForDelete);
                        if (!empty($valueId)) {
                            $valueIdsForDelete[] = $valueId;
                        }
                    }
                }
                $rowData[self::COLUMN_ATTRIBUTE_DELETE_OPTIONS] = $optionIdsForDelete;
                $rowData[self::COLUMN_ATTRIBUTE_DELETE_VALUES] = $valueIdsForDelete;
            } else {
                $rowData[self::COLUMN_ATTRIBUTE_DELETE_OPTIONS] = [];
                $rowData[self::COLUMN_ATTRIBUTE_DELETE_VALUES] = [];
            }
        }

        if ($this->isNeedStore($rowData)) {
            foreach ($this->_storeDependentAttributes as $storeDependentAttribute) {
                if (isset($rowData[$storeDependentAttribute], $rowData[self::COLUMN_STORE_ID])
                    && $storeDependentAttribute === 'frontend_label'
                    && $rowData[self::COLUMN_STORE_ID] > 0
                ) {
                    $valueUpdate = [$rowData[self::COLUMN_STORE_ID] => $rowData[$storeDependentAttribute]];
                    $rowData['store_labels'] = $valueUpdate;
                    unset($rowData[$storeDependentAttribute]);
                }
            }
        }

        if (!isset($rowData['is_user_defined']) && !$entityId) {
            $rowData['is_user_defined'] = '1';
        }

        foreach ($rowData as $field => $value) {
            if ($value === '') {
                unset($rowData[$field]);
            }
        }
        return $rowData;
    }


}
