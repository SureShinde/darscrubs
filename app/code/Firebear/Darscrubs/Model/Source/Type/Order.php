<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\Darscrubs\Model\Source\Type;

use Firebear\ImportExport\Model\Source\Type\AbstractType;

/**
 * Class Order
 *
 * @package Firebear\Darscrubs\Model\Source\Type
 */
class Order extends AbstractType
{
    /**
     * @var string
     */
    const SOURCE_TYPE = 'sap';

    /**
     * @inheritdoc
     */
    protected $code = self::SOURCE_TYPE;

    /**
     * @inheritdoc
     */
    public function run($model)
    {
        $result = true;
        $errors = [];

        try {
            $this->setExportModel($model);
            $this->getExportModel()->export();
        } catch (\Exception $e) {
            $errors[] = $e->getMessage();
            if (empty($errors)) {
                $errors[] = __('Failed to export data into SAP.');
            }

            $result = false;
        }

        return [$result, '', $errors];
    }

    /**
     * @inheritdoc
     */
    public function uploadSource()
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function importImage($importImage, $imageSting)
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function checkModified($timestamp)
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    protected function _getSourceClient()
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function resetSource()
    {
        return null;
    }
}
