<?php

namespace SilverStripe\FeatureFlags\Context;

use SilverStripe\FeatureFlags\FeatureSelection;
use SilverStripe\FeatureFlags\FeatureSelectionItem;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataList;

/**
 * Provides the CMS UI for one context key of a feature, and owns how selections for that key
 * are stored (as FeatureSelectionItem records) and matched against context at check time.
 *
 * Each FeatureSelectionItem has a ContextKey, a ContextType and a ContextID. Providers can use
 * ContextType to store different kinds of selection for the same key, e.g. ContextKey = Member,
 * ContextType = Group, ContextID = 5 to enable a feature for all members of a group.
 *
 * Use the MatchesContextByID trait for the default behaviour of matching a record by ID.
 */
interface FieldProvider
{
    public function getKey(): string;

    public function setKey(string $key): void;

    public function getCMSFields(): FieldList;

    /**
     * Convert stored selections into form data for the fields returned by getCMSFields()
     *
     * @param DataList<FeatureSelectionItem> $items Items for this provider's key
     * @return array<string, mixed>
     */
    public function loadFormData(DataList $items): array;

    /**
     * Persist submitted form data as FeatureSelectionItem records on the given selection
     *
     * @param array<string, mixed> $formData
     */
    public function saveFormData(FeatureSelection $selection, array $formData): void;

    /**
     * Whether the stored selections enable the feature for the given context value
     *
     * @param DataList<FeatureSelectionItem> $items Items for this provider's key
     */
    public function matchesContext(DataList $items, mixed $value): bool;
}
