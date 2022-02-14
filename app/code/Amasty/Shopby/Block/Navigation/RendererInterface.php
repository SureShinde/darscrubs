<?php
namespace Amasty\Shopby\Block\Navigation;

interface RendererInterface
{
    public function collectFilters();

    public function getFilter();
}
