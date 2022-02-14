<?php

declare(strict_types=1);

namespace Amasty\GroupedOptions\Model\GroupAttr;

class DataProviderFactory implements DataFactoryProviderInterface
{
    /**
     * Object Manager instance
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager = null;

    /**
     * Instance name to create
     *
     * @var string
     */
    protected $_instanceName = DataProvider::class;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    /**
     * Wrapper for self::getInstance()
     *
     * @param array $data
     *
     * @return DataProvider
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function create(array $data = []): DataProvider
    {
        return $this->getInstance();
    }

    /**
     * Get created class instance
     *
     * @return DataProvider
     */
    public function getInstance()
    {
        return $this->objectManager->get($this->_instanceName);
    }
}
