<?php
namespace Onesaas\Connect\Model\Resource\Key;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
	protected function _construct()
	{
		$this->_init('Onesaas\Connect\Model\Key','Onesaas\Connect\Model\Resource\Key');
	}
}
?>
