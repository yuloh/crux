<?php

namespace Yuloh\Crux;

use Yuloh\Crux\Concerns;

trait ApplicationTrait
{
    use Concerns\EmitsEvents,
        Concerns\HandlesHttpRequests;
}
