<?php

namespace Amasty\Shopby\Model;

use Amasty\Shopby\Api\Data\FromToFilterInterface;
use Magento\Catalog\Model\Layer\Filter\FilterInterface;
use Magento\Catalog\Model\Layer\Filter\AbstractFilter;
use Magento\Framework\App\RequestInterface;
use Amasty\Shopby\Model\Layer\Filter\Price;

class Request extends \Magento\Framework\DataObject
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var array
     */
    private $brandParam;

    public function __construct(
        RequestInterface $request,
        array $data = []
    ) {
        parent::__construct($data);
        $this->request = $request;
    }

    /**
     * @param AbstractFilter $filter
     * @return mixed|string
     */
    public function getFilterParam(FilterInterface $filter)
    {
        return $this->getParams($filter);
    }

    /**
     * @param string $paramName
     * @return mixed
     */
    public function getDeltaParam(string $paramName)
    {
        return $this->getParam($paramName);
    }

    /**
     * @param $filter
     * @return string
     */
    private function getParams(FilterInterface $filter)
    {
        $param = $this->getParam($filter->getRequestVar());
        if ($filter->getRequestVar() == \Amasty\Shopby\Model\Source\DisplayMode::ATTRUBUTE_PRICE && $param) {
            $param = $this->getParam(Price::AM_BASE_PRICE) ?: $param;
        }

        return $param;
    }

    /**
     * @param $brandParam
     * @return $this
     */
    public function setBrandParam($brandParam)
    {
        $this->brandParam = $brandParam;
        return $this;
    }

    /**
     * @return array
     */
    public function getBrandParam()
    {
        return $this->brandParam;
    }

    /**
     * @param $requestVar
     * @return mixed
     */
    public function getParam($requestVar)
    {
        $bulkParams = $this->getBulkParams();
        if (array_key_exists($requestVar, $bulkParams)) {
            $data = implode(',', $bulkParams[$requestVar]);
        } else {
            $data = $this->request->getParam($requestVar);
            $data = $data ? (string)$data : $data;
        }

        return $data;
    }

    /**
     * @return array
     */
    public function getRequestParams()
    {
        $result = $this->getBulkParams();

        if (!$result) {
            foreach ($this->request->getParams() as $key => $param) {
                if ($param && $key !== 'id') {
                    $result[$key][] = $param;
                }
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    protected function getBulkParams()
    {
        $bulkParams = $this->request->getParam('amshopby', []);
        $brandParam = $this->getBrandParam();
        if ($brandParam) {
            $bulkParams[$brandParam['code']] = $brandParam['value'];
        }
        return $bulkParams;
    }
}
