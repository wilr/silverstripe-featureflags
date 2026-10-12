<?php

namespace SilverStripe\FeatureFlags;

use SilverStripe\ORM\DataObject;

/**
 * @method FeatureSelection FeatureSelection()
 */
class FeatureSelectionItem extends DataObject
{
    private static string $table_name = 'FeatureSelectionItem';

    private static array $db = [
        'ContextKey' => 'Varchar(50)',
        'ContextID' => 'Int',
    ];

    private static array $has_one = [
        'FeatureSelection' => FeatureSelection::class,
    ];
}
