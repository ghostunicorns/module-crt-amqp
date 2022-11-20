<?php
/*
 * Copyright © GhostUnicorns. All rights reserved.
 * See LICENSE and/or COPYING.txt for license details.
 */

declare(strict_types=1);

namespace GhostUnicorns\CrtAmqp\Model\Data;

use GhostUnicorns\CrtAmqp\Api\Data\TransferorInfoInterface;

class TransferorInfo implements TransferorInfoInterface
{
    /**
     * @var int
     */
    private $activity_id;

    /**
     * @var string
     */
    private $transferor_type;

    /**
     * @param int $activity_id
     * @param string $transferor_type
     */
    public function __construct(
        int $activity_id,
        string $transferor_type
    ) {
        $this->activity_id = $activity_id;
        $this->transferor_type = $transferor_type;
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
    public function getTransferorType(): string
    {
        return $this->transferor_type;
    }
}
