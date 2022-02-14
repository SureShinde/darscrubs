<?php

declare(strict_types=1);

namespace Amasty\ShopbyBrand\Setup\Patch\Data;

use Magento\Cms\Model\PageFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;

class CreateAllBrandsCmsPage implements DataPatchInterface
{
    /**
     * @var PageFactory
     */
    private $pageFactory;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var UrlFinderInterface
     */
    private $urlFinder;

    /**
     * @var ScopeConfigInterface
     */
    private $config;

    public function __construct(
        PageFactory $pageFactory,
        WriterInterface $configWriter,
        UrlFinderInterface $urlFinder,
        ScopeConfigInterface $config
    ) {
        $this->pageFactory = $pageFactory;
        $this->configWriter = $configWriter;
        $this->urlFinder = $urlFinder;
        $this->config = $config;
    }

    public function apply()
    {
        if (!$this->config->getValue('amshopby_brand/general/brands_page')) {
            $identifier = $this->getIdentifier();
            $content = '<p style="text-align: left;"><span style="font-size: small;">
<strong>Searching for a favorite brand? Browse the list below to find just the label you\'re looking for!</strong>
</span></p><p style="text-align: left;"><span style="font-size: medium;"><strong><br /></strong></span></p>
<p><img src="{{media url="wysiwyg/collection/collection-performance.jpg"}}" alt="" /></p>
<p>{{widget type="Amasty\ShopbyBrand\Block\Widget\BrandSlider" template="widget/brand_list/slider.phtml"}}</p>
<p>{{widget type="Amasty\ShopbyBrand\Block\Widget\BrandList" columns="3"
template="widget/brand_list/index.phtml"}}</p>';
            $page = $this->pageFactory->create();
            $page->setTitle('All Brands Page')
                ->setIdentifier($identifier)
                ->setData('mageworx_hreflang_identifier', 'en-us')
                ->setData('mp_exclude_sitemap', '1')
                ->setIsActive(false)
                ->setPageLayout('1column')
                ->setStores([0])
                ->setContent($content)
                ->save();
            $this->configWriter->save('amshopby_brand/general/brands_page', $identifier);
        }

        return $this;
    }

    /**
     * @param int $index
     *
     * @return string
     */
    private function getIdentifier(int $index = 0): string
    {
        $identifier = 'brands';
        if ($index) {
            $identifier .= '_' . $index;
        }
        $rewrite = $this->urlFinder->findOneByData([UrlRewrite::REQUEST_PATH => $identifier]);
        if ($rewrite !== null) {
            return $this->getIdentifier(++$index);
        }

        return $identifier;
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }
}
