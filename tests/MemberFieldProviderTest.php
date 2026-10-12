<?php

namespace SilverStripe\FeatureFlags\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\FeatureFlags\Context\MemberFieldProvider;
use SilverStripe\Forms\CheckboxSetField;

class MemberFieldProviderTest extends SapphireTest
{
    public function testKeyAccessors(): void
    {
        $provider = new MemberFieldProvider();
        $provider->setKey('Member');

        $this->assertSame('Member', $provider->getKey());
    }

    public function testGetCMSFieldsReturnsCheckboxSet(): void
    {
        $provider = new MemberFieldProvider();
        $provider->setKey('Member');

        $fields = $provider->getCMSFields();

        $this->assertCount(1, $fields);
        $this->assertInstanceOf(CheckboxSetField::class, $fields->first());
        $this->assertSame('Member', $fields->first()->getName());
    }

    public function testConvertItemsAndFormDataRoundTrip(): void
    {
        $provider = new MemberFieldProvider();
        $provider->setKey('Member');

        $items = [1, 2, 3];
        $formData = $provider->convertItemsToFormData($items);

        $this->assertSame(['Member' => $items], $formData);
        $this->assertSame($items, $provider->convertFormDataToItems($formData));
        $this->assertSame([], $provider->convertFormDataToItems([]));
    }
}
