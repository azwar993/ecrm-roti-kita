<?php

namespace App\Services;

use App\Models\Promotion;

class PromotionDiscountCalculator
{
    public function discountCents(Promotion $promotion, int $subtotalCents, StoreCartService $money): int
    {
        $discount = $promotion->discount_type === Promotion::TYPE_PERCENTAGE
            ? (int) round($subtotalCents * (float) $promotion->discount_value / 100)
            : $money->toCents($promotion->discount_value);
        if ($promotion->max_discount_amount !== null) {
            $discount = min($discount, $money->toCents($promotion->max_discount_amount));
        }
        return min(max(0, $discount), $subtotalCents);
    }
}
