<?php

declare(strict_types = 1);

namespace Amasty\Shopby\Ui\DataProvider\Form\Category\Modifier;

use Magento\Catalog\Model\Category;
use Magento\Framework\App\RequestInterface;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;

class HideItems implements ModifierInterface
{
    const ROOT_CATEGORY_LVL = 1;

    const HIDE_FIELD_FOR_ROOT = [
        'am_exclude_from_filter'
    ];

    /**
     * @var Category
     */
    private $entity;

    /**
     * @var int
     */
    private $parentId;

    public function __construct(
        RequestInterface $request
    ) {
        $this->parentId = (int) $request->getParam('parent', 0);
    }

    /**
     * @inheritdoc
     */
    public function modifyData(array $data)
    {
        return $data;
    }

    /**
     * @inheritdoc
     */
    public function modifyMeta(array $meta)
    {
        return $this->modifyLevel($meta);
    }

    private function modifyLevel(array $meta): array
    {
        return $this->getCategoryLevel() == self::ROOT_CATEGORY_LVL
            ? $this->hideFields($meta, self::HIDE_FIELD_FOR_ROOT)
            : $meta;
    }

    private function getCategoryLevel(): int
    {
        if ($this->parentId && $this->entity->isObjectNew()) {
            $level = $this->entity->setParentId($this->parentId)->getParentCategory()->getLevel() + 1;
        } else {
            $level = $this->entity->getLevel();
        }

        return (int) $level;
    }

    private function hideFields(array $meta, array $fieldsToHide): array
    {
        foreach ($fieldsToHide as $field) {
            $meta['display_settings']['children'][$field]['arguments']['data']['config']['visible'] = false;
        }

        return $meta;
    }

    public function setCategory(?Category $category): void
    {
        $this->entity = $category;
    }
}
