<?php

namespace SilverStripe\FeatureFlags\Forms;

use SilverStripe\Forms\SearchableMultiDropdownField;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObjectInterface;

/**
 * Searchable, lazy-loading multi-select for picking feature flag context records.
 *
 * Selections are persisted as FeatureSelectionItem records by FeatureSelection::saveContextFromForm(),
 * so this field doesn't save into the record directly.
 */
class ContextSelectionField extends SearchableMultiDropdownField
{
    public function __construct(
        string $name,
        ?string $title = null,
        ?DataList $source = null,
        $value = null,
        $labelField = 'Title'
    ) {
        parent::__construct($name, $title, $source, $value, $labelField);
        $this->setIsLazyLoaded(true);
        $this->setPlaceholder('Start typing to search...');
    }

    public function saveInto(DataObjectInterface $record): void
    {
        // Intentionally empty, see class docblock
    }
}
