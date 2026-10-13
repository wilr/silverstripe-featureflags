<?php

namespace SilverStripe\FeatureFlags\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\FeatureFlags\Context\MemberFieldProvider;
use SilverStripe\FeatureFlags\FeatureSelection;
use SilverStripe\FeatureFlags\Forms\ContextSelectionField;
use SilverStripe\Security\Member;

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
        /** @var ContextSelectionField $field */
        $field = $fields->first();
        $this->assertInstanceOf(ContextSelectionField::class, $field);
        $this->assertSame('Member', $field->getName());
        $this->assertTrue($field->getIsLazyLoaded());
        $this->assertSame(Member::class, $field->getSourceList()->dataClass());
    }

    public function testFieldValueAndSaveInto(): void
    {
        $provider = new MemberFieldProvider();
        $provider->setKey('Member');

        /** @var ContextSelectionField $field */
        $field = $provider->getCMSFields()->first();
        $field->setValue(['3', '5']);

        $this->assertSame([3, 5], $field->dataValue());

        // Must not throw, even though FeatureSelection has no "Member" relation
        $record = FeatureSelection::create();
        $field->saveInto($record);
        $this->assertFalse($record->isChanged());
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
