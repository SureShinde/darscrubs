<?php
namespace Onesaas\Connect\Plugin;

class CsrfValidatorSkip
{
    /**
     * @param \Magento\Framework\App\Request\CsrfValidator $subject
     * @param \Closure $proceed
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\App\ActionInterface $action
     */
    public function aroundValidate(
        $subject,
        \Closure $proceed,
        $request,
        $action
    )	
	{
        if ($request->getModuleName() == 'onesaas_connect') {
            return; // Skip the CSRF check
        }
        $proceed($request, $action); // Proceed with Magento 2 core functionalities
    }
}