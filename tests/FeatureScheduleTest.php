<?php

namespace SilverStripe\FeatureFlags\Tests;

use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\FeatureFlags\FeatureFlag;
use SilverStripe\FeatureFlags\FeatureSelection;
use SilverStripe\ORM\FieldType\DBDatetime;

class FeatureScheduleTest extends SapphireTest
{
    protected static $fixture_file = 'FeatureFlagTest.yml';

    protected function setUp(): void
    {
        parent::setUp();

        Config::modify()->set(FeatureFlag::class, 'features', [
            ['code' => 'ALWAYS_ON', 'title' => 'Always on feature'],
            ['code' => 'ALWAYS_OFF', 'title' => 'Always off feature'],
        ]);

        DBDatetime::set_mock_now('2026-01-01 09:00:00');
    }

    protected function tearDown(): void
    {
        DBDatetime::clear_mock_now();

        parent::tearDown();
    }

    private function schedule(string $fixture, string $mode, string $at): FeatureSelection
    {
        /** @var FeatureSelection $selection */
        $selection = $this->objFromFixture(FeatureSelection::class, $fixture);
        $selection->ScheduledMode = $mode;
        $selection->ScheduledAt = $at;
        $selection->write();

        return $selection;
    }

    public function testFutureScheduleDoesNotChangeMode(): void
    {
        $selection = $this->schedule('alwaysOff', 'On', '2026-01-02 02:00:00');

        $this->assertFalse($selection->isScheduleDue());
        $this->assertSame('Off', $selection->getEffectiveMode());
        $this->assertFalse(FeatureFlag::isEnabled('ALWAYS_OFF'));
    }

    public function testScheduleTakesEffectOnceDue(): void
    {
        $this->schedule('alwaysOff', 'On', '2026-01-02 02:00:00');

        DBDatetime::set_mock_now('2026-01-02 02:00:00');
        $this->assertTrue(FeatureFlag::isEnabled('ALWAYS_OFF'));
    }

    public function testScheduleCanTurnFeatureOff(): void
    {
        $this->schedule('alwaysOn', 'Off', '2026-01-01 08:00:00');

        $this->assertFalse(FeatureFlag::isEnabled('ALWAYS_ON'));
    }

    public function testApplySchedulePersistsAndClearsSchedule(): void
    {
        $selection = $this->schedule('alwaysOff', 'On', '2026-01-02 02:00:00');

        $this->assertFalse($selection->applySchedule());

        DBDatetime::set_mock_now('2026-01-02 03:00:00');
        $this->assertTrue($selection->applySchedule());

        $selection = FeatureSelection::get()->byID($selection->ID);
        $this->assertSame('On', $selection->EnableMode);
        $this->assertEmpty($selection->ScheduledMode);
        $this->assertEmpty($selection->ScheduledAt);
        $this->assertSame('', $selection->getScheduleSummary());
    }

    public function testValidationRequiresScheduledAt(): void
    {
        /** @var FeatureSelection $selection */
        $selection = $this->objFromFixture(FeatureSelection::class, 'alwaysOff');
        $selection->ScheduledMode = 'On';

        $this->assertFalse($selection->validate()->isValid());

        $selection->ScheduledAt = '2026-01-02 02:00:00';
        $this->assertTrue($selection->validate()->isValid());
    }

    public function testValidationRejectsUnknownMode(): void
    {
        /** @var FeatureSelection $selection */
        $selection = $this->objFromFixture(FeatureSelection::class, 'alwaysOff');
        $selection->ScheduledMode = 'Sometimes';
        $selection->ScheduledAt = '2026-01-02 02:00:00';

        $this->assertFalse($selection->validate()->isValid());
    }
}
