<?php
namespace Onesaas\Connect\Model;

use Magento\Framework\Model\AbstractModel;

class Key extends AbstractModel
{
	protected function _construct()
	{
		$this->_init('Onesaas\Connect\Model\Resource\Key');
	}
}

?>
