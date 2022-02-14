<?php

declare(strict_types=1);

namespace Amasty\GroupedOptions\Model\GroupAttr\Query;

use Amasty\GroupedOptions\Model\ResourceModel\GroupAttr\LoadRelatedOptions;

class GetRelatedOptions
{
    /**
     * @var array
     */
    private $cache = [];

    /**
     * @var bool
     */
    private $allOptionsLoaded = false;

    /**
     * @var LoadRelatedOptions
     */
    private $loadRelatedOptions;

    public function __construct(LoadRelatedOptions $loadRelatedOptions)
    {
        $this->loadRelatedOptions = $loadRelatedOptions;
    }

    /**
     * Return eav options which represent in grouped options.
     * Format return data:
     *  array(
     *      attribute_id => array(
     *          eav_option_id => array (
     *              group_option_id
     *              ...
     *          )
     *          ...
     *      )
     *      ...
     *  )
     *
     * @param int|null $attributeId
     * @return array
     */
    public function execute(?int $attributeId = null): array
    {
        if (($attributeId === null && $this->allOptionsLoaded === false)
            || !isset($this->cache[$attributeId])
        ) {
            if ($attributeId === null) {
                $this->allOptionsLoaded = true;
            }

            $this->cache += $this->loadRelatedOptions->execute($attributeId, true);
        }

        return $attributeId === null ? $this->cache : $this->cache[$attributeId];
    }
}
