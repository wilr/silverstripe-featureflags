<?php

namespace SilverStripe\FeatureFlags;

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\FeatureFlags\GridField\FeatureContextItem;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\ORM\SS_List;

class FeatureFlagAdmin extends ModelAdmin
{
    private static array $managed_models = [
        FeatureSelection::class,
    ];

    private static string $url_segment = 'featureflags';

    private static string $menu_title = 'Feature Flags';

    /**
     * Map of context class => field provider class
     *
     * @var array<class-string, class-string>
     */
    private static array $context_field_providers = [];

    public function getList(): SS_List
    {
        // Persist any scheduled changes that are due so the CMS reflects the mode in effect
        if ($this->modelClass === FeatureSelection::class) {
            foreach (FeatureSelection::get()->exclude(['ScheduledAt' => null]) as $selection) {
                $selection->applySchedule();
            }
        }

        return parent::getList();
    }

    public function getEditForm($id = null, $fields = null): Form
    {
        $form = parent::getEditForm($id, $fields);

        /** @var GridField|null $gridField */
        $gridField = $form->Fields()->dataFieldByName($this->sanitiseClassName(FeatureSelection::class));
        $detailForm = $gridField?->getConfig()->getComponentByType(GridFieldDetailForm::class);
        if ($detailForm instanceof GridFieldDetailForm) {
            $detailForm->setItemRequestClass(FeatureContextItem::class);
        }

        return $form;
    }
}
