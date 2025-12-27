<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadProduct;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class LeadProductService
{
    /**
     * Sync lead products (create/update/delete)
     * 
     * @param Lead $lead
     * @param array $productsData Array of product data
     * @param int $companyId
     * @param int $authUserId
     * @param bool $sync If true, delete products not present in the data array
     * @return array Array of LeadProduct objects
     */
    public function syncLeadProducts(Lead $lead, array $productsData, int $companyId, int $authUserId, bool $sync = true)
    {
        $existingProductIds = $lead->products->pluck('id')->toArray();
        $updatedProducts = [];
        $updatedProductIds = [];

        foreach ($productsData as $data) {
            $leadProductId = $data['lead_product_id'] ?? null;
            $leadProduct = null;
            
            // Check if this is an existing product for this lead
            if ($leadProductId && in_array($leadProductId, $existingProductIds)) {
                // Update existing
                $existingLeadProduct = LeadProduct::where('id', $leadProductId)
                    ->where('lead_id', $lead->id)
                    ->where('company_id', $companyId)
                    ->first();
                
                if ($existingLeadProduct) {
                    $this->updateSingleLeadProduct($existingLeadProduct, $data, $companyId, $authUserId);
                    $leadProduct = $existingLeadProduct;
                }
            } else {
                // Create new
                $leadProduct = $this->createSingleLeadProduct($lead, $data, $companyId, $authUserId);
            }

            if ($leadProduct) {
                $updatedProducts[] = $leadProduct;
                $updatedProductIds[] = $leadProduct->id;
            } else {
                $updatedProducts[] = null;
            }
        }

        // Delete missing products if sync is enabled
        if ($sync) {
            $productsToDelete = array_diff($existingProductIds, $updatedProductIds);
            if (!empty($productsToDelete)) {
                LeadProduct::whereIn('id', $productsToDelete)
                    ->where('lead_id', $lead->id)
                    ->where('company_id', $companyId)
                    ->delete();
            }
        }

        return $updatedProducts;
    }

    /**
     * Create or update a single lead product logic
     */
    private function updateSingleLeadProduct(LeadProduct $leadProduct, array $data, int $companyId, int $authUserId)
    {
        $category = $leadProduct->type ?? $data['category'] ?? null;
        $productId = $data['product_id'] ?? null;
        $productSubCategoryId = $data['product_sub_category_id'] ?? null;

        $resolvedIds = $this->resolveProductIds($category, $productId, $productSubCategoryId, $companyId);
        
        // Update fields
        if (isset($data['category'])) $leadProduct->type = $data['category'];
        $leadProduct->product_id = $resolvedIds['product_id'];
        $leadProduct->property_unit_id = $data['property_unit_id'] ?? null;
        $leadProduct->area_id = $data['area_id'] ?? null;
        $leadProduct->product_category_id = $data['property_id'] ?? $resolvedIds['service_category_id'] ?? null;
        $leadProduct->product_sub_category_id = $resolvedIds['product_sub_category_id'];
        
        // Always update these if present, fallback to existing
        $leadProduct->quantity = isset($data['quantity']) ? (int)$data['quantity'] : ($leadProduct->quantity ?? 1);
        $leadProduct->other_price = isset($data['other_price']) ? (float)$data['other_price'] : ($leadProduct->other_price ?? 0);
        $leadProduct->discount = isset($data['discount']) ? (float)$data['discount'] : ($leadProduct->discount ?? 0);
        
        if (isset($data['negotiated_price']) || isset($data['negotiation_price'])) {
            $leadProduct->negotiated_price = $data['negotiated_price'] ?? $data['negotiation_price'] ?? null;
        }
        
        if (array_key_exists('notes', $data)) {
            $leadProduct->notes = $data['notes'];
        }

        $leadProduct->updated_by = $authUserId;
        $leadProduct->save();

        return $leadProduct;
    }

    private function createSingleLeadProduct(Lead $lead, array $data, int $companyId, int $authUserId)
    {
        $category = $data['category'] ?? null;
        if (!$category) return null;

        $productId = $data['product_id'] ?? null;
        $productSubCategoryId = $data['product_sub_category_id'] ?? null;

        // Resolve unit_id and layout_id aliases if present (for SalesController compatibility)
        if ($category === 'Property') {
            if (!empty($data['unit_id'])) {
                $productId = $data['unit_id'];
            }
            if (!empty($data['layout_id'])) {
                $productSubCategoryId = $data['layout_id'];
            }
        } elseif ($category === 'Service') {
             if (!empty($data['service_id'])) {
                $productId = $data['service_id'];
            }
        }

        $resolvedIds = $this->resolveProductIds($category, $productId, $productSubCategoryId, $companyId);
        
        // Validate Service category requirement
        if ($category === 'Service' && empty($resolvedIds['product_id'])) {
            // Service must have a product ID
            return null; // Or throw exception
        }

        $leadProduct = LeadProduct::create([
            'company_id' => $companyId,
            'lead_id' => $lead->id,
            'type' => $category,
            'property_unit_id' => $data['property_unit_id'] ?? null,
            'area_id' => $data['area_id'] ?? null,
            'product_category_id' => $data['property_id'] ?? $resolvedIds['service_category_id'] ?? null,
            'product_sub_category_id' => $resolvedIds['product_sub_category_id'],
            'product_id' => $resolvedIds['product_id'],
            'quantity' => $data['quantity'] ?? 1,
            'other_price' => $data['other_price'] ?? 0,
            'discount' => $data['discount'] ?? 0,
            'negotiated_price' => $data['negotiated_price'] ?? $data['negotiation_price'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $authUserId,
        ]);

        return $leadProduct;
    }

    private function resolveProductIds($category, $productId, $productSubCategoryId, $companyId)
    {
        $serviceCategoryId = null;
        
        // Handle Property category logic
        if (empty($productId) && !empty($productSubCategoryId) && $category === 'Property') {
            $product = Product::where('sub_category_id', $productSubCategoryId)
                ->where('company_id', $companyId)
                ->where('applies_to', 'property')
                ->first();
            
            if ($product) {
                $productId = $product->id;
            } 
        }

        // Handle Service category logic or existing Product logic
        if ($productId) {
            $product = Product::where('id', $productId)
                ->where('company_id', $companyId)
                ->first();
            
            if ($product) {
                if ($category === 'Service') {
                    $serviceCategoryId = $product->category_id ?? null;
                    if (!$productSubCategoryId) {
                        $productSubCategoryId = $product->sub_category_id ?? null;
                    }
                } elseif ($category === 'Property' && empty($productSubCategoryId)) {
                     $productSubCategoryId = $product->sub_category_id ?? null;
                }
            }
        }

        return [
            'product_id' => $productId,
            'product_sub_category_id' => $productSubCategoryId,
            'service_category_id' => $serviceCategoryId
        ];
    }
}
