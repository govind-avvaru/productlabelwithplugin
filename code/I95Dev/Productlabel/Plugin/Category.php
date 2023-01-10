<?php

namespace I95Dev\Productlabel\Plugin;


use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Smile\ProductLabel\Api\Data\ProductLabelInterface;
use Smile\ProductLabel\Block\ProductLabel\ProductLabel;
use Smile\ProductLabel\Model\ImageLabel\Image;
use Smile\ProductLabel\Model\ResourceModel\ProductLabel\CollectionFactory as ProductLabelCollectionFactory;

class Category
{
    protected Context $context;

    protected Registry $registry;

    protected ProductLabelCollectionFactory $productLabelCollectionFactory;

    protected Image $imageHelper;

    protected ProductInterface $product;

    private CacheInterface $cache;

    private StoreManagerInterface $storeManager;

    protected  $productLabel;

    public function __construct(
        Context                       $context,
        Registry                      $registry,
        Image                         $imageHelper,
        ProductLabelCollectionFactory $productLabelCollectionFactory,
        CacheInterface                $cache,
        ProductInterface              $product,
        ProductLabel                  $productLabel,
        array                         $data = []
    ) {
        $this->context                       =$context;
        $this->registry                      = $registry;
        $this->imageHelper                   = $imageHelper;
        $this->productLabelCollectionFactory = $productLabelCollectionFactory;
        $this->cache                         = $cache;
        $this->storeManager                  = $context->getStoreManager();
        $this->product                       = $product;
        $this->productLabel                  = $productLabel;
    }

    public function aroundgetProductLabels(ProductLabel $subject,callable $proceed): array
    {
        $productLabels     = [];
        $productLabelList  = $this->getProductLabelsList();

        $attributesProduct = $subject->getAttributesOfCurrentProduct();
        foreach ($productLabelList as $productLabel) {
            $attributeIdLabel = $productLabel['attribute_id'];
            $optionIdLabel    = $productLabel['option_id'];
            $categoryLabelId  =$productLabel['category_id'];
            $currentCategory = $this->registry->registry('current_category');
            if(isset($currentCategory))
            {
                $currentCategoryId=$currentCategory->getId();
            }
            if(empty($categoryLabelId)) {
                foreach ($attributesProduct as $attribute) {
                    if (isset($attribute['id']) && ($attributeIdLabel == $attribute['id'])) {
                        $options = $attribute['options'] ?? [];
                        if (!is_array($options)) {
                            $options = explode(',', $options);
                        }
                        if (
                            in_array($optionIdLabel, $options) &&
                            in_array($subject->getCurrentView(), $productLabel['display_on'])
                        ) {
                            $productLabel['class'] = $this->getCssClass($productLabel);
                            $productLabel['image'] = $subject->getImageUrl($productLabel['image']);
                            $class = $this->getCssClass($productLabel);
                            $productLabels[$class][] = $productLabel;
                        }
                    }
                }
            }
            else{
                if(isset($currentCategoryId)){
                    if($currentCategoryId==$categoryLabelId) {
                        if (in_array($subject->getCurrentView(), $productLabel['display_on'])) {
                            $productLabel['class'] = $this->getCssClass($productLabel);
                            $productLabel['image'] = $subject->getImageUrl($productLabel['image']);
                            $class = $this->getCssClass($productLabel);
                            $productLabels[$class][] = $productLabel;
                        }
                    }
                }
            }
        }

        return $productLabels;
    }
    /**
     * Fetch product labels list : the list of all enabled product labels.
     *
     * Fetched only once and put in cache.
     *
     * @return array
     */
    private function getProductLabelsList(): array
    {
        $storeId          = $this->getStoreId();
        $cacheKey         = 'smile_productlabel_frontend_' . $storeId;
        $productLabelList = $this->cache->load($cacheKey);

        if (is_string($productLabelList)) {
            $productLabelList = json_decode($productLabelList, true);
        }

        if ($productLabelList === false) {
            /** @var \Smile\ProductLabel\Model\ResourceModel\ProductLabel\CollectionFactory */
            $productLabelsCollection = $this->productLabelCollectionFactory->create();

            // @phpstan-ignore-next-line
            $productLabelList = $productLabelsCollection
                ->addStoreFilter($storeId)
                ->addFieldToFilter('is_active', true)
                ->getData();

            $productLabelList = array_map(function ($label) {
                $label['display_on'] = explode(',', $label['display_on']);

                return $label;
            }, $productLabelList);

            $this->cache->save(
                json_encode($productLabelList),
                $cacheKey,
                [\Smile\ProductLabel\Model\ProductLabel::CACHE_TAG]
            );
        }

        return $productLabelList;
    }
    /**
     * Get current store Id.
     */
    private function getStoreId(): int
    {
        return (int) $this->storeManager->getStore()->getId();
    }
    /**
     * Fetch proper css class according to current label and view.
     *
     * @param array $productLabel A product Label
     */
    private function getCssClass(array $productLabel): string
    {
        $class = '';

        if ($this->productLabel->getCurrentView() === ProductLabelInterface::PRODUCTLABEL_DISPLAY_PRODUCT) {
            $class = $productLabel['position_product_view'] . ' product';
        }

        if ($this->productLabel->getCurrentView() === ProductLabelInterface::PRODUCTLABEL_DISPLAY_LISTING) {
            $class = $productLabel['position_category_list'] . ' category';
        }

        return $class;
    }

}
