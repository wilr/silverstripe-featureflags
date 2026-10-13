<?php

namespace SilverStripe\FeatureFlags\Tests;

use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\FeatureFlags\FeatureFlag;
use SilverStripe\FeatureFlags\FeatureSelection;
use SilverStripe\FeatureFlags\FeatureSelectionItem;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Security\Member;

class FeatureFlagTest extends SapphireTest
{
    protected static $fixture_file = 'FeatureFlagTest.yml';

    protected function setUp(): void
    {
        parent::setUp();

        Config::modify()->set(FeatureFlag::class, 'features', [
            [
                'code' => 'ALWAYS_ON',
                'title' => 'Always on feature',
            ],
            [
                'code' => 'ALWAYS_OFF',
                'title' => 'Always off feature',
            ],
            [
                'code' => 'MEMBER_FEATURE',
                'title' => 'Member scoped feature',
                'context' => [
                    'Member' => Member::class,
                ],
            ],
        ]);
    }

    public function testAllFeaturesReturnsConfiguredFeatures(): void
    {
        $features = FeatureFlag::allFeatures();

        $this->assertCount(3, $features);
        $this->assertSame('ALWAYS_ON', $features[0]['code']);
    }

    public function testGetFeatureReturnsMatchingDefinition(): void
    {
        $feature = FeatureFlag::getFeature('MEMBER_FEATURE');

        $this->assertNotNull($feature);
        $this->assertSame('Member scoped feature', $feature['title']);
        $this->assertSame(Member::class, $feature['context']['Member']);
    }

    public function testGetFeatureReturnsNullForUnknownCode(): void
    {
        $this->assertNull(FeatureFlag::getFeature('DOES_NOT_EXIST'));
    }

    public function testIsEnabledReturnsFalseWhenSelectionMissing(): void
    {
        $this->assertFalse(FeatureFlag::isEnabled('MISSING_FEATURE', []));
    }

    public function testIsEnabledOnMode(): void
    {
        $this->assertTrue(FeatureFlag::isEnabled('ALWAYS_ON', []));
    }

    public function testIsEnabledOffMode(): void
    {
        $this->assertFalse(FeatureFlag::isEnabled('ALWAYS_OFF', []));
    }

    public function testIsEnabledPartialModeMatchesMemberContext(): void
    {
        $member = $this->objFromFixture(Member::class, 'allowed');
        $other = $this->objFromFixture(Member::class, 'denied');

        $this->assertTrue(FeatureFlag::isEnabled('MEMBER_FEATURE', ['Member' => $member]));
        $this->assertFalse(FeatureFlag::isEnabled('MEMBER_FEATURE', ['Member' => $other]));
        $this->assertFalse(FeatureFlag::isEnabled('MEMBER_FEATURE', []));
    }

    public function testFeatureSelectionTitleUsesConfiguredTitle(): void
    {
        /** @var FeatureSelection $selection */
        $selection = $this->objFromFixture(FeatureSelection::class, 'memberFeature');

        $this->assertSame('Member scoped feature', $selection->getTitle());
    }

    public function testFeatureSelectionCannotBeCreatedOrDeleted(): void
    {
        $selection = FeatureSelection::create();

        $this->assertFalse($selection->canCreate());
        $this->assertFalse($selection->canDelete());
    }

    public function testRequireDefaultRecordsCreatesMissingSelections(): void
    {
        Config::modify()->merge(FeatureFlag::class, 'features', [
            [
                'code' => 'NEW_FEATURE',
                'title' => 'Created by defaults',
            ],
        ]);

        $this->assertSame(0, FeatureSelection::get()->filter(['Code' => 'NEW_FEATURE'])->count());

        FeatureSelection::singleton()->requireDefaultRecords();

        $this->assertSame(1, FeatureSelection::get()->filter(['Code' => 'NEW_FEATURE'])->count());
    }

    public function testPartialFeatureHasContextItems(): void
    {
        /** @var FeatureSelection $selection */
        $selection = $this->objFromFixture(FeatureSelection::class, 'memberFeature');
        /** @var FeatureSelectionItem $item */
        $item = $this->objFromFixture(FeatureSelectionItem::class, 'allowedMember');

        $this->assertSame(1, $selection->Items()->count());
        $this->assertSame('Member', $item->ContextKey);
        $this->assertSame((int) $this->idFromFixture(Member::class, 'allowed'), (int) $item->ContextID);
    }

    public function testContextFieldNames(): void
    {
        /** @var FeatureSelection $member */
        $member = $this->objFromFixture(FeatureSelection::class, 'memberFeature');
        /** @var FeatureSelection $plain */
        $plain = $this->objFromFixture(FeatureSelection::class, 'alwaysOn');

        $this->assertSame(['Member'], $member->getContextFieldNames());
        $this->assertSame([], $plain->getContextFieldNames());
    }

    public function testSaveContextFromForm(): void
    {
        /** @var FeatureSelection $selection */
        $selection = $this->objFromFixture(FeatureSelection::class, 'memberFeature');
        $allowedID = (int) $this->idFromFixture(Member::class, 'allowed');
        $deniedID = (int) $this->idFromFixture(Member::class, 'denied');

        $form = Form::create(null, 'Form', $selection->getCMSFields(), FieldList::create());
        $form->Fields()->dataFieldByName('Member')->setValue([(string) $deniedID]);
        $selection->saveContextFromForm($form);

        $this->assertSame([$deniedID], array_map('intval', $selection->Items()->column('ContextID')));
        $this->assertNotContains($allowedID, array_map('intval', $selection->Items()->column('ContextID')));
    }
}
