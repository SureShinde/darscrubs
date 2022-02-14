<?php

namespace Amasty\Shopby\Test\Unit\Plugin\Swatches\Model\Plugin;

use Amasty\Shopby\Model\Source\DisplayMode;
use Amasty\Shopby\Test\Unit\Traits;
use Amasty\Shopby\Plugin\Swatches\Model\Plugin\FilterRendererPlugin;
use Amasty\Shopby\Helper\FilterSetting;
use Magento\LayeredNavigation\Block\Navigation\FilterRenderer;
use Magento\Swatches\Model\Swatch;
use Amasty\ShopbyBase\Model\FilterSetting as SettingsModel;
use Amasty\Shopby\Model\Layer\Filter\Attribute as AmastyAttribute;

/**
 * Class FilterRendererPluginTest
 *
 * @see FilterRendererPlugin
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * phpcs:ignoreFile
 */
class FilterRendererPluginTest extends \PHPUnit\Framework\TestCase
{
    use Traits\ObjectManagerTrait;
    use Traits\ReflectionTrait;

    /**
     * @var
     */
    private $plugin;

    /**
     * @var
     */
    private $object;

    /**
     * @covers FilterRendererPlugin::beforeAroundRender
     *
     * @dataProvider beforeAroundRenderDataProvider
     */
    public function testBeforeAroundRender($addAttribute, $displayMode, $result)
    {
        $filterSetting = $this->createPartialMock(FilterSetting::class, ['getSettingByLayerFilter']);
        $settingsModel = $this->createPartialMock(SettingsModel::class, ['getDisplayMode']);
        $this->plugin = $this->getObjectManager()->getObject(FilterRendererPlugin::class, ['filterSetting' => $filterSetting]);
        $filterRenderer = $this->getObjectManager()->getObject(FilterRenderer::class, []);
        $this->object = $this->getObjectManager()->getObject(\Magento\Framework\DataObject::class, []);
        $filter = $this->getObjectManager()->getObject(AmastyAttribute::class, []);
        if ($addAttribute) {
            $filter->setData('attribute_model', $this->object);
        }

        $filterSetting->expects($this->any())->method('getSettingByLayerFilter')->willReturn($settingsModel);
        $settingsModel->expects($this->any())->method('getDisplayMode')->willReturn($displayMode);

        $resultMethod = $this->plugin->beforeAroundRender(null, $filterRenderer, null, $filter);
        if ($addAttribute) {
            $this->assertEquals($result, $resultMethod[2]->getData('attribute_model')->getData(Swatch::SWATCH_INPUT_TYPE_KEY));
        } else {
            $this->assertNull($resultMethod[2]->getData('attribute_model'));
        }
    }

    /**
     * DataProvider for testBeforeAroundRender
     *
     * @return array
     */
    public function beforeAroundRenderDataProvider()
    {
        return [
            [false, DisplayMode::MODE_DEFAULT, null],
            [true, DisplayMode::MODE_DEFAULT, Swatch::SWATCH_INPUT_TYPE_DROPDOWN],
            [true, DisplayMode::MODE_DROPDOWN, Swatch::SWATCH_INPUT_TYPE_DROPDOWN],
            [true, DisplayMode::MODE_IMAGES, null],
            [true, DisplayMode::MODE_IMAGES_LABELS, null],
        ];
    }
}
