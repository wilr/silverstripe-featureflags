# Feature Flags

Admin-controlled feature flags for Silverstripe CMS 6.

## Requirements

- PHP 8.3+
- Silverstripe Framework ^6
- Silverstripe Admin ^3

## Installation

```bash
composer require wilr/silverstripe-featureflags
```

## Usage

Use `SilverStripe\FeatureFlags\FeatureFlag::isEnabled` to determine if a flag is set.

### Making code feature-dependent

```php
use SilverStripe\FeatureFlags\FeatureFlag;
use SilverStripe\Security\Security;

if (FeatureFlag::isEnabled('FEATURE_NAME', ['Member' => Security::getCurrentUser()])) {
    $controller->SomeSetting = 'some value';
}
```

The first argument is a feature codename. These work similarly to permission codenames. The second argument is a map of context data, described below.

The module doesn't provide any controls for *how* your application will change if the flag is enabled; that is up to you.

### Context

Context helps answer the question "when should this feature be shown?" For example, you may have a feature that you wish to enable for certain members. In other cases, you may have a feature that you wish to enable for certain projects or pages.

In the first example, `Member` would be required context. In the second example, `Project` or `SiteTree` would be.

Each feature definition (described below) includes a list of the context it expects. Based on this, the admin UI for controlling feature flags will provide different tools for selecting features.

### Defining new features

To define new features, add items to `SilverStripe\FeatureFlags\FeatureFlag.features` in your config:

```yml
SilverStripe\FeatureFlags\FeatureFlag:
  features:
    - code: FEATURE_NAME
      title: My feature
      context:
        Member: SilverStripe\Security\Member
```

Each feature has the following options:

- **code:** Codename passed as the first argument to `FeatureFlag::isEnabled()`. By convention, use `UPPER_SNAKE_CASE`.
- **title:** Title shown in the feature flag admin.
- **context:** Map of required context values. Keys are context keys; values are the class the value must derive from. Scalar context is not allowed.

### Feature admin

Your CMS UI will have a new section at `/admin/featureflags`. This ModelAdmin lists each available feature and gives form controls for choosing which context values enable the feature.

### Scheduling changes

Each feature flag has a **Schedule** tab in the admin. Use it to switch a flag to a different mode (On, Off or Partial) at a set date and time. This lets you release features out of hours without anyone at the keyboard.

A scheduled change takes effect as soon as its time passes: `FeatureFlag::isEnabled()` checks the schedule on every call, so you don't need a cron job. The next time the feature flag admin is opened, the due change is saved as the flag's mode and the schedule is cleared.

You can also save due changes from a cron job, which keeps the stored mode accurate even if nobody opens the admin:

```bash
vendor/bin/sake tasks:apply-feature-flag-schedules
```

### Context field providers

For each data type in the context, you need a form control for editing it. These are provided by "context field providers". By default, a provider is included for `SilverStripe\Security\Member`. It lets you pick individual members and/or groups; a group enables the feature for all of its members, including members of child groups.

Records are chosen with a searchable picker that loads results as you type. It stays usable with thousands of members or other records.

To add your own for a DataObject, extend `SilverStripe\FeatureFlags\Context\DataObjectFieldProvider`:

```php
namespace App\FeatureFlags;

use App\Model\Project;
use SilverStripe\FeatureFlags\Context\DataObjectFieldProvider;

class ProjectFieldProvider extends DataObjectFieldProvider
{
    protected function getDataClass(): string
    {
        return Project::class;
    }
}
```

Override `getSourceList()` to limit which records can be selected, or `getLabelField()` to change the label shown for each option.

#### Custom matching

A field provider controls how selections are saved and how they're matched against context at check time. It does this through three `FieldProvider` methods:

- `loadFormData(DataList $items)`: turns the saved items for its key into form data.
- `saveFormData(FeatureSelection $selection, array $formData)`: saves the submitted form data, usually via `$selection->syncContextItems($key, $type, $ids)`.
- `matchesContext(DataList $items, mixed $value)`: returns whether the saved items enable the feature for the given context value.

Each saved `FeatureSelectionItem` has a `ContextKey`, a `ContextType` and a `ContextID`. Use `ContextType` to store different kinds of selection for one key. For example, `MemberFieldProvider` saves groups as `ContextKey = Member, ContextType = Group, ContextID = 5`, and checks them with a single query instead of listing every member of the group.

The `MatchesContextByID` trait provides the default behaviour, where a field named after the key holds record IDs. `DataObjectFieldProvider` uses this trait; extend it and override the methods above to add other kinds of selection.

Then attach your provider via the `context_field_providers` config setting:

```yml
SilverStripe\FeatureFlags\FeatureFlagAdmin:
  context_field_providers:
    SilverStripe\Security\Member: SilverStripe\FeatureFlags\Context\MemberFieldProvider
    App\Model\Project: App\FeatureFlags\ProjectFieldProvider
```

## Development

```bash
composer install
composer lint
vendor/bin/phpunit
```

CI runs via [`silverstripe/gha-ci`](https://github.com/silverstripe/gha-ci) and covers PHPUnit, PHPCS, and PHPStan when the corresponding config files are present.

## Versioning

| Module version | Silverstripe CMS |
|----------------|------------------|
| `~0.1`         | 3                |
| `~0.2`         | 4                |
| `^1`           | 6                |

## License

BSD-3-Clause
