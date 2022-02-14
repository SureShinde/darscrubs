<?php

namespace Amasty\GroupedOptions\Api\Data;

use Magento\Framework\Exception\NoSuchEntityException;

/**
 * TODO: move from data folder
 */
interface GroupAttrRepositoryInterface
{
    /**
     * @param int $id
     * @return GroupAttrInterface
     * @throws NoSuchEntityException
     */
    public function get($id): GroupAttrInterface;

    /**
     * @param GroupAttrInterface $entity
     * @return $this
     */
    public function save(GroupAttrInterface $entity);

    /**
     * @param GroupAttrInterface $entity
     * @return $this
     */
    public function delete(GroupAttrInterface $entity);
}
