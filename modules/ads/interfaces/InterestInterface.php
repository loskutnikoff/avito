<?php

namespace app\modules\ads\interfaces;

use app\modules\ads\dto\LeadDataDto;

interface InterestInterface
{
    public function createInterest(LeadDataDto $leadData): ?int;
}
