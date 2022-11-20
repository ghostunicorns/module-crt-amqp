<?php
/*
 * Copyright © GhostUnicorns. All rights reserved.
 * See LICENSE and/or COPYING.txt for license details.
 */

declare(strict_types=1);

namespace GhostUnicorns\CrtAmqp\Api\Data;

interface TransferorInfoInterface
{
    /**
     * @return int
     */
    public function getActivityId(): int;

    /**
     * @return string
     */
    public function getTransferorType(): string;
}
