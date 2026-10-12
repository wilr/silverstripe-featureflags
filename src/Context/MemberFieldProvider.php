<?php

namespace SilverStripe\FeatureFlags\Context;

use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Security\Member;

class MemberFieldProvider implements FieldProvider
{
    private string $key = '';

    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getCMSFields(): FieldList
    {
        return FieldList::create(
            CheckboxSetField::create($this->key, $this->key, Member::get())
        );
    }

    public function convertItemsToFormData(array $items): array
    {
        return [
            $this->key => $items,
        ];
    }

    public function convertFormDataToItems(array $formData): array
    {
        return (array) ($formData[$this->key] ?? []);
    }
}
