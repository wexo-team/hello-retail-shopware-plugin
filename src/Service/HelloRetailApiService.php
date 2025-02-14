<?php declare(strict_types=1);

namespace Helret\HelloRetail\Service;

use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class HelloRetailApiService
{
    protected const extraData = "extraData";

    /**
     * @param EntityRepository<ProductCollection> $productRepository
     */
    public function __construct(
        protected HelloRetailClientService $client,
        protected EntityRepository $productRepository
    ) {
    }

    protected function getProducts(array $productData): mixed
    {
        $ids = $this->getIds($productData);
        if (!$ids) {
            return null;
        }

        $criteria = new Criteria($ids);
        return $this->productRepository->search($criteria, Context::createDefaultContext())->getEntities();
    }

    protected function getIds(array $productData, bool $group = true): array
    {
        $ids = [];
        $filteredGroups = [];
        foreach ($productData as $data) {
            if (isset($data[self::extraData]['displayGroup']) && isset($filteredGroups[$data[self::extraData]['displayGroup']])) {
                continue;
            }
            if (isset($data[self::extraData]['id'])) {
                $ids[] = $data[self::extraData]['id'];
                if (isset($data[self::extraData]['displayGroup'])) {
                    $filteredGroups[$data[self::extraData]['displayGroup']] = $data[self::extraData]['displayGroup'];
                }
            }
        }
        return $ids;
    }

    public function renderHierarchies(Entity $entity): array
    {
        $category = null;
        if ($entity::class == CategoryEntity::class) {
            $category = $entity;
        } else if ($entity::class == SalesChannelProductEntity::class) {
            $category = $entity->getSeoCategory();
        }

        if (!$category || !$category->getBreadcrumb()) {
            return [];
        }

        return $category->getBreadcrumb();
    }

    protected function renderUrls(SalesChannelContext $salesChannelContext = null): array
    {
        $urls = [];
        if ($salesChannelContext) {
            /** @var SalesChannelDomainEntity $domain */
            foreach ($salesChannelContext->getSalesChannel()->getDomains() as $domain) {
                $urls[] = $domain->getUrl();
            }
        }
        return $urls;
    }
}
