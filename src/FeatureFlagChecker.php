<?php

namespace SilverStripe\FeatureFlags;

/**
 * Default implementation of FeatureFlagCheckable.
 * Uses the FeatureSelection / FeatureSelectionItem data objects
 */
class FeatureFlagChecker implements FeatureFlagCheckable
{
    public function isEnabled(string $code, array $context = []): bool
    {
        $feature = FeatureSelection::get()->filter(['Code' => $code])->first();

        if (!$feature) {
            return false;
        }

        // Takes any scheduled mode change which is now due into account
        $mode = $feature->getEffectiveMode();

        // Simple modes
        if ($mode === 'On') {
            return true;
        }
        if ($mode === 'Off') {
            return false;
        }

        // Check each context value against the selections
        foreach ($context as $key => $obj) {
            $contextTest = $feature->Items()->filter([
                'ContextKey' => $key,
                'ContextID' => $obj ? $obj->ID : 0,
            ]);

            // Any context match will result in the feature being enabled
            if ($contextTest->count() > 0) {
                return true;
            }
        }

        return false;
    }
}
