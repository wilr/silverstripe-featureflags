<?php

namespace SilverStripe\FeatureFlags;

use LogicException;
use SilverStripe\FeatureFlags\Context\FieldProvider;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\SelectField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\HasManyList;
use SilverStripe\Security\Permission;

/**
 * @method HasManyList<FeatureSelectionItem> Items()
 */
class FeatureSelection extends DataObject
{
    private static string $table_name = 'FeatureSelection';

    private static string $singular_name = 'Feature flag';

    private static string $plural_name = 'Feature flags';

    private static array $db = [
        'Code' => 'Varchar(50)',
        'EnableMode' => 'Enum("Off, On, Partial", "Off")',
    ];

    private static array $indexes = [
        'Code' => true,
    ];

    private static array $has_many = [
        'Items' => FeatureSelectionItem::class,
    ];

    private static array $summary_fields = [
        'Code' => 'Code',
        'Title' => 'Title',
        'EnableMode' => 'Mode',
    ];

    public function getTitle(): string
    {
        $features = FeatureFlag::allFeatures();
        foreach ($features as $feature) {
            if (($feature['code'] ?? null) === $this->Code) {
                return (string) ($feature['title'] ?? $this->Code);
            }
        }

        return (string) $this->Code;
    }

    public function canView($member = null): bool
    {
        return Permission::checkMember($member, 'CMS_ACCESS_FeatureFlagAdmin');
    }

    public function canEdit($member = null): bool
    {
        return Permission::checkMember($member, 'CMS_ACCESS_FeatureFlagAdmin');
    }

    public function canCreate($member = null, $context = []): bool
    {
        return false;
    }

    public function canDelete($member = null): bool
    {
        return false;
    }

    public function getCMSFields(): FieldList
    {
        $fields = parent::getCMSFields();

        $fields->addFieldToTab('Root.Main', ReadonlyField::create('Code'), 'EnableMode');
        $fields->removeFieldFromTab('Root', 'Items');

        $fieldProviders = $this->getFieldProviders();
        foreach ($fieldProviders as $fieldProvider) {
            $this->addContextFields($fields, $fieldProvider);
        }

        // If there are no context field providers, Partial mode is not allowed
        if (!$fieldProviders) {
            $enableMode = $fields->dataFieldByName('EnableMode');
            if ($enableMode instanceof SelectField) {
                $enableMode->setSource([
                    'On' => 'On',
                    'Off' => 'Off',
                ]);
            }
        }

        return $fields;
    }

    public function saveContextFromForm(Form $form): void
    {
        foreach ($this->getFieldProviders() as $fieldProvider) {
            $this->saveContext($form, $fieldProvider);
        }
    }

    /**
     * Add fields for the given context key, using the field provider for the given class
     */
    protected function addContextFields(FieldList $fields, FieldProvider $fieldProvider): void
    {
        foreach ($fieldProvider->getCMSFields() as $field) {
            $fields->addFieldToTab('Root.Main', $field);
        }

        $ids = $this->Items()->filter(['ContextKey' => $fieldProvider->getKey()])->column('ContextID');
        $formData = $fieldProvider->convertItemsToFormData($ids);
        $fields->setValues($formData);
    }

    /**
     * Save context selections for the given field provider
     */
    protected function saveContext(Form $form, FieldProvider $fieldProvider): void
    {
        $items = $fieldProvider->convertFormDataToItems($form->getData());
        $key = $fieldProvider->getKey();

        // Remove bad items
        $badItems = $this->Items()->filter(['ContextKey' => $key]);
        if ($items) {
            $badItems = $badItems->filter(['ContextID:not' => $items]);
        }

        foreach ($badItems as $item) {
            $item->delete();
        }

        if ($items) {
            // Identify existing items to neither add nor delete
            $existingItems = $this->Items()
                ->filter(['ContextKey' => $key, 'ContextID' => $items])
                ->column('ContextID');

            // Add new items
            foreach (array_diff($items, $existingItems) as $itemID) {
                $item = FeatureSelectionItem::create();
                $item->ContextKey = $key;
                $item->ContextID = $itemID;
                $this->Items()->add($item);
            }
        }
    }

    /**
     * Return the FieldProvider instances for selecting the context of this feature flag
     *
     * @return FieldProvider[]
     */
    protected function getFieldProviders(): array
    {
        $feature = FeatureFlag::getFeature((string) $this->Code);
        if (empty($feature['context'])) {
            return [];
        }

        $fieldProviderMap = FeatureFlagAdmin::config()->get('context_field_providers') ?? [];
        $fieldProviders = [];

        foreach ($feature['context'] as $key => $className) {
            if (empty($fieldProviderMap[$className])) {
                throw new LogicException('Can\'t find context field provider for ' . $className);
            }
            $fieldProviderClass = $fieldProviderMap[$className];
            /** @var FieldProvider $fieldProvider */
            $fieldProvider = new $fieldProviderClass();
            $fieldProvider->setKey($key);
            $fieldProviders[] = $fieldProvider;
        }

        return $fieldProviders;
    }

    public function requireDefaultRecords(): void
    {
        parent::requireDefaultRecords();

        $features = FeatureFlag::allFeatures();
        foreach ($features as $feature) {
            $code = $feature['code'] ?? null;
            if (!$code) {
                continue;
            }
            if (FeatureSelection::get()->filter(['Code' => $code])->count() === 0) {
                $selection = FeatureSelection::create();
                $selection->Code = $code;
                $selection->write();
                DB::alteration_message('Adding feature "' . $code . '"', 'changed');
            }
        }
    }
}
