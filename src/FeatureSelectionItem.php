<?php

namespace SilverStripe\FeatureFlags;

use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;

/**
 * A single context selection for a feature. How items are created and matched is up to the
 * FieldProvider for the ContextKey; ContextType lets a provider store different kinds of selection
 * for the same key (an empty type is the default "match by ID" behaviour).
 *
 * @property string $ContextKey
 * @property string|null $ContextType
 * @property int $ContextID
 * @method FeatureSelection FeatureSelection()
 */
class FeatureSelectionItem extends DataObject
{
    private static string $table_name = 'FeatureSelectionItem';

    private static array $db = [
        'ContextKey' => 'Varchar(50)',
        'ContextType' => 'Varchar(255)',
        'ContextID' => 'Int',
    ];

    private static array $indexes = [
        'Context' => ['ContextKey', 'ContextType', 'ContextID'],
    ];

    private static array $has_one = [
        'FeatureSelection' => FeatureSelection::class,
    ];

    /**
     * Filter items to the given ContextType. An empty type also matches items saved before
     * ContextType existed (NULL).
     *
     * @param DataList<FeatureSelectionItem> $items
     * @return DataList<FeatureSelectionItem>
     */
    public static function filterByType(DataList $items, string $type): DataList
    {
        return $items->filter(['ContextType' => $type === '' ? ['', null] : $type]);
    }
}
