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

        // Check each context value against the selections, using the field provider for that key
        foreach ($context as $key => $value) {
            $fieldProvider = $feature->getFieldProvider((string) $key);
            if (!$fieldProvider) {
                continue;
            }

            $items = $feature->Items()->filter(['ContextKey' => $key]);

            // Any context match will result in the feature being enabled
            if ($fieldProvider->matchesContext($items, $value)) {
                return true;
            }
        }

        return false;
    }
}
