<?php

declare(strict_types=1);

namespace Amasty\GroupedOptions\Ui\Button\Group;

class SaveButton extends GenericButton
{
    /**
     * @return array
     */
    public function getButtonData()
    {
        return [
            'label' => __('Save'),
            'class' => 'save primary',
            'on_click' => '',
            'sort_order' => '50',
        ];
    }
}
