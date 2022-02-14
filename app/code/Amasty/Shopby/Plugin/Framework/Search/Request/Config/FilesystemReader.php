<?php

declare(strict_types=1);

namespace Amasty\Shopby\Plugin\Framework\Search\Request\Config;

use Amasty\Shopby\Model\Search\RequestGenerator;

class FilesystemReader
{
    /**
     * @var RequestGenerator
     */
    private $requestGenerator;

    public function __construct(RequestGenerator $requestGenerator)
    {
        $this->requestGenerator = $requestGenerator;
    }

    /**
     * @param \Magento\Framework\Config\ReaderInterface $subject
     * @param array $requests
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormatParameter)
     */
    public function afterRead(\Magento\Framework\Config\ReaderInterface $subject, $requests): array
    {
        return array_merge_recursive($requests, $this->requestGenerator->generate());
    }
}
