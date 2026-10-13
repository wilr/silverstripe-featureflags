<?php

namespace SilverStripe\FeatureFlags\Context;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\SearchableMultiDropdownField;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;

/**
 * Base field provider for selecting any DataObject as context.
 *
 * Uses a searchable, lazy-loading picker so it scales to large numbers of records.
 * Subclasses only need to define getDataClass(), and optionally getSourceList() or getLabelField().
 */
abstract class DataObjectFieldProvider implements FieldProvider
{
    private string $key = '';

    /**
     * @return class-string<DataObject>
     */
    abstract protected function getDataClass(): string;

    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Records available for selection
     */
    protected function getSourceList(): DataList
    {
        return DataList::create($this->getDataClass());
    }

    /**
     * Field (or getter) used for the label of each option
     */
    protected function getLabelField(): string
    {
        return 'Title';
    }

    public function getCMSFields(): FieldList
    {
        $field = SearchableMultiDropdownField::create(
            $this->key,
            $this->key,
            $this->getSourceList(),
            null,
            $this->getLabelField()
        );
        $field->setIsLazyLoaded(true);
        $field->setPlaceholder(
            _t(__CLASS__ . '.SEARCH_PLACEHOLDER', 'Start typing to search...')
        );

        return FieldList::create($field);
    }

    public function convertItemsToFormData(array $items): array
    {
        return [
            $this->key => array_map('intval', $items),
        ];
    }

    public function convertFormDataToItems(array $formData): array
    {
        return array_values(array_filter(array_map('intval', (array) ($formData[$this->key] ?? []))));
    }
}
