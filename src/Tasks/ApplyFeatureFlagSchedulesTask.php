<?php

namespace SilverStripe\FeatureFlags\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\FeatureFlags\FeatureSelection;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Persists any scheduled feature flag changes which are now due.
 *
 * Scheduled changes take effect at check time without this task, so running it
 * (e.g. via cron) is optional and only keeps the stored mode in sync.
 */
class ApplyFeatureFlagSchedulesTask extends BuildTask
{
    protected static string $commandName = 'apply-feature-flag-schedules';

    protected string $title = 'Apply feature flag schedules';

    protected static string $description = 'Persist scheduled feature flag mode changes which are now due';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $count = 0;
        foreach (FeatureSelection::get()->exclude(['ScheduledAt' => null]) as $selection) {
            if ($selection->applySchedule()) {
                $output->writeln(sprintf('Set %s to %s', $selection->Code, $selection->EnableMode));
                $count++;
            }
        }

        $output->writeln(sprintf('Applied %d scheduled change(s)', $count));

        return Command::SUCCESS;
    }
}
