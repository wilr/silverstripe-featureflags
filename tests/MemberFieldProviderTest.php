<?php

namespace SilverStripe\FeatureFlags\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\FeatureFlags\Context\MemberFieldProvider;
use SilverStripe\FeatureFlags\FeatureSelectionItem;
use SilverStripe\Forms\SearchableMultiDropdownField;
use SilverStripe\Security\Group;

class MemberFieldProviderTest extends SapphireTest
{
    public function testKeyAccessors(): void
    {
        $provider = new MemberFieldProvider();
        $provider->setKey('Member');

        $this->assertSame('Member', $provider->getKey());
    }

    public function testGetCMSFieldsReturnsMemberAndGroupPickers(): void
    {
        $provider = new MemberFieldProvider();
        $provider->setKey('Member');

        $fields = $provider->getCMSFields();

        $this->assertCount(2, $fields);
        /** @var SearchableMultiDropdownField $members */
        $members = $fields->dataFieldByName('Member');
        $this->assertInstanceOf(SearchableMultiDropdownField::class, $members);
        $this->assertTrue($members->getIsLazyLoaded());
        $this->assertSame('Start typing to search...', $members->getPlaceholder());

        /** @var SearchableMultiDropdownField $groups */
        $groups = $fields->dataFieldByName('Member_Groups');
        $this->assertInstanceOf(SearchableMultiDropdownField::class, $groups);
        $this->assertTrue($groups->getIsLazyLoaded());
        $this->assertSame('Member groups', $groups->Title());
    }

    public function testMatchesContextRejectsNonMembers(): void
    {
        $provider = new MemberFieldProvider();
        $provider->setKey('Member');

        $items = FeatureSelectionItem::get();
        $this->assertFalse($provider->matchesContext($items, null));
        $this->assertFalse($provider->matchesContext($items, Group::create()));
    }
}
