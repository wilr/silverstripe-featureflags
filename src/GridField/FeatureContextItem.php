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
        $record = parent::saveFormIntoRecord($data, $form);

        if ($record instanceof FeatureSelection) {
            $record->saveContextFromForm($form);
        }

        return $record;
    }
}
