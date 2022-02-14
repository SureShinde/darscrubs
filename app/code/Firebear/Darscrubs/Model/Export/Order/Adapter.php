<?php

namespace Firebear\Darscrubs\Model\Export\Order;

use Magento\Framework\App\CacheInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Filesystem;
use Firebear\ImportExport\Model\Export\Adapter\Csv;
use Magento\Framework\Filesystem\File\Write as FileHandler;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;

/**
 * @codingStandardsIgnoreFile
 * phpcs:ignoreFile
 */
class Adapter extends Csv
{
    /**
     * Adapter Data
     *
     * @var []
     */
    protected $data;

    /**
     * @var Converter
     */
    private $converter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private $orderData;

    /**
     * @param Converter $converter
     * @inheritdoc
     */
    public function __construct(
        Converter $converter,
        Filesystem $filesystem,
        CacheInterface $cache,
        LoggerInterface $logger,
        $destination = null,
        array $data = []
    ) {
        parent::__construct($filesystem, $cache, $logger, $destination, $data);

        $this->converter = $converter;
        $this->logger = $logger;
    }

    /**
     * Write row data to source file.
     *
     * @throws \Exception
     * @inheritdoc
     */
    public function writeRow(array $rowData)
    {
        if (!empty($rowData['entity_id']) && !empty($this->orderData)) {
            // Write rows related to single order entity
            $this->writeOrderData();
            $this->orderData = [];
        }

        // Aggregate rows data related to current order
        $this->collectOrderData($rowData);

        return $this;
    }

    /**
     * Collect rows data related to single order entity to have all the order data in one bunch
     *
     * @param array $rowData
     */
    private function collectOrderData($rowData)
    {
        $this->orderData[] = $rowData;
    }

    /**
     * Write rows related to single order entity
     *
     * @throws FileSystemException
     * @throws LocalizedException
     */
    private function writeOrderData()
    {
        $orderData = $this->converter->covertOrderData($this->orderData);
        foreach ($orderData as $dataRow) {
            $dataRow = implode('	', $dataRow) . "\r\n";
            $this->_fileHandler->write($dataRow);
        }
    }
}
