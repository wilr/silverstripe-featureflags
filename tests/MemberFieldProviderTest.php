<?php

namespace SilverStripe\FeatureFlags\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\FeatureFlags\Context\MemberFieldProvider;
use SilverStripe\Forms\SearchableMultiDropdownField;

class MemberFieldProviderTest extends SapphireTest
{
    public function testKeyAccessors(): void
    {
        $provider = new MemberFieldProvider();
        $provider->setKey('Member');

        $this->assertSame('Member', $provider->getKey());
    }

    public function testGetCMSFieldsReturnsLazyLoadedSearchableField(): void
    {
        $provider = new MemberFieldProvider();
        $provider->setKey('Member');

        $fields = $provider->getCMSFields();

        $this->assertCount(1, $fields);
        /** @var SearchableMultiDropdownField $field */
        $field = $fields->first();
        $this->assertInstanceOf(SearchableMultiDropdownField::class, $field);
        $this->assertSame('Member', $field->getName());
        $this->assertTrue($field->getIsLazyLoaded());
        $this->assertSame('Start typing to search...', $field->getPlaceholder());
    }

    public function testFieldValueConvertsToItems(): void
    {
        $provider = new MemberFieldProvider();
        $provider->setKey('Member');

        /** @var SearchableMultiDropdownField $field */
        $field = $provider->getCMSFields()->first();
        $field->setValue(['3', '5']);

        $this->assertSame([3, 5], $provider->convertFormDataToItems(['Member' => $field->dataValue()]));
    }

    public function testConvertItemsAndFormDataRoundTrip(): void
    {
        $provider = new MemberFieldProvider();
        $provider->setKey('Member');

        $items = [1, 2, 3];
        $formData = $provider->convertItemsToFormData($items);

        $this->assertSame(['Member' => $items], $formData);
        $this->assertSame($items, $provider->convertFormDataToItems($formData));
        $this->assertSame([2, 4], $provider->convertFormDataToItems(['Member' => ['2', '', '4']]));
        $this->assertSame([], $provider->convertFormDataToItems([]));
    }
}
