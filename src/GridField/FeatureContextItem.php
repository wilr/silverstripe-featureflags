<?php

namespace SilverStripe\FeatureFlags\GridField;

use SilverStripe\FeatureFlags\FeatureSelection;
use SilverStripe\Forms\GridField\GridFieldDetailForm_ItemRequest;
use SilverStripe\ORM\DataObject;

/**
 * Subclass of GridFieldDetailForm_ItemRequest to override the save handler.
 * Needed so context field provider values are persisted alongside the record.
 */
class FeatureContextItem extends GridFieldDetailForm_ItemRequest
{
    protected function saveFormIntoRecord($data, $form): DataObject
    {
        if ($this->record instanceof FeatureSelection) {
            // Context selections are stored as FeatureSelectionItem records rather than as a relation
            // on the record, so save them here and exclude their fields from the standard form save
            $this->record->saveContextFromForm($form);
            foreach ($this->record->getContextFieldNames() as $fieldName) {
                $form->Fields()->removeByName($fieldName, true);
            }
        }

        return parent::saveFormIntoRecord($data, $form);
    }
}
