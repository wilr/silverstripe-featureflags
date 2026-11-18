<?php

namespace SilverStripe\FeatureFlags\Context;

use SilverStripe\FeatureFlags\FeatureSelection;
use SilverStripe\FeatureFlags\FeatureSelectionItem;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\SearchableMultiDropdownField;
use SilverStripe\ORM\DataList;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;

/**
 * Enables a feature for selected members, or for all members of selected groups
 * (including members of child groups).
 */
class MemberFieldProvider extends DataObjectFieldProvider
{
    public const string GROUP_TYPE = 'Group';

    protected function getDataClass(): string
    {
        return Member::class;
    }

    protected function getGroupFieldName(): string
    {
        return $this->getKey() . '_Groups';
    }

    public function getCMSFields(): FieldList
    {
        $fields = parent::getCMSFields();

        $groups = SearchableMultiDropdownField::create(
            $this->getGroupFieldName(),
            _t(__CLASS__ . '.GROUPS', '{key} groups', ['key' => $this->getKey()]),
            Group::get()
        );
        $groups->setIsLazyLoaded(true);
        $groups->setPlaceholder(
            _t(DataObjectFieldProvider::class . '.SEARCH_PLACEHOLDER', 'Start typing to search...')
        );
        $fields->push($groups);

        return $fields;
    }

    public function loadFormData(DataList $items): array
    {
        $data = parent::loadFormData($items);
        $data[$this->getGroupFieldName()] = FeatureSelectionItem::filterByType($items, static::GROUP_TYPE)
            ->column('ContextID');

        return $data;
    }

    public function saveFormData(FeatureSelection $selection, array $formData): void
    {
        parent::saveFormData($selection, $formData);

        $selection->syncContextItems(
            $this->getKey(),
            static::GROUP_TYPE,
            $this->getIDsFromFormData($formData, $this->getGroupFieldName())
        );
    }

    public function matchesContext(DataList $items, mixed $value): bool
    {
        if (parent::matchesContext($items, $value)) {
            return true;
        }

        if (!$value instanceof Member || !$value->ID) {
            return false;
        }

        $groupIDs = FeatureSelectionItem::filterByType($items, static::GROUP_TYPE)->column('ContextID');
        if (!$groupIDs) {
            return false;
        }

        // Member::Groups() includes ancestors of the member's groups, so this also matches child groups
        return $value->Groups()->filter(['ID' => $groupIDs])->exists();
    }
}
