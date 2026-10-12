<?php

namespace SilverStripe\FeatureFlags;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;

/**
 * Class for interacting with the available feature flags
 */
class FeatureFlag
{
    use Configurable;
    use Injectable;

    /**
     * @var array<int, array{code: string, title: string, context?: array<string, class-string>}>
     */
    private static array $features = [];

    public static function isEnabled(string $code, array $context = []): bool
    {
        return Injector::inst()->get(FeatureFlagCheckable::class)->isEnabled($code, $context);
    }

    /**
     * @return array<int, array{code: string, title: string, context?: array<string, class-string>}>
     */
    public static function allFeatures(): array
    {
        return (array) static::config()->get('features');
    }

    /**
     * @return array{code: string, title: string, context?: array<string, class-string>}|null
     */
    public static function getFeature(string $code): ?array
    {
        foreach (FeatureFlag::allFeatures() as $feature) {
            if (($feature['code'] ?? null) === $code) {
                return $feature;
            }
        }

        return null;
    }
}
