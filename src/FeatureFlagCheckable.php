<?php

namespace SilverStripe\FeatureFlags;

/**
 * Interface defining a provider for feature flag checks
 */
interface FeatureFlagCheckable
{
    public function isEnabled(string $code, array $context = []): bool;
}
