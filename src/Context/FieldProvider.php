<?php

namespace SilverStripe\FeatureFlags\Context;

use SilverStripe\Forms\FieldList;

/**
 * Interface for new context UIs
 */
interface FieldProvider
{
    public function getKey(): string;

    public function setKey(string $key): void;

    public function getCMSFields(): FieldList;

    /**
     * @param array<int|string> $items
     * @return array<string, mixed>
     */
    public function convertItemsToFormData(array $items): array;

    /**
     * @param array<string, mixed> $formData
     * @return array<int|string>
     */
    public function convertFormDataToItems(array $formData): array;
}
