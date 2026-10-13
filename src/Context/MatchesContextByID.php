<?php

namespace SilverStripe\FeatureFlags\Context;

use SilverStripe\FeatureFlags\FeatureSelection;
use SilverStripe\FeatureFlags\FeatureSelectionItem;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;

/**
 * Default FieldProvider behaviour: a form field named after the context key holds a list of record IDs,
 * stored as items with an empty ContextType, and the feature is enabled when the context record's ID
 * is one of them.
 */
trait MatchesContextByID
{
    abstract public function getKey(): string;

    public function loadFormData(DataList $items): array
    {
        return [
            $this->getKey() => FeatureSelectionItem::filterByType($items, '')->column('ContextID'),
        ];
    }

    public function saveFormData(FeatureSelection $selection, array $formData): void
    {
        $selection->syncContextItems($this->getKey(), '', $this->getIDsFromFormData($formData, $this->getKey()));
    }

    public function matchesContext(DataList $items, mixed $value): bool
    {
        if (!$value instanceof DataObject || !$value->ID) {
            return false;
        }

        return FeatureSelectionItem::filterByType($items, '')->filter(['ContextID' => $value->ID])->exists();
    }

    /**
     * @param array<string, mixed> $formData
     * @return int[]
     */
    protected function getIDsFromFormData(array $formData, string $fieldName): array
    {
        return array_values(array_filter(array_map('intval', (array) ($formData[$fieldName] ?? []))));
    }
}
