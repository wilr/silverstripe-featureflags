<?php

namespace SilverStripe\FeatureFlags\Context;

use SilverStripe\Security\Member;

class MemberFieldProvider extends DataObjectFieldProvider
{
    protected function getDataClass(): string
    {
        return Member::class;
    }
}
