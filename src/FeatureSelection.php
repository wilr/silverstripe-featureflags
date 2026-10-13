<?php

namespace SilverStripe\FeatureFlags;

use LogicException;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\FeatureFlags\Context\FieldProvider;
use SilverStripe\Forms\DatetimeField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\SelectField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\ORM\HasManyList;
use SilverStripe\Security\Permission;

/**
 * @property string $Code
 * @property string $EnableMode
 * @property string|null $ScheduledMode
 * @property string|null $ScheduledAt
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
        // Mode to switch to once ScheduledAt has passed. Empty means no scheduled change.
        'ScheduledMode' => 'Varchar(10)',
        'ScheduledAt' => 'Datetime',
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
        'ScheduleSummary' => 'Scheduled change',
    ];

    /**
     * @var array<int, string>
     */
    private static array $enable_modes = ['On', 'Off', 'Partial'];

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

    /**
     * Returns true if a scheduled mode change has been set and its time has passed
     */
    public function isScheduleDue(): bool
    {
        if (!$this->ScheduledMode || !$this->ScheduledAt) {
            return false;
        }

        $scheduledAt = $this->dbObject('ScheduledAt')->getTimestamp();

        return $scheduledAt <= DBDatetime::now()->getTimestamp();
    }

    /**
     * The mode currently in effect, taking any due scheduled change into account
     */
    public function getEffectiveMode(): string
    {
        return $this->isScheduleDue() ? (string) $this->ScheduledMode : (string) $this->EnableMode;
    }

    /**
     * Persist a due scheduled change: copies ScheduledMode into EnableMode and clears the schedule.
     *
     * @return bool true if the record was changed
     */
    public function applySchedule(): bool
    {
        if (!$this->isScheduleDue()) {
            return false;
        }

        $this->EnableMode = $this->ScheduledMode;
        $this->ScheduledMode = null;
        $this->ScheduledAt = null;
        $this->write();

        return true;
    }

    public function getScheduleSummary(): string
    {
        if (!$this->ScheduledMode || !$this->ScheduledAt) {
            return '';
        }

        return $this->ScheduledMode . ' at ' . $this->dbObject('ScheduledAt')->Nice();
    }

    public function validate(): ValidationResult
    {
        $result = parent::validate();

        if ($this->ScheduledMode && !in_array($this->ScheduledMode, static::config()->get('enable_modes'), true)) {
            $result->addFieldError('ScheduledMode', 'Invalid scheduled mode');
        }

        if ($this->ScheduledMode && !$this->ScheduledAt) {
            $result->addFieldError('ScheduledAt', 'Please choose when the scheduled change should happen');
        }

        return $result;
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
        $fields->removeByName(['ScheduledMode', 'ScheduledAt']);

        $fieldProviders = $this->getFieldProviders();
        foreach ($fieldProviders as $fieldProvider) {
            $this->addContextFields($fields, $fieldProvider);
        }

        // If there are no context field providers, Partial mode is not allowed
        $modes = ['On' => 'On', 'Off' => 'Off'];
        if ($fieldProviders) {
            $modes['Partial'] = 'Partial';
        } else {
            $enableMode = $fields->dataFieldByName('EnableMode');
            if ($enableMode instanceof SelectField) {
                $enableMode->setSource($modes);
            }
        }

        $fields->addFieldsToTab('Root.Schedule', [
            DropdownField::create('ScheduledMode', 'Change mode to', $modes)
                ->setEmptyString('No scheduled change')
                ->setDescription(
                    'Automatically switch this feature to the selected mode at the given time.'
                    . ' The change takes effect as soon as the time passes; no cron job is required.'
                ),
            DatetimeField::create('ScheduledAt', 'Change at'),
        ]);

        return $fields;
    }

    /**
     * Names of the form fields used to select context for this feature
     *
     * @return string[]
     */
    public function getContextFieldNames(): array
    {
        $names = [];
        foreach ($this->getFieldProviders() as $fieldProvider) {
            foreach ($fieldProvider->getCMSFields()->dataFields() as $field) {
                $names[] = $field->getName();
            }
        }

        return $names;
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

        $items = $this->Items()->filter(['ContextKey' => $fieldProvider->getKey()]);
        $fields->setValues($fieldProvider->loadFormData($items));
    }

    /**
     * Save context selections for the given field provider
     */
    protected function saveContext(Form $form, FieldProvider $fieldProvider): void
    {
        $fieldProvider->saveFormData($this, $form->getData());
    }

    /**
     * Replace the items of the given context key and type with the given IDs,
     * leaving unchanged items in place
     *
     * @param int[] $ids
     */
    public function syncContextItems(string $key, string $type, array $ids): void
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $existing = FeatureSelectionItem::filterByType($this->Items()->filter(['ContextKey' => $key]), $type);

        foreach ($existing as $item) {
            if (!in_array((int) $item->ContextID, $ids, true)) {
                $item->delete();
            }
        }

        $existingIDs = array_map('intval', $existing->column('ContextID'));
        foreach (array_diff($ids, $existingIDs) as $id) {
            $item = FeatureSelectionItem::create();
            $item->ContextKey = $key;
            $item->ContextType = $type;
            $item->ContextID = $id;
            $this->Items()->add($item);
        }
    }

    /**
     * The field provider for the given context key, or null if the feature doesn't define that key
     */
    public function getFieldProvider(string $key): ?FieldProvider
    {
        foreach ($this->getFieldProviders() as $fieldProvider) {
            if ($fieldProvider->getKey() === $key) {
                return $fieldProvider;
            }
        }

        return null;
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
