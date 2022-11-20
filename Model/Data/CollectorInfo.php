<?php
/*
 * Copyright © GhostUnicorns. All rights reserved.
 * See LICENSE and/or COPYING.txt for license details.
 */

declare(strict_types=1);

namespace GhostUnicorns\CrtAmqp\Model\Data;

use GhostUnicorns\CrtAmqp\Api\Data\CollectorInfoInterface;

class CollectorInfo implements CollectorInfoInterface
{
    /**
     * @var int
     */
    private $activity_id;

    /**
     * @var string
     */
    private $collector_type;

    /**
     * @param int $activity_id
     * @param string $collector_type
     */
    public function __construct(
        int $activity_id,
        string $collector_type
    ) {
        $this->activity_id = $activity_id;
        $this->collector_type = $collector_type;
    }

    /**
     * @return int
     */
    public function getActivityId(): int
    {
        return $this->activity_id;
    }

    /**
     * @return string
     */
    public function getCollectorType(): string
    {
        return $this->collector_type;
    }
}
