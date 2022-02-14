<?php
namespace Onesaas\Connect\Model\Resource;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Key extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('osconnectkeys', 'key_id');
    }
}
?>
